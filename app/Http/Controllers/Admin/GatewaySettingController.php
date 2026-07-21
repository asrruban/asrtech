<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Payments\Gateway;
use App\Payments\GatewayRegistry;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * WHMCS-style gateway management: activate a module first, then
 * configure it. Active-but-unconfigured gateways never appear at
 * checkout (the registry only offers ready gateways).
 */
class GatewaySettingController extends Controller
{
    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly SettingService $settings,
    ) {}

    public function edit(): Response
    {
        $active = $this->gateways->activeKeys();
        $live = $this->gateways->enabledKeys();

        return Inertia::render('Admin/Configuration/Settings/Gateways', [
            'gateways' => collect($this->gateways->all())
                ->map(fn (Gateway $gateway): array => [
                    'key' => $gateway->key(),
                    'name' => $gateway->displayName(),
                    'moduleName' => $gateway->name(),
                    'description' => $gateway->description(),
                    'implemented' => $gateway->implemented(),
                    'ready' => $gateway->ready(),
                    'configured' => $gateway->isConfigured(),
                    'active' => in_array($gateway->key(), $active, true),
                    'live' => in_array($gateway->key(), $live, true),
                    'callbackUrls' => $this->callbackUrls($gateway),
                    'webhookInstructions' => $gateway->webhookInstructions(),
                    'fields' => array_map(fn (array $field): array => [
                        'name' => $field['name'],
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'description' => $field['description'] ?? null,
                        'options' => $field['options'] ?? null,
                        'required' => $field['required'] ?? true,
                        // Secrets are write-only: only report their presence.
                        'value' => $field['type'] === 'password'
                            ? null
                            : $gateway->config($field['name']),
                        'configured' => filled($gateway->config($field['name'])),
                    ], $gateway->fields()),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function activate(string $gateway): RedirectResponse
    {
        $module = $this->gateways->find($gateway);

        abort_if($module === null, 404);

        if (! $module->implemented()) {
            throw ValidationException::withMessages([
                'gateway' => __(':name is not available yet.', ['name' => $module->displayName()]),
            ]);
        }

        $this->storeActive(array_unique([...$this->gateways->activeKeys(), $gateway]));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $module->ready()
                ? __(':name activated — it is now offered at checkout.', ['name' => $module->displayName()])
                : __(':name activated — add its credentials to start taking payments.', ['name' => $module->displayName()]),
        ]);

        return redirect()->route('admin.settings.gateways.edit');
    }

    public function deactivate(string $gateway): RedirectResponse
    {
        abort_if($this->gateways->find($gateway) === null, 404);

        $remaining = array_values(array_diff($this->gateways->activeKeys(), [$gateway]));

        if ($remaining === []) {
            throw ValidationException::withMessages([
                'gateway' => __('At least one payment gateway must stay active.'),
            ]);
        }

        $this->storeActive($remaining);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Gateway deactivated.')]);

        return redirect()->route('admin.settings.gateways.edit');
    }

    public function update(Request $request, string $gateway): RedirectResponse
    {
        $module = $this->gateways->find($gateway);

        abort_if($module === null, 404);

        if (! in_array($gateway, $this->gateways->activeKeys(), true)) {
            throw ValidationException::withMessages([
                'config' => __('Activate the gateway before configuring it.'),
            ]);
        }

        /** @var array<string, string|null> $values */
        $values = (array) $request->validate([
            'config' => ['required', 'array'],
            'config.*' => ['nullable', 'string', 'max:1000'],
        ])['config'];

        $errors = [];

        foreach ($module->fields() as $field) {
            if (! ($field['required'] ?? true)) {
                continue;
            }

            $provided = filled($values[$field['name']] ?? null);
            $stored = $field['type'] === 'password' && filled($module->config($field['name']));

            if (! $provided && ! $stored) {
                $errors["config.{$field['name']}"] = __(':label is required.', ['label' => $field['label']]);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        foreach ($module->fields() as $field) {
            if (! array_key_exists($field['name'], $values)) {
                continue;
            }

            $value = $values[$field['name']];

            // Blank password fields keep their stored secret.
            if ($field['type'] === 'password' && blank($value)) {
                continue;
            }

            $this->settings->put("gateway.{$gateway}.{$field['name']}", $value === null ? null : (string) $value);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name configuration saved.', [
            'name' => $module->displayName(),
        ])]);

        return redirect()->route('admin.settings.gateways.edit');
    }

    /** @param array<int, string> $keys */
    private function storeActive(array $keys): void
    {
        $this->settings->put('payment_gateways', implode(',', array_values($keys)));
        $this->settings->put('payment_gateway', null);
    }

    /**
     * Webhook and return URLs for modules that ship a callback handler.
     *
     * @return list<array{label: string, url: string}>
     */
    private function callbackUrls(Gateway $gateway): array
    {
        if ($this->gateways->callback($gateway->key()) === null) {
            return [];
        }

        return [
            [
                'label' => __('Webhook URL'),
                'url' => route('gateways.webhook', $gateway->key()),
            ],
            [
                'label' => __('Return URL'),
                'url' => route('gateways.return', $gateway->key()),
            ],
        ];
    }
}
