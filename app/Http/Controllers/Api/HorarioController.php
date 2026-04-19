<?php

namespace App\Http\Controllers\Api;

use App\Models\Horario;

class HorarioController extends BaseCrudController
{
    protected string $modelClass = Horario::class;
    protected string $primaryKey = 'id_horario';

    protected array $storeRules = [
        'nombre' => ['required', 'string', 'max:120'],
        'hora_inicio' => ['required', 'date_format:H:i:s'],
        'hora_fin' => ['required', 'date_format:H:i:s', 'after:hora_inicio'],
    ];
}
