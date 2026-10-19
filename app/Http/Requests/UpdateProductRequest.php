<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => [
                'sometimes', 'required', 'integer',
                Rule::exists('categories', 'id')->where('user_id', $this->user()->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'sku' => [
                'sometimes', 'required', 'string', 'max:64',
                Rule::unique('products', 'sku')
                    ->where('user_id', $this->user()->id)
                    ->ignore($product),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'stock' => ['sometimes', 'integer', 'min:0', 'max:2147483647'],
            'image' => ['sometimes', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }
}
