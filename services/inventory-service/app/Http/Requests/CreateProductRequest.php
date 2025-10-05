<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Product Request Validation
 */
class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:200'],
            'sku' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[A-Z0-9\-]+$/'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'name.min' => 'Product name must be at least 2 characters',
            'sku.required' => 'SKU is required',
            'sku.regex' => 'SKU must contain only uppercase letters, numbers and hyphens',
            'price.required' => 'Price is required',
            'price.min' => 'Price must be greater than zero',
            'category_id.exists' => 'Category not found',
        ];
    }
}

