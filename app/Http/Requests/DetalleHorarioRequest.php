<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DetalleHorarioRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_horario' => ['required','integer','exists:horario_generado,id_horario'],
            'id_materia' => ['required','integer','exists:materia,id_materia'],
            'id_docente' => ['nullable','integer','exists:docente,id_docente'],
            'id_horario_bloque' => ['required','integer','exists:bloque_horario,id_bloque'],
        ];
    }
}
