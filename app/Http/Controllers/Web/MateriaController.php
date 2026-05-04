<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Materia;
use App\Models\Docente;
use App\Models\DocenteMateria;
use App\Models\DisponibilidadDocente;
use App\Models\Modulo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
    public function headCreateMateria(Request $request): JsonResponse
    {
        $request->validate(['nombre' => 'required|string|max:120']);

        $materia = Materia::query()->create([
            'nombre'          => trim($request->nombre),
            'horas_semanales' => $request->horas_semanales ?? 1,
            'año_academico'   => $request->año_academico ?? 1,
        ]);

        return response()->json(['message' => 'Materia creada.', 'data' => $materia], 201);
    }

    public function headAssignMateria(Request $request): JsonResponse
    {
        $request->validate([
            'id_docente' => 'required|integer|exists:docentes,id_docente',
            'id_materia' => 'required|integer|exists:materias,id_materia',
            'id_bloque'  => 'required|integer|exists:bloques_horarios,id_bloque',
            'id_aula'    => 'required|integer|exists:aulas,id_aula',
        ]);

        $idModulo = $request->id_modulo ?? $this->getActiveModuloId();

        if (!$idModulo) {
            return response()->json(['message' => 'No hay un módulo activo o disponible para esta fecha.'], 422);
        }

        // 1. Validar disponibilidad del docente
        $isAvailable = DisponibilidadDocente::where('id_docente', $request->id_docente)
            ->where('id_bloque', $request->id_bloque)
            ->exists();

        if (!$isAvailable) {
            $docente = Docente::find($request->id_docente);
            return response()->json([
                'message' => "El docente {$docente->nombre} {$docente->apellido} no está disponible en el bloque seleccionado."
            ], 422);
        }

        // 2. Verificar colisión del docente
        $clashDocente = DocenteMateria::where('id_docente', $request->id_docente)
            ->where('id_modulo', $idModulo)
            ->where('id_bloque', $request->id_bloque)
            ->exists();
        
        if ($clashDocente) {
            return response()->json(['message' => 'El docente ya tiene una asignación en este bloque y periodo.'], 422);
        }

        // 3. Verificar colisión de aula
        $clashAula = DocenteMateria::where('id_aula', $request->id_aula)
            ->where('id_modulo', $idModulo)
            ->where('id_bloque', $request->id_bloque)
            ->exists();
            
        if ($clashAula) {
            return response()->json(['message' => 'El aula seleccionada ya está ocupada en este bloque y periodo.'], 422);
        }

        $assignment = DocenteMateria::create(array_merge($request->all(), ['id_modulo' => $idModulo]));

        return response()->json(['message' => 'Materia asignada correctamente cumpliendo todas las restricciones.', 'data' => $assignment], 201);
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
