<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BloqueHorario extends Model
{
    protected $table = 'bloque_horario';
    protected $primaryKey = 'id_bloque';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'orden',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleHorario::class, 'id_horario_bloque', 'id_bloque');
    }
}
