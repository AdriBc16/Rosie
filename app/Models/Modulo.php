<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modulo extends Model
{
    protected $table = 'modulo';
    protected $primaryKey = 'id_modulo';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_final',
        'id_semestre',
    ];

    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class, 'id_semestre', 'id_semestre');
    }

    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class, 'id_modulo', 'id_modulo');
    }

    public function docenteMaterias(): HasMany
    {
        return $this->hasMany(DocenteMateria::class, 'id_modulo', 'id_modulo');
    }

    public function horasLibresDocentes(): HasMany
    {
        return $this->hasMany(HorasLibresDoc::class, 'id_modulo', 'id_modulo');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_modulo', 'id_modulo');
    }
}
