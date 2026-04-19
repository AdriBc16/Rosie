<?php

namespace App\Http\Controllers\Api;

use App\Models\Modulo;

class ModuloController extends BaseCrudController
{
    protected string $modelClass = Modulo::class;
    protected string $primaryKey = 'id_modulo';
    protected array $with = ['semestre'];

    protected array $storeRules = [
        'nombre' => ['required', 'string', 'max:120'],
        'fecha_inicio' => ['required', 'date'],
        'fecha_final' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        'id_semestre' => ['required', 'integer', 'exists:semestre,id_semestre'],
    ];
}
