<?php

namespace App\Payments;

use App\Services\SettingService;

/**
 * Base class for payment gateway modules, WHMCS-style: each gateway
 * lives in its own app/Payments/{Name} folder containing a
 * {Name}Gateway class (and optionally Callback/{Name}Callback for
 * webhooks and payment returns). Folders are discovered automatically
 * and appear on the admin Payment Gateways page with their config
 * form. Per-gateway credentials live in the encrypted settings store
 * under "gateway.{key}.{field}".
 */
abstract class Gateway implements PaymentGateway
{
    public function __construct(protected readonly SettingService $settings) {}

    /**
     * Unique machine key, e.g. "stripe".
     */
    abstract public function key(): string;

    /**
     * Module name. Admins can override it with the display name field.
     */
    abstract public function name(): string;

    abstract public function description(): string;

    /**
     * Whether the gateway is fully configured and can take payments.
     */
    public function ready(): bool
    {
        return false;
    }

    /**
     * Whether the module's charge flow is implemented. Unimplemented
     * modules show as "coming soon" and cannot be activated.
     */
    public function implemented(): bool
    {
        return true;
    }

    /**
     * Setup instructions shown next to the webhook/return URLs on the
     * admin page (e.g. where to register the webhook endpoint).
     */
    public function webhookInstructions(): ?string
    {
        return null;
    }

    /**
     * Admin-facing name, honoring the per-gateway rename setting.
     */
    public function displayName(): string
    {
        return $this->config('display_name') ?: $this->name();
    }

    /**
     * Module-specific config fields. Supported types: text, password
     * (write-only), yesno, select (with options).
     *
     * @return list<array{name: string, label: string, type: string, description?: string, options?: array<string, string>, required?: bool}>
     */
    public function configFields(): array
    {
        return [];
    }

    /**
     * All fields shown on the admin page: the shared display-name
     * override followed by the module's own fields.
     *
     * @return list<array{name: string, label: string, type: string, description?: string, options?: array<string, string>, required?: bool}>
     */
    final public function fields(): array
    {
        return [
            [
                'name' => 'display_name',
                'label' => 'Display name',
                'type' => 'text',
                'description' => "Shown instead of \"{$this->name()}\" when set.",
                'required' => false,
            ],
            ...$this->configFields(),
        ];
    }

    /**
     * True when every required config field has a stored value.
     */
    public function isConfigured(): bool
    {
        foreach ($this->configFields() as $field) {
            if (($field['required'] ?? true) && blank($this->config($field['name']))) {
                return false;
            }
        }

        return true;
    }

    public function config(string $field, ?string $default = null): ?string
    {
        return $this->settings->get("gateway.{$this->key()}.{$field}", $default);
    }
}
