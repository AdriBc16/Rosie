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
        $query = Docente::query()->with(['universidad']);

        if ($request->filled('take')) {
            return response()->json($query->take((int) $request->integer('take'))->get());
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $record = Docente::query()
            ->with(['universidad'])
            ->where('id_docente', $id)
            ->firstOrFail();

        return response()->json($record);
    }

    public function store(DocenteRequest $request): JsonResponse
    {
        $data = $this->hashPasswordIfNeeded($request->validated());

        $record = Docente::query()->create($data);
        $record->load(['universidad']);

        return response()->json($record, 201);
    }

    public function update(DocenteRequest $request, int $id): JsonResponse
    {
        $record = Docente::query()
            ->where('id_docente', $id)
            ->firstOrFail();

        $data = $this->hashPasswordIfNeeded($request->validated());

        $record->update($data);
        $record->load(['universidad']);

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