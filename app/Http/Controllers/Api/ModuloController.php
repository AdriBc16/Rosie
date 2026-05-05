<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModuloRequest;
use App\Models\Modulo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuloController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Modulo::query()->with(['semestre']);

        if ($request->filled('take')) {
            return response()->json($query->take((int) $request->integer('take'))->get());
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $record = Modulo::query()
            ->with(['semestre'])
            ->where('id_modulo', $id)
            ->firstOrFail();

        return response()->json($record);
    }

    public function store(ModuloRequest $request): JsonResponse
    {
        $record = Modulo::query()->create($request->validated());
        $record->load(['semestre']);

        return response()->json($record, 201);
    }

    public function update(ModuloRequest $request, int $id): JsonResponse
    {
        $record = Modulo::query()
            ->where('id_modulo', $id)
            ->firstOrFail();

        $record->update($request->validated());
        $record->load(['semestre']);

        return response()->json($record);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = Modulo::query()
            ->where('id_modulo', $id)
            ->firstOrFail();

        $record->delete();

        return response()->json([
            'message' => 'Registro eliminado correctamente',
            'id' => $id,
        ]);
    }
}