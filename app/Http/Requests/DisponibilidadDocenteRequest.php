<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DisponibilidadDocenteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_docente' => ['required','integer','exists:docente,id_docente'],
            'dia_semana' => ['required','integer','between:1,7'],
            'hora_inicio' => ['required','date_format:H:i:s'],
            'hora_fin' => ['required','date_format:H:i:s','after:hora_inicio'],
        ];
    }
}
