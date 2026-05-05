<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EstudianteRequest;
use App\Models\Estudiante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EstudianteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Estudiante::query()->with(['universidad', 'modulo']);

        if ($request->filled('take')) {
            return response()->json($query->take((int) $request->integer('take'))->get());
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $record = Estudiante::query()
            ->with(['universidad', 'modulo'])
            ->where('id_estudiante', $id)
            ->firstOrFail();

        return response()->json($record);
    }

    public function store(EstudianteRequest $request): JsonResponse
    {
        $data = $this->hashPasswordIfNeeded($request->validated());

        $record = Estudiante::query()->create($data);
        $record->load(['universidad', 'modulo']);

        return response()->json($record, 201);
    }

    public function update(EstudianteRequest $request, int $id): JsonResponse
    {
        $record = Estudiante::query()
            ->where('id_estudiante', $id)
            ->firstOrFail();

        $data = $this->hashPasswordIfNeeded($request->validated());

        $record->update($data);
        $record->load(['universidad', 'modulo']);

        return response()->json($record);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = Estudiante::query()
            ->where('id_estudiante', $id)
            ->firstOrFail();

        $record->delete();

        return response()->json([
            'message' => 'Registro eliminado correctamente',
            'id' => $id,
        ]);
    }

    private function hashPasswordIfNeeded(array $data): array
    {
        if (empty($data['password'])) {
            unset($data['password']);

            return $data;
        }

        $data['password'] = Hash::make($data['password']);

        return $data;
    }
}