<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Product Request Validation
 */
class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:200'],
            'price' => ['sometimes', 'numeric', 'min:0.01', 'max:9999999.99'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'string', 'in:active,inactive,discontinued'],
        ];
    }
}

