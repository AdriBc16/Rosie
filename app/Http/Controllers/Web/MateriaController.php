<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\DocenteMateria;
use App\Models\DisponibilidadDocente;
use App\Models\Materia;
use App\Models\Modulo;
use App\Models\Prerequisito;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MateriaController extends Controller
{
    public function headCreateMateria(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:120',
            'horas_semanales' => 'nullable|integer|min:1|max:20',
            'año_academico' => 'nullable|integer|min:1|max:10',
            'prerrequisitos' => 'nullable|array',
            'prerrequisitos.*' => 'integer|exists:materias,id_materia',
        ]);

        $materia = DB::transaction(function () use ($data) {
            $created = Materia::query()->create([
                'nombre' => trim($data['nombre']),
                'horas_semanales' => $data['horas_semanales'] ?? 1,
                'año_academico' => $data['año_academico'] ?? 1,
            ]);

            foreach (($data['prerrequisitos'] ?? []) as $idPrerequisito) {
                Prerequisito::query()->create([
                    'id_materia' => $created->id_materia,
                    'id_materia_prerrequisito' => (int) $idPrerequisito,
                    'descripcion' => null,
                ]);
            }

            return $created;
        });

        return response()->json(['message' => 'Materia creada.', 'data' => $materia], 201);
    }

    public function headAssignMateria(Request $request): JsonResponse
    {
        $request->validate([
            'id_docente' => 'required|integer|exists:docentes,id_docente',
            'id_materia' => 'required|integer|exists:materias,id_materia',
            'id_bloque' => 'required|integer|exists:bloques_horarios,id_bloque',
            'id_aula' => 'required|integer|exists:aulas,id_aula',
        ]);

        $idModulo = $request->id_modulo ?? $this->getActiveModuloId();

        if (!$idModulo) {
            return response()->json(['message' => 'No hay un modulo activo o disponible para esta fecha.'], 422);
        }

        $isAvailable = DisponibilidadDocente::where('id_docente', $request->id_docente)
            ->where('id_bloque', $request->id_bloque)
            ->exists();

        if (!$isAvailable) {
            $docente = Docente::find($request->id_docente);
            return response()->json([
                'message' => "El docente {$docente->nombre} {$docente->apellido} no esta disponible en el bloque seleccionado."
            ], 422);
        }

        $clashDocente = DocenteMateria::where('id_docente', $request->id_docente)
            ->where('id_modulo', $idModulo)
            ->where('id_bloque', $request->id_bloque)
            ->exists();

        if ($clashDocente) {
            return response()->json(['message' => 'El docente ya tiene una asignacion en este bloque y periodo.'], 422);
        }

        $clashAula = DocenteMateria::where('id_aula', $request->id_aula)
            ->where('id_modulo', $idModulo)
            ->where('id_bloque', $request->id_bloque)
            ->exists();

        if ($clashAula) {
            return response()->json(['message' => 'El aula seleccionada ya esta ocupada en este bloque y periodo.'], 422);
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
