<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\ProductRelease;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProductReleaseRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');
        /** @var ProductRelease|null $release */
        $release = $this->route('release');

        return [
            'version' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_releases', 'version')
                    ->where('product_id', $product->id)
                    ->ignore($release),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'release_notes' => ['nullable', 'string', 'max:20000'],
            'released_at' => ['required', 'date'],
            'available_until' => ['nullable', 'date', 'after:released_at'],
            'download_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'status' => ['required', 'boolean'],
            'release_file' => [
                $release === null ? 'required' : 'nullable',
                'file',
                'max:512000',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->safe()->except('release_file');
    }
}
