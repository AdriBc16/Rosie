<?php

namespace App\Http\Controllers\Api;

use App\Models\DocenteMateria;

class DocenteMateriaController extends BaseCrudController
{
    protected string $modelClass = DocenteMateria::class;
    protected string $primaryKey = 'id_dm';
    protected array $with = ['materia', 'docente', 'horario', 'modulo'];

    protected array $storeRules = [
        'id_materia' => ['required', 'integer', 'exists:materia,id_materia'],
        'id_docente' => ['required', 'integer', 'exists:docente,id_docente'],
        'id_horario' => ['required', 'integer', 'exists:horario,id_horario'],
        'id_modulo' => ['required', 'integer', 'exists:modulo,id_modulo'],
    ];
}
