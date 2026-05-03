<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InscripcionRequest extends FormRequest
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
            'id_dm' => ['nullable', 'integer', 'exists:docente_materia,id_dm'],
            'id_materia' => ['nullable', 'integer', 'exists:materia,id_materia'],
            'estado' => ['nullable', 'in:bloqueada,pendiente,cursando,aprobada,reprobada,incompleta'],
            'intentos' => ['nullable', 'integer', 'min:1', 'max:20'],
            'fecha_inscripcion' => ['nullable', 'date'],
        ];
    }
}
