<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisponibilidadDocente extends Model
{
    protected $table = 'disponibilidad_docente';
    protected $primaryKey = 'id_disponibilidad';
    public $timestamps = false;

    protected $fillable = [
        'id_docente',
        'id_bloque',
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function bloque(): BelongsTo
    {
        return $this->belongsTo(BloqueHorario::class, 'id_bloque', 'id_bloque');
    }
}
