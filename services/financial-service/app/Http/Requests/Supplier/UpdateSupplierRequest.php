<?php

declare(strict_types=1);

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier');

        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'document' => ['nullable', 'string', 'size:14', "unique:suppliers,document,{$supplierId}"],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Supplier name is required',
            'name.min' => 'Supplier name must be at least 3 characters',
            'document.unique' => 'A supplier with this document already exists',
        ];
    }
}


