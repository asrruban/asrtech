<?php

namespace Tests\Feature;

use App\Enums\ReferralStatus;
use App\Events\OrderPaid;
use App\Models\Admin;
use App\Models\Affiliate;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Referral;
use App\Models\User;
use App\Services\AffiliateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_join_and_gets_a_referral_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/client-area/affiliate/join')
            ->assertRedirect('/client-area/affiliate');

        $affiliate = Affiliate::query()->sole();
        $this->assertSame($user->id, $affiliate->user_id);
        $this->assertSame(8, strlen($affiliate->code));
        $this->assertTrue($affiliate->active);
    }

    public function test_referral_cookie_attributes_new_registration(): void
    {
        $promoter = User::factory()->create();
        $affiliate = app(AffiliateService::class)->join($promoter);

        $response = $this->get('/?ref='.$affiliate->code);
        $response->assertCookie('aff_ref', $affiliate->code);

        $this->withCookie('aff_ref', $affiliate->code)
            ->post('/register', [
                'name' => 'Referred Buyer',
                'email' => 'buyer@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ]);

        $buyer = User::query()->where('email', 'buyer@example.com')->sole();
        $this->assertSame($affiliate->id, $buyer->referred_by_affiliate_id);
    }

    public function test_paid_order_credits_pending_commission_once(): void
    {
        $promoter = User::factory()->create();
        $affiliate = app(AffiliateService::class)->join($promoter);
        $buyer = User::factory()->create(['referred_by_affiliate_id' => $affiliate->id]);

        [$product, $price] = $this->productAndPrice();

        $this->actingAs($buyer)
            ->post("/checkout/{$product->slug}/prices/{$price->id}", ['gateway' => 'sandbox']);

        $referral = Referral::query()->sole();
        $this->assertSame($affiliate->id, $referral->affiliate_id);
        $this->assertSame(ReferralStatus::Pending, $referral->status);
        $this->assertSame('10.00', $referral->commission_amount); // 10% of 100
        $this->assertSame('10.00', $affiliate->fresh()->pending_balance);

        // Paying the same order again must not double-credit.
        $order = $buyer->orders()->sole();
        OrderPaid::dispatch($order->refresh());
        $this->assertSame(1, Referral::query()->count());
    }

    public function test_admin_approve_and_pay_moves_balances(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Site Admin',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'a-secure-password',
        ]);
        $promoter = User::factory()->create();
        $affiliate = app(AffiliateService::class)->join($promoter);
        $buyer = User::factory()->create(['referred_by_affiliate_id' => $affiliate->id]);

        [$product, $price] = $this->productAndPrice();
        $this->actingAs($buyer)
            ->post("/checkout/{$product->slug}/prices/{$price->id}", ['gateway' => 'sandbox']);

        $referral = Referral::query()->sole();

        $this->actingAs($admin, 'admin')
            ->post("/admin/referrals/{$referral->id}/approve")
            ->assertRedirect('/admin/affiliates');

        $affiliate->refresh();
        $this->assertSame(ReferralStatus::Approved, $referral->fresh()->status);
        $this->assertSame('0.00', $affiliate->pending_balance);
        $this->assertSame('10.00', $affiliate->approved_balance);

        $this->actingAs($admin, 'admin')
            ->post("/admin/referrals/{$referral->id}/pay")
            ->assertRedirect('/admin/affiliates');

        $affiliate->refresh();
        $this->assertSame(ReferralStatus::Paid, $referral->fresh()->status);
        $this->assertSame('0.00', $affiliate->approved_balance);
        $this->assertSame('10.00', $affiliate->paid_balance);
    }

    public function test_self_referral_is_never_credited(): void
    {
        $promoter = User::factory()->create();
        $affiliate = app(AffiliateService::class)->join($promoter);
        $promoter->forceFill(['referred_by_affiliate_id' => $affiliate->id])->save();

        [$product, $price] = $this->productAndPrice();
        $this->actingAs($promoter)
            ->post("/checkout/{$product->slug}/prices/{$price->id}", ['gateway' => 'sandbox']);

        $this->assertSame(0, Referral::query()->count());
    }

    /** @return array{0: Product, 1: ProductPrice} */
    private function productAndPrice(): array
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'modules'],
            ['name' => 'Modules', 'status' => true],
        );
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Affiliate Product '.fake()->unique()->numerify('###'),
            'slug' => 'affiliate-product-'.fake()->unique()->numerify('###'),
            'type' => 'whmcs_module',
            'price' => 100,
            'status' => true,
            'featured' => false,
        ]);
        $price = $product->prices()->create([
            'billing_cycle' => 'one_time',
            'currency' => 'USD',
            'price' => 100,
            'setup_fee' => 0,
            'enabled' => true,
        ]);

        return [$product, $price];
    }
}
