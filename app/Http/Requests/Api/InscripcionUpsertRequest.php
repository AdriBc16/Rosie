<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class InscripcionUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_estudiante' => ['required', 'integer', 'exists:estudiante,id_estudiante'],
            'id_modulo' => ['required', 'integer', 'exists:modulo,id_modulo'],
            'id_dm' => ['required', 'integer', 'exists:docente_materia,id_dm'],
        ];
    }
}
