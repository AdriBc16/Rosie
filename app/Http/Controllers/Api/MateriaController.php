<?php

namespace App\Http\Controllers\Api;

use App\Models\Materia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MateriaController extends BaseCrudController
{
    protected string $modelClass = Materia::class;
    protected string $primaryKey = 'id_materia';

    protected array $storeRules = [
        'nombre' => ['required', 'string', 'max:120'],
    ];

    public function ingesta(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'materias' => ['required', 'array', 'min:1'],
            'materias.*.nombre' => ['required', 'string', 'max:120'],
        ]);

        $materias = collect($payload['materias'])
            ->map(function (array $item) {
                return Materia::updateOrCreate(
                    ['nombre' => $item['nombre']],
                    ['nombre' => $item['nombre']]
                );
            })
            ->values();

        return response()->json([
            'message' => 'Materias procesadas correctamente',
            'total' => $materias->count(),
            'data' => $materias,
        ], 201);
    }
}
