<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Payments\GatewayRegistry;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminGatewaySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_modules_are_discovered_from_their_folders(): void
    {
        $registry = app(GatewayRegistry::class);

        $this->assertArrayHasKey('sandbox', $registry->all());
        $this->assertArrayHasKey('stripe', $registry->all());
        $this->assertArrayHasKey('paypal', $registry->all());
        $this->assertArrayHasKey('bkash', $registry->all());
        $this->assertArrayHasKey('sslcommerz', $registry->all());
        $this->assertSame(['free_trial', 'sandbox'], $registry->readyKeys());
    }

    public function test_stripe_becomes_ready_once_configured(): void
    {
        $settings = app(SettingService::class);
        $settings->put('gateway.stripe.publishable_key', 'pk_test_123');
        $settings->put('gateway.stripe.secret_key', 'sk_test_456');

        $this->assertContains('stripe', app(GatewayRegistry::class)->readyKeys());
    }

    public function test_stripe_callback_handler_is_discovered(): void
    {
        $this->assertNotNull(app(GatewayRegistry::class)->callback('stripe'));
        $this->assertNull(app(GatewayRegistry::class)->callback('paypal'));
    }

    public function test_admin_sees_activation_state_and_stripe_callback_urls(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/settings/gateways')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Configuration/Settings/Gateways')
                ->has('gateways', 8)
                ->where('gateways', function ($gateways) {
                    $sandbox = collect($gateways)->firstWhere('key', 'sandbox');
                    $stripe = collect($gateways)->firstWhere('key', 'stripe');
                    $paypal = collect($gateways)->firstWhere('key', 'paypal');

                    return $sandbox['active'] === true
                        && $sandbox['live'] === true
                        && $stripe['active'] === false
                        && $stripe['implemented'] === true
                        && $paypal['implemented'] === false
                        && collect($stripe['callbackUrls'])->pluck('url')->contains(route('gateways.webhook', 'stripe'))
                        && collect($stripe['callbackUrls'])->pluck('url')->contains(route('gateways.return', 'stripe'))
                        && filled($stripe['webhookInstructions']);
                }));
    }

    public function test_stripe_can_be_activated_before_it_is_configured(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/settings/gateways/stripe/activate')
            ->assertRedirect('/admin/settings/gateways');

        $registry = app(GatewayRegistry::class);
        $this->assertSame('sandbox,stripe', app(SettingService::class)->get('payment_gateways'));
        $this->assertContains('stripe', $registry->activeKeys());
        // Not configured yet, so checkout still only offers the sandbox.
        $this->assertSame(['sandbox'], $registry->enabledKeys());
    }

    public function test_activated_stripe_goes_live_once_configured(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->post('/admin/settings/gateways/stripe/activate');

        $this->put('/admin/settings/gateways/stripe', [
            'config' => [
                'publishable_key' => 'pk_test_123',
                'secret_key' => 'sk_test_456',
            ],
        ])->assertRedirect('/admin/settings/gateways');

        $registry = app(GatewayRegistry::class);
        $this->assertSame('sk_test_456', app(SettingService::class)->get('gateway.stripe.secret_key'));
        $this->assertContains('stripe', $registry->enabledKeys());
    }

    public function test_configuration_requires_activation_first(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/settings/gateways/stripe', [
            'config' => [
                'publishable_key' => 'pk_test_123',
                'secret_key' => 'sk_test_456',
            ],
        ])->assertSessionHasErrors('config');

        $this->assertNull(app(SettingService::class)->get('gateway.stripe.secret_key'));
    }

    public function test_required_fields_are_enforced_when_configuring(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->post('/admin/settings/gateways/stripe/activate');

        $this->put('/admin/settings/gateways/stripe', [
            'config' => ['publishable_key' => 'pk_test_123', 'secret_key' => ''],
        ])->assertSessionHasErrors('config.secret_key');
    }

    public function test_unimplemented_gateways_cannot_be_activated(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/settings/gateways/paypal/activate')
            ->assertSessionHasErrors('gateway');

        $this->assertNotContains('paypal', app(GatewayRegistry::class)->activeKeys());
    }

    public function test_admin_can_deactivate_but_not_the_last_gateway(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->post('/admin/settings/gateways/stripe/activate');

        $this->delete('/admin/settings/gateways/stripe')
            ->assertRedirect('/admin/settings/gateways');
        $this->assertSame('sandbox', app(SettingService::class)->get('payment_gateways'));

        $this->delete('/admin/settings/gateways/sandbox')
            ->assertSessionHasErrors('gateway');
        $this->assertContains('sandbox', app(GatewayRegistry::class)->activeKeys());
    }

    public function test_admin_can_rename_a_gateway(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/settings/gateways/sandbox', [
            'config' => ['display_name' => 'Test Mode Payments'],
        ])->assertRedirect('/admin/settings/gateways');

        $this->assertSame('Test Mode Payments', app(GatewayRegistry::class)->find('sandbox')?->displayName());
    }

    public function test_secrets_are_write_only_and_kept_when_blank(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $settings = app(SettingService::class);
        $settings->put('gateway.stripe.publishable_key', 'pk_test_123');
        $settings->put('gateway.stripe.secret_key', 'sk_test_456');
        $this->post('/admin/settings/gateways/stripe/activate');

        $this->get('/admin/settings/gateways')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('gateways', function ($gateways) {
                    $stripe = collect($gateways)->firstWhere('key', 'stripe');
                    $secret = collect($stripe['fields'])->firstWhere('name', 'secret_key');
                    $publishable = collect($stripe['fields'])->firstWhere('name', 'publishable_key');

                    return $secret['value'] === null
                        && $secret['configured'] === true
                        && $publishable['value'] === 'pk_test_123';
                }));

        $this->put('/admin/settings/gateways/stripe', [
            'config' => [
                'publishable_key' => 'pk_test_123',
                'secret_key' => '',
            ],
        ]);

        $this->assertSame('sk_test_456', $settings->get('gateway.stripe.secret_key'));
    }

    public function test_legacy_single_gateway_setting_still_works(): void
    {
        $settings = app(SettingService::class);
        $settings->put('gateway.stripe.publishable_key', 'pk_test_123');
        $settings->put('gateway.stripe.secret_key', 'sk_test_456');
        $settings->put('payment_gateway', 'stripe');

        $this->assertSame(['stripe'], app(GatewayRegistry::class)->enabledKeys());
        $this->assertSame('stripe', app(GatewayRegistry::class)->default()->key());
    }

    public function test_enabled_gateways_fall_back_to_sandbox_for_unknown_keys(): void
    {
        app(SettingService::class)->put('payment_gateways', 'nonexistent');

        $this->assertSame(['sandbox'], app(GatewayRegistry::class)->enabledKeys());
        $this->assertSame('sandbox', app(GatewayRegistry::class)->default()->key());
    }

    public function test_gateway_page_requires_an_admin_session(): void
    {
        $this->get('/admin/settings/gateways')->assertRedirect('/admin/login');
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Gateway Admin',
            'email' => 'gateways@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
