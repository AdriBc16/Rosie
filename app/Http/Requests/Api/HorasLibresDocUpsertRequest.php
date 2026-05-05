<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class HorasLibresDocUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_docente' => ['required', 'integer', 'exists:docente,id_docente'],
            'id_horario' => ['required', 'integer', 'exists:horario,id_horario'],
            'id_modulo' => ['required', 'integer', 'exists:modulo,id_modulo'],
        ];
    }
}
