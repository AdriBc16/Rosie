<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class MateriaIngestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'materias' => ['required', 'array', 'min:1'],
            'materias.*.nombre' => ['required', 'string', 'max:120'],
        ];
    }
}
