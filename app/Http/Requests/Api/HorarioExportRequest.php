<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class HorarioExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'formato' => ['nullable', 'in:json,csv'],
            'id_modulo' => ['nullable', 'integer', 'exists:modulo,id_modulo'],
        ];
    }
}
