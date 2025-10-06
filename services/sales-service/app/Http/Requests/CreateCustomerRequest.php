<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Customer Request
 */
class CreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Will be handled by JWT middleware
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:200',
                'regex:/^[a-zA-ZÀ-ÿ\s\-\'\.]+$/u',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^[0-9\(\)\-\s\.]+$/',
            ],
            'document' => [
                'required',
                'string',
                'regex:/^[0-9\.\-\/]+$/',
            ],
            'address_street' => [
                'nullable',
                'string',
                'max:255',
            ],
            'address_number' => [
                'nullable',
                'string',
                'max:20',
            ],
            'address_complement' => [
                'nullable',
                'string',
                'max:100',
            ],
            'address_city' => [
                'nullable',
                'string',
                'max:100',
            ],
            'address_state' => [
                'nullable',
                'string',
                'size:2',
                'regex:/^[A-Z]{2}$/',
            ],
            'address_zip_code' => [
                'nullable',
                'string',
                'regex:/^[0-9\-]+$/',
            ],
        ];
    }
}
