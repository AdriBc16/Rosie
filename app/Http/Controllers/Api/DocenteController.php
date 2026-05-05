<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocenteRequest;
use App\Models\Docente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DocenteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Docente::query()->with(['universidad', 'carreras']);

        if ($request->filled('take')) {
            return response()->json($query->take((int) $request->integer('take'))->get());
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $record = Docente::query()
            ->with(['universidad', 'carreras'])
            ->where('id_docente', $id)
            ->firstOrFail();

        return response()->json($record);
    }

    public function store(DocenteRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $carreraIds = $payload['carrera_ids'] ?? [];
        unset($payload['carrera_ids']);

        $data = $this->hashPasswordIfNeeded($payload);

        $record = Docente::query()->create($data);
        $record->carreras()->sync($carreraIds);
        $record->load(['universidad', 'carreras']);

        return response()->json($record, 201);
    }

    public function update(DocenteRequest $request, int $id): JsonResponse
    {
        $record = Docente::query()
            ->where('id_docente', $id)
            ->firstOrFail();

        $payload = $request->validated();
        $carreraIds = $payload['carrera_ids'] ?? null;
        unset($payload['carrera_ids']);

        $data = $this->hashPasswordIfNeeded($payload);

        $record->update($data);
        if (is_array($carreraIds)) {
            $record->carreras()->sync($carreraIds);
        }
        $record->load(['universidad', 'carreras']);

        return response()->json($record);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = Docente::query()
            ->where('id_docente', $id)
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
