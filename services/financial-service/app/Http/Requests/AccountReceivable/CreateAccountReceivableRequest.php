<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountReceivable;

use Illuminate\Foundation\Http\FormRequest;

class CreateAccountReceivableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'uuid'],
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'issue_date' => ['required', 'date', 'date_format:Y-m-d'],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:365'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer is required',
            'category_id.required' => 'Category is required',
            'category_id.exists' => 'Category not found',
            'amount.required' => 'Amount is required',
            'amount.min' => 'Amount must be greater than zero',
            'issue_date.required' => 'Issue date is required',
            'payment_terms_days.required' => 'Payment terms is required',
            'payment_terms_days.max' => 'Payment terms cannot exceed 365 days',
        ];
    }
}


