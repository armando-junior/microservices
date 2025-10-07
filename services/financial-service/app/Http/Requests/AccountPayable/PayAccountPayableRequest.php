<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountPayable;

use Illuminate\Foundation\Http\FormRequest;

class PayAccountPayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}


