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
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }
}
