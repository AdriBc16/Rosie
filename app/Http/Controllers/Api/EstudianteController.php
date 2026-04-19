<?php

namespace App\Http\Controllers\Api;

use App\Models\Estudiante;
use Illuminate\Support\Facades\Hash;

class EstudianteController extends BaseCrudController
{
    protected string $modelClass = Estudiante::class;
    protected string $primaryKey = 'id_estudiante';
    protected array $with = ['universidad', 'modulo'];

    protected array $storeRules = [
        'nombre' => ['required', 'string', 'max:120'],
        'id_universidad' => ['required', 'integer', 'exists:universidad,id_universidad'],
        'correo' => ['required', 'email', 'max:150'],
        'id_modulo' => ['required', 'integer', 'exists:modulo,id_modulo'],
        'password' => ['nullable', 'string', 'min:6', 'max:100'],
    ];

    protected array $updateRules = [
        'nombre' => ['required', 'string', 'max:120'],
        'id_universidad' => ['required', 'integer', 'exists:universidad,id_universidad'],
        'correo' => ['required', 'email', 'max:150'],
        'id_modulo' => ['required', 'integer', 'exists:modulo,id_modulo'],
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
