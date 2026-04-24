<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class HeadCreateMateriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3', 'max:120', 'unique:materia,nombre'],
        ];
    }
}
