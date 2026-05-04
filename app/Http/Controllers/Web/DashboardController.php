<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\DocenteMateria;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\Modulo;
use App\Models\Materia;
use App\Models\BloqueHorario;
use App\Models\DisponibilidadDocente;
use App\Models\Aula;
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
            ->get(['id_materia', 'nombre', 'horas_semanales', 'año_academico']);

        $modulos = Modulo::query()
            ->orderBy('fecha_inicio')
            ->get(['id_modulo', 'nombre', 'fecha_inicio', 'fecha_final', 'id_semestre', 'creditos']);


        $estudiantes = Estudiante::query()
            ->orderBy('nombre')
            ->get(['id_estudiante', 'nombre', 'apellido', 'correo']);

        $aulas = Aula::orderBy('nombre')->get();
        $bloques = BloqueHorario::orderBy('orden')->get();

        $asignacionesActuales = DocenteMateria::where('id_modulo', $activeModuloId)->get();

        return response()->json([
            'data' => [
                'docentes'    => $teachers,
                'materias'    => $materias,
                'modulos'     => $modulos,
                'estudiantes' => $estudiantes,
                'aulas'       => $aulas,
                'bloques'     => $bloques,
                'activeModuloId' => $activeModuloId,
                'asignacionesActuales' => $asignacionesActuales
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
            if (!$teacher) return response()->json(['message' => 'Docente no encontrado.'], 404);

            $query = DocenteMateria::query()
                ->with(['materia:id_materia,nombre', 'modulo:id_modulo,fecha_inicio,fecha_final', 'bloque', 'aula'])
                ->withCount(['inscripciones' => fn ($q) => $q->whereColumn('inscripciones.id_modulo', 'docente_materias.id_modulo')])
                ->where('id_docente', $idPersona)
                ->orderBy('id_modulo');

            if ($moduleId) $query->where('id_modulo', $moduleId);

            $subjects = $query->get()->map(fn (DocenteMateria $a) => [
                'id'               => $a->id_dm,
                'materia'          => $a->materia?->nombre,
                'modulo_id'        => $a->id_modulo,
                'modulo_nombre'    => $a->modulo?->nombre,
                'fecha_inicio'     => $a->modulo?->fecha_inicio,
                'fecha_final'      => $a->modulo?->fecha_final,
                'bloque'           => $a->bloque?->nombre,
                'aula'             => $a->aula?->nombre,
                'estudiantes_count'=> $a->inscripciones_count,
            ]);

            return response()->json([
                'data' => [
                    'tipo'     => 'docente',
                    'persona'  => ['id' => $teacher->id_docente, 'nombre' => "{$teacher->nombre} {$teacher->apellido}", 'correo' => $teacher->correo],
                    'materias' => $subjects,
                ],
            ]);
        }

        $student = Estudiante::query()->find($idPersona);
        if (!$student) return response()->json(['message' => 'Estudiante no encontrado.'], 404);

        $query = Inscripcion::query()
            ->with(['materia:id_materia,nombre', 'modulo:id_modulo,fecha_inicio,fecha_final'])
            ->where('id_estudiante', $idPersona)
            ->orderBy('id_modulo');

        if ($moduleId) $query->where('id_modulo', $moduleId);

        $subjects = $query->get()->map(fn (Inscripcion $i) => [
            'id'          => $i->id_inscripcion,
            'materia'     => $i->materia?->nombre,
            'modulo_id'   => $i->id_modulo,
            'modulo_nombre' => $i->modulo?->nombre,
            'fecha_inicio'=> $i->modulo?->fecha_inicio,
            'fecha_final' => $i->modulo?->fecha_final,
            'estado'      => $i->estado,
        ]);

        return response()->json([
            'data' => [
                'tipo'     => 'estudiante',
                'persona'  => ['id' => $student->id_estudiante, 'nombre' => "{$student->nombre} {$student->apellido}", 'correo' => $student->correo],
                'materias' => $subjects,
            ],
        ]);
    }

    public function headTeacherAvailability(Request $request, int $idDocente): JsonResponse
    {
        $bloquesDisponibles = DisponibilidadDocente::where('id_docente', $idDocente)
            ->with('bloque')
            ->get()
            ->map(fn($d) => $d->bloque?->nombre);

        return response()->json(['disponibilidad' => $bloquesDisponibles]);
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
