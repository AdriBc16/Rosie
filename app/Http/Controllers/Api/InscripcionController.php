<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InscripcionRequest;
use App\Models\Inscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InscripcionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Inscripcion::query()->with(['estudiante', 'modulo', 'docenteMateria']);

        if ($request->filled('take')) {
            return response()->json($query->take((int) $request->integer('take'))->get());
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $record = Inscripcion::query()
            ->with(['estudiante', 'modulo', 'docenteMateria'])
            ->where('id_inscripcion', $id)
            ->firstOrFail();

        return response()->json($record);
    }

    public function store(InscripcionRequest $request): JsonResponse
    {
        $record = Inscripcion::query()->create($request->validated());
        $record->load(['estudiante', 'modulo', 'docenteMateria']);

        return response()->json($record, 201);
    }

    public function update(InscripcionRequest $request, int $id): JsonResponse
    {
        $record = Inscripcion::query()
            ->where('id_inscripcion', $id)
            ->firstOrFail();

        $record->update($request->validated());
        $record->load(['estudiante', 'modulo', 'docenteMateria']);

        return response()->json($record);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = Inscripcion::query()
            ->where('id_inscripcion', $id)
            ->firstOrFail();

        $record->delete();

        return response()->json([
            'message' => 'Registro eliminado correctamente',
            'id' => $id,
        ]);
    }
}