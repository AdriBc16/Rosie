<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semestre extends Model
{
    protected $table = 'semestres';
    protected $primaryKey = 'id_semestre';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_final',
    ];

    public function modulos(): HasMany
    {
        return $this->hasMany(Modulo::class, 'id_semestre', 'id_semestre');
    }
}
