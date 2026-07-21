<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveSeoSettingsRequest extends FormRequest
{
    public const VERIFICATION_FIELDS = [
        'google_site_verification',
        'bing_site_verification',
        'yandex_site_verification',
        'baidu_site_verification',
        'pinterest_site_verification',
    ];

    protected function prepareForValidation(): void
    {
        $normalized = [];

        // Accept a pasted full <meta ... content="..."> tag and keep
        // only the verification token itself.
        foreach (self::VERIFICATION_FIELDS as $field) {
            $value = $this->input($field);

            if (is_string($value) && preg_match('/content=["\']([^"\']+)["\']/', $value, $matches) === 1) {
                $normalized[$field] = $matches[1];
            } elseif (is_string($value)) {
                $normalized[$field] = trim($value);
            }
        }

        $this->merge($normalized);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'default_meta_title' => ['required', 'string', 'max:255'],
            'default_meta_description' => ['required', 'string', 'max:500'],

            'home_meta_title' => ['nullable', 'string', 'max:255'],
            'home_meta_description' => ['nullable', 'string', 'max:500'],
            'home_meta_keywords' => ['nullable', 'string', 'max:500'],

            'google_site_verification' => ['nullable', 'string', 'max:255'],
            'bing_site_verification' => ['nullable', 'string', 'max:255'],
            'yandex_site_verification' => ['nullable', 'string', 'max:255'],
            'baidu_site_verification' => ['nullable', 'string', 'max:255'],
            'pinterest_site_verification' => ['nullable', 'string', 'max:255'],

            'ga4_measurement_id' => ['nullable', 'string', 'regex:/^G-[A-Z0-9]{4,20}$/i'],
            'gtm_container_id' => ['nullable', 'string', 'regex:/^GTM-[A-Z0-9]{4,12}$/i'],
            'meta_pixel_id' => ['nullable', 'string', 'regex:/^[0-9]{5,30}$/'],

            'og_image' => ['nullable', 'image', 'max:2048'],
            'home_og_image' => ['nullable', 'image', 'max:2048'],
            'remove_og_image' => ['nullable', 'boolean'],
            'remove_home_og_image' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'ga4_measurement_id.regex' => __('Use the GA4 measurement ID format, e.g. G-XXXXXXXXXX.'),
            'gtm_container_id.regex' => __('Use the Tag Manager container ID format, e.g. GTM-XXXXXXX.'),
            'meta_pixel_id.regex' => __('The Meta Pixel ID is the numeric ID from Events Manager.'),
        ];
    }
}
