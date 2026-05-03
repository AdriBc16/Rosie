<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleHorario extends Model
{
    protected $table = 'detalle_horario';
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_horario',
        'id_materia',
        'id_docente',
        'id_horario_bloque',
    ];

    public function horario(): BelongsTo
    {
        return $this->belongsTo(HorarioGenerado::class, 'id_horario', 'id_horario');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function bloque(): BelongsTo
    {
        return $this->belongsTo(BloqueHorario::class, 'id_horario_bloque', 'id_bloque');
    }
}
