<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class HorarioIdealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_modulo' => ['nullable', 'integer', 'exists:modulo,id_modulo'],
        ];
    }
}
