<?php

namespace App\Http\Controllers\Api;

use App\Models\Universidad;

class UniversidadController extends BaseCrudController
{
    protected string $modelClass = Universidad::class;
    protected string $primaryKey = 'id_universidad';

    protected array $storeRules = [
        'nombre' => ['required', 'string', 'max:120'],
    ];
}
