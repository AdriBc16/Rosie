<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Docente extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'docente';
    protected $primaryKey = 'id_docente';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'id_universidad',
        'id_carrera',
        'es_jefe_carrera',
        'descripcion',
        'correo',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

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

    public function carreras(): BelongsToMany
    {
        return $this->belongsToMany(
            Carrera::class,
            'carrera_docente',
            'id_docente',
            'id_carrera',
            'id_docente',
            'id_carrera'
        );
    }
}
