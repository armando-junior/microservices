<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Usuário só pode atualizar seus próprios dados
        return true; // A verificação será feita no controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:1',
                'max:100',
                'regex:/^[a-zA-ZÀ-ÿ\s\-\'\.]+$/',
            ],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email:rfc',
                'max:255',
                'unique:users,email,' . $userId . ',id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.string' => 'O nome deve ser um texto.',
            'name.min' => 'O nome deve ter pelo menos :min caractere.',
            'name.max' => 'O nome não pode ter mais de :max caracteres.',
            'name.regex' => 'O nome contém caracteres inválidos.',
            
            'email.required' => 'O e-mail é obrigatório.',
            'email.string' => 'O e-mail deve ser um texto.',
            'email.email' => 'O e-mail deve ser válido.',
            'email.max' => 'O e-mail não pode ter mais de :max caracteres.',
            'email.unique' => 'Este e-mail já está em uso.',
        ];
    }
}

