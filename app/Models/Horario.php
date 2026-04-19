<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horario extends Model
{
    protected $table = 'horario';
    protected $primaryKey = 'id_horario';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'hora_inicio',
        'hora_fin',
    ];

    public function docenteMaterias(): HasMany
    {
        return $this->hasMany(DocenteMateria::class, 'id_horario', 'id_horario');
    }

    public function horasLibresDocentes(): HasMany
    {
        return $this->hasMany(HorasLibresDoc::class, 'id_horario', 'id_horario');
    }
}
