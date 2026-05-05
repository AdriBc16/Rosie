<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleHorario extends Model
{
    protected $table = 'detalle_horarios';
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_horario',
        'id_materia',
        'id_docente',
        'id_bloque',
        'id_aula',
    ];

    public function horarioGenerado(): BelongsTo
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
        return $this->belongsTo(BloqueHorario::class, 'id_bloque', 'id_bloque');
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class, 'id_aula', 'id_aula');
    }
}
