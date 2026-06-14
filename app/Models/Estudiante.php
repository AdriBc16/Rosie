<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Estudiante extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'estudiantes';
    protected $primaryKey = 'id_estudiante';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'correo',
        'cohorte_ingreso',
        'password',
        'es_traspaso',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password'    => 'hashed',
            'cohorte_ingreso' => 'integer',
            'es_traspaso' => 'boolean',
        ];
    }

    public function semestres(): BelongsToMany
    {
        return $this->belongsToMany(Semestre::class, 'estudiante_semestres', 'id_estudiante', 'id_semestre');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_estudiante', 'id_estudiante');
    }

    public function historialMaterias(): HasMany
    {
        return $this->hasMany(HistorialMateria::class, 'id_estudiante', 'id_estudiante');
    }

    public function horariosGenerados(): HasMany
    {
        return $this->hasMany(HorarioGenerado::class, 'id_estudiante', 'id_estudiante');
    }
}
