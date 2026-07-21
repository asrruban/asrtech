<?php

namespace App\Http\Requests\Admin;

use App\Services\StorageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStorageSettingsRequest extends FormRequest
{
    private const PATH_REGEX = 'regex:/^[A-Za-z0-9][A-Za-z0-9._\/-]*$/';

    protected function prepareForValidation(): void
    {
        $paths = [];

        foreach (['storage_path_branding', 'storage_path_tickets', 'storage_path_products'] as $key) {
            if (is_string($this->input($key))) {
                $paths[$key] = trim($this->input($key), " \t/");
            }
        }

        $this->merge($paths);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'storage_driver' => ['required', Rule::in(array_keys(StorageService::DRIVERS))],

            'storage_s3_key' => ['nullable', 'required_if:storage_driver,s3', 'string', 'max:255'],
            'storage_s3_secret' => ['nullable', 'string', 'max:1000'],
            'storage_s3_region' => ['nullable', 'required_if:storage_driver,s3', 'string', 'max:64'],
            'storage_s3_bucket' => ['nullable', 'required_if:storage_driver,s3', 'string', 'max:255'],
            'storage_s3_url' => ['nullable', 'url', 'max:2000'],

            'storage_r2_account_id' => ['nullable', 'required_if:storage_driver,r2', 'string', 'alpha_num:ascii', 'max:64'],
            'storage_r2_key' => ['nullable', 'required_if:storage_driver,r2', 'string', 'max:255'],
            'storage_r2_secret' => ['nullable', 'string', 'max:1000'],
            'storage_r2_bucket' => ['nullable', 'required_if:storage_driver,r2', 'string', 'max:255'],
            'storage_r2_url' => ['nullable', 'url', 'max:2000'],

            'storage_b2_key_id' => ['nullable', 'required_if:storage_driver,b2', 'string', 'max:255'],
            'storage_b2_key' => ['nullable', 'string', 'max:1000'],
            'storage_b2_region' => ['nullable', 'required_if:storage_driver,b2', 'string', 'max:64'],
            'storage_b2_bucket' => ['nullable', 'required_if:storage_driver,b2', 'string', 'max:255'],
            'storage_b2_url' => ['nullable', 'url', 'max:2000'],

            'storage_path_branding' => ['required', 'string', 'max:100', self::PATH_REGEX],
            'storage_path_tickets' => ['required', 'string', 'max:100', self::PATH_REGEX],
            'storage_path_products' => ['required', 'string', 'max:100', self::PATH_REGEX],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            '*.required_if' => __('This field is required for the selected storage driver.'),
            'storage_path_branding.regex' => __('Use letters, numbers, dashes, dots, and forward slashes only.'),
            'storage_path_tickets.regex' => __('Use letters, numbers, dashes, dots, and forward slashes only.'),
            'storage_path_products.regex' => __('Use letters, numbers, dashes, dots, and forward slashes only.'),
        ];
    }
}
