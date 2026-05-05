<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HistorialMateriaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_estudiante' => ['required','integer','exists:estudiante,id_estudiante'],
            'id_materia' => ['required','integer','exists:materia,id_materia'],
            'convalidada' => ['nullable','boolean'],
        ];
    }
}
