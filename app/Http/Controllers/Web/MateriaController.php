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

        // Solo vinculamos lo básico. Módulo y Bloque se elegirán en el portal del estudiante.
        // Esto permite que el sistema sugiera opciones basadas en disponibilidad.
        $dm = DocenteMateria::updateOrCreate(
            ['id_materia' => $idMateria], 
            [
                'id_docente' => $idDocente,
                'id_aula'    => $idAula,
                'id_modulo'  => null,
                'id_bloque'  => null,
            ]
        );

        $enrollmentMode = $request->enrollment_mode ?? 'none';
        
        if ($enrollmentMode === 'all') {
            $estudiantes = \App\Models\Estudiante::all();
            foreach ($estudiantes as $estudiante) {
                \App\Models\Inscripcion::updateOrCreate([
                    'id_estudiante' => $estudiante->id_estudiante,
                    'id_materia'    => $idMateria,
                ], [
                    'id_modulo' => null,
                    'estado' => 'pendiente',
                    'intentos' => 1,
                    'fecha_inscripcion' => now(),
                ]);
            }
        } elseif (is_numeric($enrollmentMode)) {
            \App\Models\Inscripcion::updateOrCreate([
                'id_estudiante' => $enrollmentMode,
                'id_materia'    => $idMateria,
            ], [
                'id_modulo' => null,
                'estado' => 'pendiente',
                'intentos' => 1,
                'fecha_inscripcion' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Docente vinculado a la materia. El horario será definido por la elección de los estudiantes.',
            'data'    => $dm
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
