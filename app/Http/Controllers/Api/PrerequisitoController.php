<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PrerequisitoRequest;
use App\Models\Prerequisito;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrerequisitoController extends BaseCrudController
{
    protected string $modelClass = Prerequisito::class;
    protected string $primaryKey = 'id_prerrequisito';
    protected array $with = ['materia', 'materiaPrerequisito'];
    protected ?string $storeRequestClass = PrerequisitoRequest::class;
}
