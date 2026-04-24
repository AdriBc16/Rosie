<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SemestreRequest;
use App\Models\Semestre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SemestreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Semestre::query();

        if ($request->filled('take')) {
            return response()->json($query->take((int) $request->integer('take'))->get());
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $record = Semestre::query()
            ->where('id_semestre', $id)
            ->firstOrFail();

        return response()->json($record);
    }

    public function store(SemestreRequest $request): JsonResponse
    {
        $record = Semestre::query()->create($request->validated());

        return response()->json($record, 201);
    }

    public function update(SemestreRequest $request, int $id): JsonResponse
    {
        $record = Semestre::query()
            ->where('id_semestre', $id)
            ->firstOrFail();

        $record->update($request->validated());

        return response()->json($record);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = Semestre::query()
            ->where('id_semestre', $id)
            ->firstOrFail();

        $record->delete();

        return response()->json([
            'message' => 'Registro eliminado correctamente',
            'id' => $id,
        ]);
    }
}