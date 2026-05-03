<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarreraRequest;
use App\Models\Carrera;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarreraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Carrera::query()->with(['universidad']);

        if ($request->filled('take')) {
            return response()->json($query->take((int) $request->integer('take'))->get());
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $record = Carrera::query()
            ->with(['universidad'])
            ->where('id_carrera', $id)
            ->firstOrFail();

        return response()->json($record);
    }

    public function store(CarreraRequest $request): JsonResponse
    {
        $record = Carrera::query()->create($request->validated());
        $record->load(['universidad']);

        return response()->json($record, 201);
    }

    public function update(CarreraRequest $request, int $id): JsonResponse
    {
        $record = Carrera::query()
            ->where('id_carrera', $id)
            ->firstOrFail();

        $record->update($request->validated());
        $record->load(['universidad']);

        return response()->json($record);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = Carrera::query()
            ->where('id_carrera', $id)
            ->firstOrFail();

        $record->delete();

        return response()->json([
            'message' => 'Registro eliminado correctamente',
            'id' => $id,
        ]);
    }
}
