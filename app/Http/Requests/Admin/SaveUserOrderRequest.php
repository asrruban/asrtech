<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserOrderRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'product_price_id' => [
                'nullable',
                'integer',
                Rule::exists('product_prices', 'id')->where('product_id', $this->integer('product_id')),
            ],
            'mark_paid' => ['required', 'boolean'],
            'complimentary' => ['required', 'boolean'],
        ];
    }
}
