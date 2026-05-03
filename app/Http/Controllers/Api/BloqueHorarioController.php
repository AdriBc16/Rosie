<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BloqueHorarioRequest;
use App\Models\BloqueHorario;

class BloqueHorarioController extends BaseCrudController
{
    protected string $modelClass = BloqueHorario::class;
    protected string $primaryKey = 'id_bloque';
    protected ?string $storeRequestClass = BloqueHorarioRequest::class;
}
