<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\DisponibilidadDocenteRequest;
use App\Models\DisponibilidadDocente;

class DisponibilidadDocenteController extends BaseCrudController
{
    protected string $modelClass = DisponibilidadDocente::class;
    protected string $primaryKey = 'id_disponibilidad';
    protected array $with = ['docente'];
    protected ?string $storeRequestClass = DisponibilidadDocenteRequest::class;
}
