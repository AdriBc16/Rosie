<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Docente extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'docentes';
    protected $primaryKey = 'id_docente';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'es_jefe_carrera',
        'correo',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password'        => 'hashed',
            'es_jefe_carrera' => 'boolean',
        ];
    }

    public function docenteMaterias(): HasMany
    {
        return $this->hasMany(DocenteMateria::class, 'id_docente', 'id_docente');
    }

    public function disponibilidades(): HasMany
    {
        return $this->hasMany(DisponibilidadDocente::class, 'id_docente', 'id_docente');
    }

    public function detalleHorarios(): HasMany
    {
        return $this->hasMany(DetalleHorario::class, 'id_docente', 'id_docente');
    }
}
