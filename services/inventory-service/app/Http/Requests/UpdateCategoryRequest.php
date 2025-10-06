<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Category Request
 */
class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'min:2',
                'max:100',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
            'status' => [
                'sometimes',
                'string',
                'in:active,inactive',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.min' => 'Category name must be at least 2 characters long',
            'name.max' => 'Category name must not exceed 100 characters',
            'description.max' => 'Description must not exceed 500 characters',
            'status.in' => 'Status must be either active or inactive',
        ];
    }
}
