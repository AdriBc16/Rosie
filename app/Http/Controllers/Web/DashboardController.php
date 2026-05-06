<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\DocenteMateria;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\HistorialMateria;
use App\Models\Materia;
use App\Models\Modulo;
use App\Models\Prerequisito;
use App\Models\Semestre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function headCatalog(Request $request): JsonResponse
    {
        $activeModuloId = $this->getActiveModuloId();

        $teachers = Docente::query()
            ->orderBy('nombre')
            ->get(['id_docente', 'nombre', 'apellido', 'correo', 'es_jefe_carrera']);

        $materias = Materia::query()
            ->orderBy('nombre')
            ->get(['id_materia', 'nombre', 'horas_semanales', 'anio_academico', 'semestre_academico'])
            ->map(fn ($m) => [
                'id_materia' => $m->id_materia,
                'nombre' => $m->nombre,
                'horas_semanales' => $m->horas_semanales,
                'anio_academico' => $m->anio_academico,
                'semestre_academico' => $m->semestre_academico,
            ])
            ->values();

        $modulos = Modulo::query()
            ->orderBy('fecha_inicio')
            ->get(['id_modulo', 'nombre', 'fecha_inicio', 'fecha_final', 'id_semestre', 'creditos', 'numero_en_semestre']);

        $semestres = Semestre::query()
            ->orderBy('numero')
            ->get(['id_semestre', 'nombre', 'numero']);

        $estudiantes = Estudiante::query()
            ->orderBy('nombre')
            ->get(['id_estudiante', 'nombre', 'apellido', 'correo', 'cohorte_ingreso']);

        $aulas = Aula::query()->orderBy('nombre')->get();
        $bloques = BloqueHorario::query()->orderBy('orden')->get();
        $prerrequisitos = Prerequisito::query()->get(['id_prerrequisito', 'id_materia', 'id_materia_prerrequisito', 'descripcion']);
        $historialMaterias = HistorialMateria::query()->get(['id_estudiante', 'id_materia', 'convalidada']);
        $inscripciones = Inscripcion::query()->get(['id_estudiante', 'id_materia', 'id_modulo', 'estado', 'fecha_inscripcion']);

        $asignacionesActuales = DocenteMateria::query()->get();

        return response()->json([
            'data' => [
                'docentes' => $teachers,
                'materias' => $materias,
                'modulos' => $modulos,
                'semestres' => $semestres,
                'estudiantes' => $estudiantes,
                'aulas' => $aulas,
                'bloques' => $bloques,
                'prerrequisitos' => $prerrequisitos,
                'historialMaterias' => $historialMaterias,
                'inscripciones' => $inscripciones,
                'activeModuloId' => $activeModuloId,
                'asignacionesActuales' => $asignacionesActuales,
            ],
        ]);
    }

    public function headPersonSubjects(Request $request, string $tipo, int $idPersona): JsonResponse
    {
        if (!in_array($tipo, ['docente', 'estudiante'], true)) {
            return response()->json(['message' => 'Tipo invalido. Usa docente o estudiante.'], 422);
        }

        $moduleId = $request->query('id_modulo') ? (int) $request->query('id_modulo') : null;

        if ($tipo === 'docente') {
            $teacher = Docente::query()->find($idPersona);
            if (!$teacher) {
                return response()->json(['message' => 'Docente no encontrado.'], 404);
            }

            $query = DocenteMateria::query()
                ->with(['materia:id_materia,nombre', 'modulo:id_modulo,fecha_inicio,fecha_final', 'bloque', 'aula'])
                ->withCount(['inscripciones' => fn($q) => $q->whereColumn('inscripciones.id_modulo', 'docente_materias.id_modulo')])
                ->where('id_docente', $idPersona)
                ->orderBy('id_modulo');

            if ($moduleId) {
                $query->where('id_modulo', $moduleId);
            }

            $subjects = $query->get()->map(fn(DocenteMateria $a) => [
                'id' => $a->id_dm,
                'materia' => $a->materia?->nombre,
                'modulo_id' => $a->id_modulo,
                'modulo_nombre' => $a->modulo?->nombre,
                'fecha_inicio' => $a->modulo?->fecha_inicio,
                'fecha_final' => $a->modulo?->fecha_final,
                'bloque' => $a->bloque?->nombre,
                'aula' => $a->aula?->nombre,
                'estudiantes_count' => $a->inscripciones_count,
            ]);

            return response()->json([
                'data' => [
                    'tipo' => 'docente',
                    'persona' => [
                        'id' => $teacher->id_docente,
                        'nombre' => "{$teacher->nombre} {$teacher->apellido}",
                        'correo' => $teacher->correo,
                    ],
                    'materias' => $subjects,
                ],
            ]);
        }

        $student = Estudiante::query()->find($idPersona);
        if (!$student) {
            return response()->json(['message' => 'Estudiante no encontrado.'], 404);
        }

        $query = Inscripcion::query()
            ->with(['materia:id_materia,nombre', 'modulo:id_modulo,fecha_inicio,fecha_final'])
            ->where('id_estudiante', $idPersona)
            ->orderBy('id_modulo');

        if ($moduleId) {
            $query->where('id_modulo', $moduleId);
        }

        $subjects = $query->get()->map(fn(Inscripcion $i) => [
            'id' => $i->id_inscripcion,
            'materia' => $i->materia?->nombre,
            'modulo_id' => $i->id_modulo,
            'modulo_nombre' => $i->modulo?->nombre,
            'fecha_inicio' => $i->modulo?->fecha_inicio,
            'fecha_final' => $i->modulo?->fecha_final,
            'estado' => $i->estado,
        ]);

        return response()->json([
            'data' => [
                'tipo' => 'estudiante',
                'persona' => [
                    'id' => $student->id_estudiante,
                    'nombre' => "{$student->nombre} {$student->apellido}",
                    'correo' => $student->correo,
                ],
                'materias' => $subjects,
            ],
        ]);
    }

    public function headTeacherAvailability(Request $request, int $idDocente): JsonResponse
    {
        $bloquesDisponibles = DisponibilidadDocente::query()
            ->where('id_docente', $idDocente)
            ->with('bloque')
            ->get()
            ->map(fn($d) => $d->bloque?->nombre);

        return response()->json(['disponibilidad' => $bloquesDisponibles]);
    }

    private function getActiveModuloId(): ?int
    {
        $modulo = Modulo::query()
            ->where('fecha_inicio', '<=', now())
            ->where('fecha_final', '>=', now())
            ->first();

        if ($modulo) {
            return $modulo->id_modulo;
        }

        $modulo = Modulo::query()
            ->where('fecha_inicio', '>', now())
            ->orderBy('fecha_inicio', 'asc')
            ->first();

        if ($modulo) {
            return $modulo->id_modulo;
        }

        $modulo = Modulo::query()->orderBy('fecha_final', 'desc')->first();

        return $modulo?->id_modulo;
    }
}
