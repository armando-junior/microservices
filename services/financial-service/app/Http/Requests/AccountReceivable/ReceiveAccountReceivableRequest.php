<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountReceivable;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveAccountReceivableRequest extends FormRequest
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


