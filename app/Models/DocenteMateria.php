<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocenteMateria extends Model
{
    protected $table = 'docente_materias';
    protected $primaryKey = 'id_dm';
    public $timestamps = false;

    protected $fillable = [
        'id_materia',
        'id_docente',
        'id_modulo',
        'id_bloque',
        'id_aula',
    ];

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'id_modulo', 'id_modulo');
    }

    public function bloque(): BelongsTo
    {
        return $this->belongsTo(BloqueHorario::class, 'id_bloque', 'id_bloque');
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class, 'id_aula', 'id_aula');
    }

    /**
     * Inscripciones para esta asignación (misma materia Y mismo módulo).
     * Usamos hasMany por id_materia y filtramos por id_modulo en withCount
     * mediante whereColumn para que sea eficiente a nivel SQL.
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_materia', 'id_materia');
    }
}
