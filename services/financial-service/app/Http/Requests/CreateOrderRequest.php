<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Order Request
 */
class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Will be handled by JWT middleware
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'string',
                'uuid',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
