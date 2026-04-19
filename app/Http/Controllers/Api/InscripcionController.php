<?php

namespace App\Http\Controllers\Api;

use App\Models\Inscripcion;

class InscripcionController extends BaseCrudController
{
    protected string $modelClass = Inscripcion::class;
    protected string $primaryKey = 'id_inscripcion';
    protected array $with = ['estudiante', 'modulo', 'docenteMateria'];

    protected array $storeRules = [
        'id_estudiante' => ['required', 'integer', 'exists:estudiante,id_estudiante'],
        'id_modulo' => ['required', 'integer', 'exists:modulo,id_modulo'],
        'id_dm' => ['required', 'integer', 'exists:docente_materia,id_dm'],
    ];
}
