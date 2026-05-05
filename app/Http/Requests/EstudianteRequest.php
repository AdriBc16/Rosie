<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'nombre' => ['required', 'string', 'max:120'],
            'apellido' => ['nullable', 'string', 'max:120'],
            'id_universidad' => ['required', 'integer', 'exists:universidad,id_universidad'],
            'correo' => ['required', 'email', 'max:150', Rule::unique('estudiante', 'correo')->ignore($id, 'id_estudiante')],
            'id_modulo' => ['required', 'integer', 'exists:modulo,id_modulo'],
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
            'es_traspaso' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('correo')) {
            $this->merge(['correo' => mb_strtolower(trim((string) $this->input('correo')))]);
        }
    }
}
