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
        $query = Inscripcion::query()->with(['estudiante', 'modulo', 'docenteMateria', 'materia']);

        if ($request->filled('take')) {
            return response()->json($query->take((int) $request->integer('take'))->get());
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $record = Inscripcion::query()
            ->with(['estudiante', 'modulo', 'docenteMateria', 'materia'])
            ->where('id_inscripcion', $id)
            ->firstOrFail();

        return response()->json($record);
    }

    public function store(InscripcionRequest $request): JsonResponse
    {
        $data = $this->normalizePayload($request->validated());
        $record = Inscripcion::query()->create($data);
        $record->load(['estudiante', 'modulo', 'docenteMateria', 'materia']);

        return response()->json($record, 201);
    }

    public function update(InscripcionRequest $request, int $id): JsonResponse
    {
        $record = Inscripcion::query()
            ->where('id_inscripcion', $id)
            ->firstOrFail();

        $data = $this->normalizePayload($request->validated());
        $record->update($data);
        $record->load(['estudiante', 'modulo', 'docenteMateria', 'materia']);

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

    private function normalizePayload(array $data): array
    {
        if (!empty($data['id_dm']) && empty($data['id_materia'])) {
            $idMateria = \App\Models\DocenteMateria::query()
                ->where('id_dm', (int) $data['id_dm'])
                ->value('id_materia');

            if ($idMateria) {
                $data['id_materia'] = (int) $idMateria;
            }
        }

        if (empty($data['estado'])) {
            $data['estado'] = 'pendiente';
        }

        if (empty($data['intentos'])) {
            $data['intentos'] = 1;
        }

        if (empty($data['fecha_inscripcion'])) {
            $data['fecha_inscripcion'] = now();
        }

        return $data;
    }
}
