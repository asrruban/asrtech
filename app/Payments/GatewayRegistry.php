<?php

namespace App\Payments;

use App\Services\SettingService;

/**
 * Discovers gateway modules from app/Payments/{Name} folders, the way
 * WHMCS scans modules/gateways — add a folder with a {Name}Gateway
 * class and it appears in the admin panel; add Callback/{Name}Callback
 * beside it to receive webhooks and payment returns.
 */
class GatewayRegistry
{
    public const DEFAULT = 'sandbox';

    public function __construct(private readonly SettingService $settings) {}

    /** @return array<string, Gateway> */
    public function all(): array
    {
        $gateways = [];

        foreach (glob(app_path('Payments/*'), GLOB_ONLYDIR) ?: [] as $directory) {
            $name = basename($directory);
            $class = "App\\Payments\\{$name}\\{$name}Gateway";

            if (! class_exists($class) || ! is_subclass_of($class, Gateway::class)) {
                continue;
            }

            /** @var Gateway $gateway */
            $gateway = app($class);
            $gateways[$gateway->key()] = $gateway;
        }

        uasort($gateways, fn (Gateway $a, Gateway $b): int => [! $a->ready(), $a->displayName()] <=> [! $b->ready(), $b->displayName()]);

        return $gateways;
    }

    /** @return list<string> */
    public function readyKeys(): array
    {
        return array_keys(array_filter($this->all(), fn (Gateway $gateway): bool => $gateway->ready()));
    }

    /**
     * Keys the admin has activated, whether or not the gateway is
     * configured yet. Checkout uses enabledKeys(), which additionally
     * requires the gateway to be ready.
     *
     * @return list<string>
     */
    public function activeKeys(): array
    {
        $stored = $this->settings->get('payment_gateways');

        $keys = $stored === null
            ? array_filter([$this->settings->get('payment_gateway')])
            : array_filter(explode(',', $stored));

        $keys = array_values(array_intersect($keys, array_keys($this->all())));

        return $keys === [] ? [self::DEFAULT] : $keys;
    }

    public function find(string $key): ?Gateway
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Keys the admin enabled for checkout, filtered to gateways that
     * are actually ready. Falls back to the legacy single-gateway
     * setting, then to the sandbox default.
     *
     * @return list<string>
     */
    public function enabledKeys(): array
    {
        $stored = $this->settings->get('payment_gateways');

        $keys = $stored === null
            ? array_filter([$this->settings->get('payment_gateway')])
            : array_filter(explode(',', $stored));

        $keys = array_values(array_intersect($keys, $this->readyKeys()));

        return $keys === []
            ? array_values(array_intersect([self::DEFAULT], $this->readyKeys()))
            : $keys;
    }

    /**
     * Enabled gateway modules in display order.
     *
     * @return array<string, Gateway>
     */
    public function enabled(): array
    {
        return array_filter(
            $this->all(),
            fn (Gateway $gateway): bool => in_array($gateway->key(), $this->enabledKeys(), true),
        );
    }

    public function findEnabled(string $key): ?Gateway
    {
        return $this->enabled()[$key] ?? null;
    }

    /**
     * The gateway used when the customer does not pick one.
     */
    public function default(): Gateway
    {
        $enabled = $this->enabled();

        if ($enabled === []) {
            throw new \RuntimeException('No payment gateway is enabled.');
        }

        return reset($enabled);
    }

    /**
     * The callback handler living beside the gateway class, if any.
     */
    public function callback(string $key): ?object
    {
        $gateway = $this->find($key);

        if ($gateway === null) {
            return null;
        }

        $name = class_basename($gateway::class);
        $name = substr($name, 0, -strlen('Gateway'));
        $class = "App\\Payments\\{$name}\\Callback\\{$name}Callback";

        return class_exists($class) ? app($class) : null;
    }
}
