<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SemestreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_final' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }
}