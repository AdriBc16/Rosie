<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\Modulo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InscripcionController extends Controller
{
    public function headEnrollStudent(Request $request): JsonResponse
    {
        $request->validate([
            'id_estudiante' => 'required|integer|exists:estudiantes,id_estudiante',
            'id_materia'    => 'required|integer|exists:materias,id_materia',
        ]);

        $idModulo = $request->id_modulo ?? $this->getActiveModuloId();

        if (!$idModulo) {
            return response()->json(['message' => 'No hay un módulo activo para realizar la inscripción.'], 422);
        }

        // 1. Verificar Prerrequisitos
        $prerrequisitos = \App\Models\Prerequisito::where('id_materia', $request->id_materia)->get();
        foreach ($prerrequisitos as $p) {
            $aprobada = Inscripcion::where('id_estudiante', $request->id_estudiante)
                ->where('id_materia', $p->id_materia_prerrequisito)
                ->where('estado', 'aprobada')
                ->exists();
            
            $convalidada = \App\Models\HistorialMateria::where('id_estudiante', $request->id_estudiante)
                ->where('id_materia', $p->id_materia_prerrequisito)
                ->where('convalidada', true)
                ->exists();

            if (!$aprobada && !$convalidada) {
                $materiaReq = Materia::find($p->id_materia_prerrequisito);
                return response()->json([
                    'message' => "El estudiante no cumple con los prerrequisitos. Debe aprobar primero: {$materiaReq->nombre}."
                ], 422);
            }
        }

        // 2. Verificar si ya está inscrito
        $exists = Inscripcion::where('id_estudiante', $request->id_estudiante)
            ->where('id_materia', $request->id_materia)
            ->where('id_modulo', $idModulo)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'El estudiante ya está inscrito en esta materia para el periodo actual.'], 422);
        }

        $inscripcion = Inscripcion::create([
            'id_estudiante' => $request->id_estudiante,
            'id_materia'    => $request->id_materia,
            'id_modulo'     => $idModulo,
            'estado'        => 'cursando',
            'fecha_inscripcion' => now(),
            'intentos'      => 1
        ]);

        return response()->json(['message' => 'Estudiante inscrito correctamente.', 'data' => $inscripcion], 201);
    }

    private function getActiveModuloId(): ?int
    {
        $modulo = Modulo::where('fecha_inicio', '<=', now())
            ->where('fecha_final', '>=', now())
            ->first();

        if ($modulo) return $modulo->id_modulo;

        $modulo = Modulo::where('fecha_inicio', '>', now())
            ->orderBy('fecha_inicio', 'asc')
            ->first();

        if ($modulo) return $modulo->id_modulo;

        $modulo = Modulo::orderBy('fecha_final', 'desc')->first();

        return $modulo?->id_modulo;
    }
}
