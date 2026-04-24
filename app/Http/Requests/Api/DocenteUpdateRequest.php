<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocenteUpdateRequest extends FormRequest
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
            'id_universidad' => ['required', 'integer', 'exists:universidad,id_universidad'],
            'es_jefe_carrera' => ['required', 'boolean'],
            'correo' => ['required', 'email', 'max:150', Rule::unique('docente', 'correo')->ignore($id, 'id_docente')],
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('correo')) {
            $this->merge(['correo' => mb_strtolower(trim((string) $this->input('correo')))]);
        }
    }
}
