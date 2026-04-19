<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Docente extends Model
{
    protected $table = 'docente';
    protected $primaryKey = 'id_docente';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'id_universidad',
        'es_jefe_carrera',
        'correo',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function universidad(): BelongsTo
    {
        return $this->belongsTo(Universidad::class, 'id_universidad', 'id_universidad');
    }

    public function docenteMaterias(): HasMany
    {
        return $this->hasMany(DocenteMateria::class, 'id_docente', 'id_docente');
    }

    public function horasLibresDocentes(): HasMany
    {
        return $this->hasMany(HorasLibresDoc::class, 'id_docente', 'id_docente');
    }
}
