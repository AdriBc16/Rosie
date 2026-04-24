<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DocenteMateriaUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_materia' => ['required', 'integer', 'exists:materia,id_materia'],
            'id_docente' => ['required', 'integer', 'exists:docente,id_docente'],
            'id_horario' => ['required', 'integer', 'exists:horario,id_horario'],
            'id_modulo' => ['required', 'integer', 'exists:modulo,id_modulo'],
        ];
    }
}
