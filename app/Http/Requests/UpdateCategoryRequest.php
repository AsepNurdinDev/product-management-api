<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('categories', 'name')
                    ->where('user_id', $this->user()->id)
                    ->ignore($category),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
