<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MateriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'id_carrera' => ['required', 'integer', 'exists:carrera,id_carrera'],
            'id_semestre' => ['required', 'integer', 'exists:semestre,id_semestre'],
            'horas_semanales' => ['nullable', 'integer', 'min:1', 'max:20'],
            'ano_academico' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
