<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\HorarioGeneradoRequest;
use App\Models\HorarioGenerado;

class HorarioGeneradoController extends BaseCrudController
{
    protected string $modelClass = HorarioGenerado::class;
    protected string $primaryKey = 'id_horario';
    protected array $with = ['estudiante', 'modulo', 'detalles'];
    protected ?string $storeRequestClass = HorarioGeneradoRequest::class;
}
