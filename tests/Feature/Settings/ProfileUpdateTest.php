<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function validDetails(User $user): array
    {
        return [
            'first_name' => 'Al',
            'last_name' => 'Amin',
            'company_name' => 'ASRHost',
            'address_1' => 'Purbadhala Bazar',
            'city' => 'Purbadhala',
            'state' => 'Netrakona',
            'postcode' => '2400',
            'country' => 'BD',
            'phone' => '01785726034',
            'email' => $user->email,
            'vat_number' => '',
            'newsletter' => true,
        ];
    }

    public function test_account_details_page_is_displayed(): void
    {
        $user = User::factory()->create(['name' => 'Al Amin']);

        $this->actingAs($user)
            ->get('/client-area/account-details')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Account/Details')
                ->where('details.first_name', 'Al')
                ->where('details.last_name', 'Amin')
                ->where('details.account_id', $user->id)
                ->has('account')
                ->has('totalDue'));
    }

    public function test_account_details_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/client-area/account-details', $this->validDetails($user))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/client-area/account-details');

        $user->refresh();
        $this->assertSame('Al Amin', $user->name);
        $this->assertSame('ASRHost', $user->company_name);
        $this->assertSame('Purbadhala Bazar', $user->address_1);
        $this->assertSame('BD', $user->country);
        $this->assertTrue($user->newsletter);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_changing_email_requires_reverification(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/client-area/account-details', [
                ...$this->validDetails($user),
                'email' => 'new-address@example.com',
            ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('new-address@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_required_billing_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/client-area/account-details', [
                ...$this->validDetails($user),
                'first_name' => '',
                'address_1' => '',
                'phone' => '',
            ])->assertSessionHasErrors(['first_name', 'address_1', 'phone']);
    }

    public function test_password_can_be_changed_with_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/client-area/change-password')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Account/ChangePassword')
                ->where('hasPassword', true));

        $this->actingAs($user)
            ->patch('/client-area/change-password', [
                'current_password' => 'password',
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])->assertSessionHasNoErrors()
            ->assertRedirect('/client-area/change-password');

        $this->assertTrue(Hash::check('brand-new-password', $user->refresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/client-area/change-password', [
                'current_password' => 'not-the-password',
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])->assertSessionHasErrors('current_password');
    }

    public function test_social_accounts_can_set_a_password_without_a_current_one(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)
            ->get('/client-area/change-password')
            ->assertInertia(fn (Assert $page) => $page->where('hasPassword', false));

        $this->actingAs($user)
            ->patch('/client-area/change-password', [
                'password' => 'first-ever-password',
                'password_confirmation' => 'first-ever-password',
            ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('first-ever-password', $user->refresh()->password));
    }

    public function test_old_settings_urls_redirect_to_account_details(): void
    {
        $this->get('/settings')->assertRedirect('/client-area/account-details');
        $this->get('/settings/profile')->assertRedirect('/client-area/account-details');
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/client-area/account', [
                'password' => 'password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }
}
