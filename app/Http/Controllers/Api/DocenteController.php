<?php

namespace App\Http\Controllers\Api;

use App\Models\Docente;
use Illuminate\Support\Facades\Hash;

class DocenteController extends BaseCrudController
{
    protected string $modelClass = Docente::class;
    protected string $primaryKey = 'id_docente';
    protected array $with = ['universidad'];

    protected array $storeRules = [
        'nombre' => ['required', 'string', 'max:120'],
        'id_universidad' => ['required', 'integer', 'exists:universidad,id_universidad'],
        'es_jefe_carrera' => ['required', 'boolean'],
        'correo' => ['required', 'email', 'max:150'],
        'password' => ['nullable', 'string', 'min:6', 'max:100'],
    ];

    protected array $updateRules = [
        'nombre' => ['required', 'string', 'max:120'],
        'id_universidad' => ['required', 'integer', 'exists:universidad,id_universidad'],
        'es_jefe_carrera' => ['required', 'boolean'],
        'correo' => ['required', 'email', 'max:150'],
        'password' => ['nullable', 'string', 'min:6', 'max:100'],
    ];

    protected function transformData(array $data): array
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }
}
