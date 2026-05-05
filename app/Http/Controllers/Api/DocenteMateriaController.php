<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocenteMateriaRequest;
use App\Models\DocenteMateria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocenteMateriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DocenteMateria::query()->with(['materia', 'docente', 'horario', 'modulo']);

        if ($request->filled('take')) {
            return response()->json($query->take((int) $request->integer('take'))->get());
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $record = DocenteMateria::query()
            ->with(['materia', 'docente', 'horario', 'modulo'])
            ->where('id_dm', $id)
            ->firstOrFail();

        return response()->json($record);
    }

    public function store(DocenteMateriaRequest $request): JsonResponse
    {
        $record = DocenteMateria::query()->create($request->validated());
        $record->load(['materia', 'docente', 'horario', 'modulo']);

        return response()->json($record, 201);
    }

    public function update(DocenteMateriaRequest $request, int $id): JsonResponse
    {
        $record = DocenteMateria::query()
            ->where('id_dm', $id)
            ->firstOrFail();

        $record->update($request->validated());
        $record->load(['materia', 'docente', 'horario', 'modulo']);

        return response()->json($record);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = DocenteMateria::query()
            ->where('id_dm', $id)
            ->firstOrFail();

        $record->delete();

        return response()->json([
            'message' => 'Registro eliminado correctamente',
            'id' => $id,
        ]);
    }
}