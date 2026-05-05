<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BloqueHorarioRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre' => ['required','string','max:120'],
            'dia_semana' => ['required','integer','between:1,7'],
            'hora_inicio' => ['required','date_format:H:i:s'],
            'hora_fin' => ['required','date_format:H:i:s','after:hora_inicio'],
            'orden' => ['nullable','integer','min:1','max:20'],
        ];
    }
}
