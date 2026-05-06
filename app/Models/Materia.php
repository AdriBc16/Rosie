<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materia extends Model
{
    protected $table = 'materias';
    protected $primaryKey = 'id_materia';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'horas_semanales',
        'año_academico',
        'semestre_academico',
    ];

    public function docenteMaterias(): HasMany
    {
        return $this->hasMany(DocenteMateria::class, 'id_materia', 'id_materia');
    }

    public function prerrequisitos(): HasMany
    {
        return $this->hasMany(Prerequisito::class, 'id_materia', 'id_materia');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_materia', 'id_materia');
    }

    public function historialMaterias(): HasMany
    {
        return $this->hasMany(HistorialMateria::class, 'id_materia', 'id_materia');
    }
}
