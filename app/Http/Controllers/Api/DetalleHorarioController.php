<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\DetalleHorarioRequest;
use App\Models\DetalleHorario;

class DetalleHorarioController extends BaseCrudController
{
    protected string $modelClass = DetalleHorario::class;
    protected string $primaryKey = 'id_detalle';
    protected array $with = ['horario', 'materia', 'docente', 'bloque'];
    protected ?string $storeRequestClass = DetalleHorarioRequest::class;
}
