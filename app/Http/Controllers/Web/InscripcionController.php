<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
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

        $idEstudiante = $request->id_estudiante;
        $idMateria = $request->id_materia;

        // 1. Verificar Prerrequisitos de la materia
        $prerrequisitos = \App\Models\Prerequisito::where('id_materia', $idMateria)->get();
        foreach ($prerrequisitos as $p) {
            $cumple = Inscripcion::where('id_estudiante', $idEstudiante)
                ->where('id_materia', $p->id_materia_prerrequisito)
                ->where('estado', 'aprobada')
                ->exists() 
                || 
                \App\Models\HistorialMateria::where('id_estudiante', $idEstudiante)
                ->where('id_materia', $p->id_materia_prerrequisito)
                ->where('convalidada', true)
                ->exists();

            if (!$cumple) {
                $materiaReq = Materia::find($p->id_materia_prerrequisito);
                return response()->json([
                    'message' => "El estudiante no cumple con los prerrequisitos. Debe aprobar: {$materiaReq->nombre}."
                ], 422);
            }
        }

        // 2. Lógica de selección de módulo
        $selectedModuloId = null;
        $errorDetail = "No se encontró un módulo con cupo disponible (máx 3 materias) o sin choques de horario.";

        if ($request->filled('id_modulo')) {
            // Caso Especial: El Jefe eligió un módulo manualmente
            $selectedModuloId = $request->id_modulo;
            $moduloManual = Modulo::find($selectedModuloId);
            
            // Validar límite incluso en caso manual
            $countInModulo = Inscripcion::where('id_estudiante', $idEstudiante)
                ->where('id_modulo', $selectedModuloId)
                ->count();
            if ($countInModulo >= 3) {
                return response()->json(['message' => "El módulo seleccionado ya tiene el máximo de 3 materias."], 422);
            }
        } else {
            // Selección Automática: Buscamos el mejor módulo disponible
            $modulosDisponibles = Modulo::where('fecha_final', '>=', now())
                ->orderBy('fecha_inicio')
                ->get();

            foreach ($modulosDisponibles as $modulo) {
                // A. Verificar límite de 3 materias por módulo
                $countInModulo = Inscripcion::where('id_estudiante', $idEstudiante)
                    ->where('id_modulo', $modulo->id_modulo)
                    ->count();

                if ($countInModulo >= 3) continue;

                // B. Verificar choque de horario
                $asignacionNueva = \App\Models\DocenteMateria::where('id_materia', $idMateria)
                    ->where('id_modulo', $modulo->id_modulo)
                    ->first();

                if ($asignacionNueva && $asignacionNueva->id_bloque) {
                    $choqueHorario = Inscripcion::query()
                        ->join('docente_materias', function($join) {
                            $join->on('inscripciones.id_materia', '=', 'docente_materias.id_materia')
                                 ->on('inscripciones.id_modulo', '=', 'docente_materias.id_modulo');
                        })
                        ->where('inscripciones.id_estudiante', $idEstudiante)
                        ->where('inscripciones.id_modulo', $modulo->id_modulo)
                        ->where('docente_materias.id_bloque', $asignacionNueva->id_bloque)
                        ->exists();

                    if ($choqueHorario) continue;
                }

                $selectedModuloId = $modulo->id_modulo;
                break;
            }
        }

        if (!$selectedModuloId) {
            return response()->json(['message' => $errorDetail], 422);
        }

        // 3. Verificar límite de 29 créditos por semestre
        $moduloSeleccionado = Modulo::find($selectedModuloId);
        $idSemestre = $moduloSeleccionado->id_semestre;
        $materiaNew = Materia::find($idMateria);
        $creditosNueva = $materiaNew->creditos ?? 3;

        $creditosActuales = Inscripcion::query()
            ->join('modulos', 'inscripciones.id_modulo', '=', 'modulos.id_modulo')
            ->join('materias', 'inscripciones.id_materia', '=', 'materias.id_materia')
            ->where('inscripciones.id_estudiante', $idEstudiante)
            ->where('modulos.id_semestre', $idSemestre)
            ->whereIn('inscripciones.estado', ['cursando', 'pendiente', 'bloqueada'])
            ->sum('materias.creditos');

        if (($creditosActuales + $creditosNueva) > 29) {
            $estudiante = Estudiante::find($idEstudiante);
            $nombreAlumno = trim("{$estudiante->nombre} {$estudiante->apellido}");
            return response()->json([
                'message' => "el alumno {$nombreAlumno} no puede cursar la materia {$materiaNew->nombre} porque excede los créditos, máx. 29"
            ], 422);
        }

        // 4. Verificar si ya está inscrito específicamente en ese módulo (redundante pero seguro)
        $exists = Inscripcion::where('id_estudiante', $idEstudiante)
            ->where('id_materia', $idMateria)
            ->where('id_modulo', $selectedModuloId)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'El estudiante ya está inscrito en esta materia en el módulo seleccionado.'], 422);
        }

        $inscripcion = Inscripcion::create([
            'id_estudiante' => $idEstudiante,
            'id_materia'    => $idMateria,
            'id_modulo'     => $selectedModuloId,
            'estado'        => 'cursando',
            'fecha_inscripcion' => now(),
            'intentos'      => 1
        ]);

        $moduloNombre = Modulo::find($selectedModuloId)->nombre;
        return response()->json([
            'message' => "Estudiante inscrito correctamente en el {$moduloNombre}.", 
            'data' => $inscripcion
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
