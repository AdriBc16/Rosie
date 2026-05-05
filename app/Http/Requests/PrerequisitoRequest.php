<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrerequisitoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_materia' => ['required','integer','exists:materia,id_materia'],
            'id_materia_prerrequisito' => ['required','integer','different:id_materia','exists:materia,id_materia'],
            'descripcion' => ['nullable','string','max:255'],
        ];
    }
}
