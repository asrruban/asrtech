<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Mail\SubscriptionDunningReminderMail;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DunningService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DunningTest extends TestCase
{
    use RefreshDatabase;

    public function test_dunning_ladder_sends_escalating_reminders(): void
    {
        Mail::fake();

        $subscription = $this->pastDueSubscription();

        // Day 0: no reminder due yet.
        app(DunningService::class)->process();
        Mail::assertNotQueued(SubscriptionDunningReminderMail::class);
        $this->assertSame(0, $subscription->refresh()->dunning_reminders_sent);

        // Day 2: first rung (day 1) is due.
        $this->travel(2)->days();
        app(DunningService::class)->process();
        Mail::assertQueued(SubscriptionDunningReminderMail::class, fn ($mail) => $mail->attempt === 1 && ! $mail->isFinal);
        $this->assertSame(1, $subscription->refresh()->dunning_reminders_sent);

        // Re-running must not duplicate.
        app(DunningService::class)->process();
        Mail::assertQueued(SubscriptionDunningReminderMail::class, 1);

        // Day 4: second rung (day 3).
        $this->travel(2)->days();
        app(DunningService::class)->process();
        Mail::assertQueued(SubscriptionDunningReminderMail::class, 2);
        $this->assertSame(2, $subscription->refresh()->dunning_reminders_sent);

        // Day 8: final rung (day 7) marked as final notice.
        $this->travel(4)->days();
        app(DunningService::class)->process();
        Mail::assertQueued(SubscriptionDunningReminderMail::class, fn ($mail) => $mail->attempt === 3 && $mail->isFinal);
        $this->assertSame(3, $subscription->refresh()->dunning_reminders_sent);

        // Ladder exhausted: nothing more.
        $this->travel(5)->days();
        app(DunningService::class)->process();
        Mail::assertQueued(SubscriptionDunningReminderMail::class, 3);
    }

    public function test_dunning_counter_resets_when_payment_succeeds(): void
    {
        Mail::fake();

        $subscription = $this->pastDueSubscription();

        $this->travel(2)->days();
        app(DunningService::class)->process();
        $this->assertSame(1, $subscription->refresh()->dunning_reminders_sent);

        app(SubscriptionService::class)->syncGatewayStatus(
            $subscription,
            'evt_recovery_'.fake()->unique()->numerify('###'),
            SubscriptionStatus::Active,
            now(),
            now()->addMonth(),
            false,
        );

        $this->assertSame(0, $subscription->refresh()->dunning_reminders_sent);
    }

    public function test_dunning_can_be_disabled_via_config(): void
    {
        Mail::fake();
        config()->set('asrtech.dunning.enabled', false);

        $subscription = $this->pastDueSubscription();

        $this->travel(10)->days();
        app(DunningService::class)->process();

        Mail::assertNotQueued(SubscriptionDunningReminderMail::class);
        $this->assertSame(0, $subscription->refresh()->dunning_reminders_sent);
    }

    private function pastDueSubscription(): Subscription
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'modules'],
            ['name' => 'Modules', 'status' => true],
        );
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Automation Toolkit',
            'slug' => 'automation-toolkit-'.fake()->unique()->numerify('###'),
            'type' => 'whmcs_module',
            'price' => 29,
            'status' => true,
            'featured' => false,
        ]);
        $price = $product->prices()->create([
            'billing_cycle' => 'monthly',
            'currency' => 'USD',
            'price' => 29,
            'setup_fee' => 0,
            'enabled' => true,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post("/checkout/{$product->slug}/prices/{$price->id}", ['gateway' => 'sandbox']);

        $subscription = $user->subscriptions()->sole();

        app(SubscriptionService::class)->markPaymentFailed(
            $subscription,
            'evt_fail_'.fake()->unique()->numerify('###'),
            'inv_123',
        );

        return $subscription->refresh();
    }
}
