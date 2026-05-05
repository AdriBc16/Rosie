<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AuthLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'correo' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['required', 'in:docente,estudiante'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
