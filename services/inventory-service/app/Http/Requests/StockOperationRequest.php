<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Stock Operation Request Validation
 * (usado para increase e decrease)
 */
class StockOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
            'reference_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Quantity is required',
            'quantity.min' => 'Quantity must be at least 1',
            'reason.required' => 'Reason is required',
            'reason.min' => 'Reason must be at least 5 characters',
        ];
    }
}

