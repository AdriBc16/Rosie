<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HorarioRequest;
use App\Models\Horario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Horario::query();

        if ($request->filled('take')) {
            return response()->json($query->take((int) $request->integer('take'))->get());
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $record = Horario::query()
            ->where('id_horario', $id)
            ->firstOrFail();

        return response()->json($record);
    }

    public function store(HorarioRequest $request): JsonResponse
    {
        $record = Horario::query()->create($request->validated());

        return response()->json($record, 201);
    }

    public function update(HorarioRequest $request, int $id): JsonResponse
    {
        $record = Horario::query()
            ->where('id_horario', $id)
            ->firstOrFail();

        $record->update($request->validated());

        return response()->json($record);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = Horario::query()
            ->where('id_horario', $id)
            ->firstOrFail();

        $record->delete();

        return response()->json([
            'message' => 'Registro eliminado correctamente',
            'id' => $id,
        ]);
    }
}