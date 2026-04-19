<?php

namespace App\Http\Controllers\Api;

use App\Models\Semestre;

class SemestreController extends BaseCrudController
{
    protected string $modelClass = Semestre::class;
    protected string $primaryKey = 'id_semestre';

    protected array $storeRules = [
        'nombre' => ['required', 'string', 'max:120'],
        'fecha_inicio' => ['required', 'date'],
        'fecha_final' => ['required', 'date', 'after_or_equal:fecha_inicio'],
    ];
}
