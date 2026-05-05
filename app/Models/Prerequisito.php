<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prerequisito extends Model
{
    protected $table = 'prerrequisitos';
    protected $primaryKey = 'id_prerrequisito';
    public $timestamps = false;

    protected $fillable = [
        'id_materia',
        'id_materia_prerrequisito',
        'descripcion',
    ];

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function materiaPrerequisito(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_materia_prerrequisito', 'id_materia');
    }
}
