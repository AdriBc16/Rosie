<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    protected $table = 'carrera';
    protected $primaryKey = 'id_carrera';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'id_universidad',
    ];

    public function universidad(): BelongsTo
    {
        return $this->belongsTo(Universidad::class, 'id_universidad', 'id_universidad');
    }

    public function materias(): HasMany
    {
        return $this->hasMany(Materia::class, 'id_carrera', 'id_carrera');
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(
            Docente::class,
            'carrera_docente',
            'id_carrera',
            'id_docente',
            'id_carrera',
            'id_docente'
        );
    }
}
