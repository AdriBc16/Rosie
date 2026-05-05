<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HorarioGeneradoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_estudiante' => ['required','integer','exists:estudiante,id_estudiante'],
            'id_modulo' => ['required','integer','exists:modulo,id_modulo'],
            'estado' => ['nullable','in:borrador,confirmado'],
            'fecha_generacion' => ['nullable','date'],
        ];
    }
}
