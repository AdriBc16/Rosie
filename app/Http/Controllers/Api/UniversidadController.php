<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UniversidadRequest;
use App\Models\Universidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UniversidadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Universidad::query();

        if ($request->filled('take')) {
            return response()->json($query->take((int) $request->integer('take'))->get());
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $record = Universidad::query()
            ->where('id_universidad', $id)
            ->firstOrFail();

        return response()->json($record);
    }

    public function store(UniversidadRequest $request): JsonResponse
    {
        $record = Universidad::query()->create($request->validated());

        return response()->json($record, 201);
    }

    public function update(UniversidadRequest $request, int $id): JsonResponse
    {
        $record = Universidad::query()
            ->where('id_universidad', $id)
            ->firstOrFail();

        $record->update($request->validated());

        return response()->json($record);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = Universidad::query()
            ->where('id_universidad', $id)
            ->firstOrFail();

        $record->delete();

        return response()->json([
            'message' => 'Registro eliminado correctamente',
            'id' => $id,
        ]);
    }
}