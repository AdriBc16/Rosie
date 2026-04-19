<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Universidad extends Model
{
    protected $table = 'universidad';
    protected $primaryKey = 'id_universidad';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
    ];

    public function docentes(): HasMany
    {
        return $this->hasMany(Docente::class, 'id_universidad', 'id_universidad');
    }

    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class, 'id_universidad', 'id_universidad');
    }
}
