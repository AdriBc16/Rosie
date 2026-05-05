<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inscripcion extends Model
{
    protected $table = 'inscripciones';
    protected $primaryKey = 'id_inscripcion';
    public $timestamps = false;

    protected $fillable = [
        'id_estudiante',
        'id_modulo',
        'id_materia',
        'estado',
        'intentos',
        'fecha_inscripcion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inscripcion' => 'datetime',
            'intentos'          => 'integer',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante', 'id_estudiante');
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'id_modulo', 'id_modulo');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }
}
