<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorasLibresDoc extends Model
{
    protected $table = 'horas_libres_doc';
    protected $primaryKey = 'id_hld';
    public $timestamps = false;

    protected $fillable = [
        'id_docente',
        'id_horario',
        'id_modulo',
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class, 'id_horario', 'id_horario');
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'id_modulo', 'id_modulo');
    }
}
