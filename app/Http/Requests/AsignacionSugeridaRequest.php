<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignacionSugeridaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_carrera' => ['required', 'integer', 'exists:carrera,id_carrera'],
            'id_semestre' => ['required', 'integer', 'exists:semestre,id_semestre'],
            'id_modulo' => ['required', 'integer', 'exists:modulo,id_modulo'],
            'id_materia' => ['required', 'integer', 'exists:materia,id_materia'],
        ];
    }
}
