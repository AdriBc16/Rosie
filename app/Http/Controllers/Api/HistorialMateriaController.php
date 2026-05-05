<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\HistorialMateriaRequest;
use App\Models\HistorialMateria;

class HistorialMateriaController extends BaseCrudController
{
    protected string $modelClass = HistorialMateria::class;
    protected string $primaryKey = 'id_historial';
    protected array $with = ['estudiante', 'materia'];
    protected ?string $storeRequestClass = HistorialMateriaRequest::class;
}
