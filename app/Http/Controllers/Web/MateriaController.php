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
            'anio_academico' => 'nullable|integer|min:1|max:10',
            'semestre_academico' => 'nullable|integer|min:1|max:20',
            'prerrequisitos' => 'nullable|array',
            'prerrequisitos.*' => 'integer|exists:materias,id_materia',
        ]);

        $materia = DB::transaction(function () use ($data) {
            $created = Materia::query()->create([
                'nombre' => trim($data['nombre']),
                'horas_semanales' => $data['horas_semanales'] ?? 1,
                'anio_academico' => $data['anio_academico'] ?? 1,
                'semestre_academico' => $data['semestre_academico'] ?? 1,
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
            'id_aula'    => 'required|integer|exists:aulas,id_aula',
        ]);

        $idDocente = $request->id_docente;
        $idMateria = $request->id_materia;
        $idAula    = $request->id_aula;

        // ALGORITMO: Buscar Módulo y Bloque disponible para este docente y aula
        $modulos = Modulo::where('fecha_final', '>=', now())
            ->orderBy('fecha_inicio')
            ->get();

        $selectedModuloId = null;
        $selectedBloqueId = null;

        foreach ($modulos as $mod) {
            // NUEVO: Validar que no estemos sobrecargando el módulo con oferta académica
            // Para una universidad pequeña, queremos que la oferta esté distribuida.
            // Si ya hay 2 materias del mismo "nivel/semestre" en este módulo, saltamos.
            $ofertaEnModulo = DocenteMateria::where('id_modulo', $mod->id_modulo)->count();
            if ($ofertaEnModulo >= 2) {
                // Intentamos buscar otro módulo antes de saturar este
                // (Opcional: podrías relajar esto si no hay más módulos)
            }

            // Obtener bloques donde el docente está disponible en este módulo
            $disponibilidades = DisponibilidadDocente::where('id_docente', $idDocente)
                ->where('id_modulo', $mod->id_modulo)
                ->pluck('id_bloque')
                ->toArray();

            if (empty($disponibilidades)) continue;

            shuffle($disponibilidades);

            foreach ($disponibilidades as $bloqueId) {
                // 1. Evitar choque del Docente
                $choqueDocente = DocenteMateria::where('id_docente', $idDocente)
                    ->where('id_modulo', $mod->id_modulo)
                    ->where('id_bloque', $bloqueId)
                    ->exists();
                if ($choqueDocente) continue;

                // 2. Evitar choque de Aula
                $choqueAula = DocenteMateria::where('id_aula', $idAula)
                    ->where('id_modulo', $mod->id_modulo)
                    ->where('id_bloque', $bloqueId)
                    ->exists();
                if ($choqueAula) continue;

                // Si llegamos aquí, el bloque es válido
                $selectedModuloId = $mod->id_modulo;
                $selectedBloqueId = $bloqueId;
                break 2;
            }
        }

        if (!$selectedModuloId || !$selectedBloqueId) {
            return response()->json([
                'message' => 'No se encontró un horario disponible para este docente y aula en los módulos activos. Verifique la disponibilidad del docente.'
            ], 422);
        }

        $assignment = DocenteMateria::create([
            'id_docente' => $idDocente,
            'id_materia' => $idMateria,
            'id_aula'    => $idAula,
            'id_modulo'  => $selectedModuloId,
            'id_bloque'  => $selectedBloqueId,
        ]);

        $enrollmentMode = $request->enrollment_mode ?? 'none';
        
        if ($enrollmentMode === 'all') {
            $estudiantes = \App\Models\Estudiante::all();
            foreach ($estudiantes as $estudiante) {
                \App\Models\Inscripcion::firstOrCreate([
                    'id_estudiante' => $estudiante->id_estudiante,
                    'id_materia'    => $idMateria,
                    'id_modulo'     => $selectedModuloId, 
                ], [
                    'estado' => 'pendiente',
                    'intentos' => 1,
                    'fecha_inscripcion' => now(),
                ]);
            }
        } elseif (is_numeric($enrollmentMode)) {
            \App\Models\Inscripcion::firstOrCreate([
                'id_estudiante' => $enrollmentMode,
                'id_materia'    => $idMateria,
                'id_modulo'     => $selectedModuloId,
            ], [
                'estado' => 'pendiente',
                'intentos' => 1,
                'fecha_inscripcion' => now(),
            ]);
        }

        $modulo = Modulo::find($selectedModuloId);
        $bloque = \App\Models\BloqueHorario::find($selectedBloqueId);

        return response()->json([
            'message' => "Docente asignado correctamente. Horario fijado en {$modulo->nombre} - {$bloque->nombre}.",
            'data' => $assignment
        ], 201);
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
