<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aula extends Model
{
    protected $table = 'aulas';
    protected $primaryKey = 'id_aula';
    public $timestamps = false;

    protected $fillable = ['nombre', 'capacidad'];

    public function detalleHorarios(): HasMany
    {
        return $this->hasMany(DetalleHorario::class, 'id_aula', 'id_aula');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(DocenteMateria::class, 'id_aula', 'id_aula');
    }
}
