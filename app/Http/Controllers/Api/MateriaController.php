<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MateriaIngestaRequest;
use App\Http\Requests\MateriaRequest;
use App\Models\Materia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Materia::query();

        if ($request->filled('take')) {
            return response()->json($query->take((int) $request->integer('take'))->get());
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $record = Materia::query()
            ->where('id_materia', $id)
            ->firstOrFail();

        return response()->json($record);
    }

    public function store(MateriaRequest $request): JsonResponse
    {
        $record = Materia::query()->create($request->validated());

        return response()->json($record, 201);
    }

    public function update(MateriaRequest $request, int $id): JsonResponse
    {
        $record = Materia::query()
            ->where('id_materia', $id)
            ->firstOrFail();

        $record->update($request->validated());

        return response()->json($record);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = Materia::query()
            ->where('id_materia', $id)
            ->firstOrFail();

        $record->delete();

        return response()->json([
            'message' => 'Registro eliminado correctamente',
            'id' => $id,
        ]);
    }

    public function ingesta(MateriaIngestaRequest $request): JsonResponse
    {
        $payload = $request->validated();

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