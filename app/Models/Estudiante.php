<?php

namespace App\Models;

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
            'es_traspaso' => 'boolean',
        ];
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
