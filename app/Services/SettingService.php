<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    public function get(string $key, ?string $default = null): ?string
    {
        $setting = Setting::query()->where('setting', $key)->first();

        return $setting === null ? $default : ($setting->value ?? $default);
    }

    public function put(string $key, ?string $value): void
    {
        Setting::query()->updateOrCreate(
            ['setting' => $key],
            ['value' => $value],
        );
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        $settings = [];

        foreach (Setting::query()->get(['setting', 'value']) as $setting) {
            $settings[$setting->setting] = $setting->value;
        }

        return $settings;
    }
}
