<?php

namespace App\Http\Controllers\Api;

use App\Models\HorasLibresDoc;

class HorasLibresDocController extends BaseCrudController
{
    protected string $modelClass = HorasLibresDoc::class;
    protected string $primaryKey = 'id_hld';
    protected array $with = ['docente', 'horario', 'modulo'];

    protected array $storeRules = [
        'id_docente' => ['required', 'integer', 'exists:docente,id_docente'],
        'id_horario' => ['required', 'integer', 'exists:horario,id_horario'],
        'id_modulo' => ['required', 'integer', 'exists:modulo,id_modulo'],
    ];
}
