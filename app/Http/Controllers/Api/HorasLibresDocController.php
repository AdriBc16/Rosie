<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HorasLibresDocRequest;
use App\Models\HorasLibresDoc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HorasLibresDocController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = HorasLibresDoc::query()->with(['docente', 'horario', 'modulo']);

        if ($request->filled('take')) {
            return response()->json($query->take((int) $request->integer('take'))->get());
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $record = HorasLibresDoc::query()
            ->with(['docente', 'horario', 'modulo'])
            ->where('id_hld', $id)
            ->firstOrFail();

        return response()->json($record);
    }

    public function store(HorasLibresDocRequest $request): JsonResponse
    {
        $record = HorasLibresDoc::query()->create($request->validated());
        $record->load(['docente', 'horario', 'modulo']);

        return response()->json($record, 201);
    }

    public function update(HorasLibresDocRequest $request, int $id): JsonResponse
    {
        $record = HorasLibresDoc::query()
            ->where('id_hld', $id)
            ->firstOrFail();

        $record->update($request->validated());
        $record->load(['docente', 'horario', 'modulo']);

        return response()->json($record);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = HorasLibresDoc::query()
            ->where('id_hld', $id)
            ->firstOrFail();

        $record->delete();

        return response()->json([
            'message' => 'Registro eliminado correctamente',
            'id' => $id,
        ]);
    }
}