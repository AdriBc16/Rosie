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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
            ->get(['id_modulo', 'nombre', 'fecha_inicio', 'fecha_final', 'id_semestre', 'numero_en_semestre']);

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

        $disponibilidadDocente = DisponibilidadDocente::query()
            ->get(['id_docente', 'id_bloque', 'id_modulo']);

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
                'disponibilidadDocente' => $disponibilidadDocente,
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

    public function headStudents(Request $request): JsonResponse
    {
        $estudiantes = Estudiante::query()
            ->orderBy('cohorte_ingreso')
            ->orderBy('nombre')
            ->get(['id_estudiante', 'nombre', 'apellido', 'correo', 'cohorte_ingreso', 'es_traspaso']);

        $grouped = $estudiantes->groupBy('cohorte_ingreso')
            ->map(fn ($group, $cohorte) => [
                'cohorte' => $cohorte,
                'estudiantes' => $group->map(fn ($e) => [
                    'id_estudiante'   => $e->id_estudiante,
                    'nombre'          => trim("{$e->nombre} {$e->apellido}"),
                    'correo'          => $e->correo,
                    'cohorte_ingreso' => $e->cohorte_ingreso,
                    'es_traspaso'     => (bool) $e->es_traspaso,
                ])->values(),
            ])->values();

        return response()->json(['data' => $grouped]);
    }

    public function headStudentMaterias(Request $request, int $idEstudiante): JsonResponse
    {
        $estudiante = Estudiante::findOrFail($idEstudiante);

        $todasLasMaterias = Materia::orderBy('semestre_academico')->orderBy('nombre')->get();

        // Inscripciones del estudiante
        $inscripciones = Inscripcion::where('id_estudiante', $idEstudiante)
            ->get()
            ->keyBy('id_materia');

        // Historial (convalidadas)
        $historial = HistorialMateria::where('id_estudiante', $idEstudiante)
            ->get()
            ->keyBy('id_materia');

        // Prerrequisitos: id_materia => [ids de sus prerrequisitos]
        $prereqMap = Prerequisito::all()
            ->groupBy('id_materia')
            ->map(fn ($rows) => $rows->pluck('id_materia_prerrequisito')->all());

        // IDs aprobadas o convalidadas (cuentan para habilitar prerrequisitos)
        $aprobadas = collect();
        foreach ($inscripciones as $idMat => $insc) {
            if ($insc->estado === 'aprobada') $aprobadas->push($idMat);
        }
        foreach ($historial as $idMat => $hist) {
            if ($hist->convalidada) $aprobadas->push($idMat);
        }
        $aprobadas = $aprobadas->unique()->values();

        $materiasPorSemestre = $todasLasMaterias->groupBy('semestre_academico')
            ->map(fn ($mats, $semNum) => [
                'semestre' => $semNum,
                'materias' => $mats->map(function ($m) use ($inscripciones, $historial, $prereqMap, $aprobadas) {
                    $idMat = $m->id_materia;

                    // Determinar estado
                    if (isset($historial[$idMat]) && $historial[$idMat]->convalidada) {
                        $estado = 'convalidada';
                    } elseif (isset($inscripciones[$idMat])) {
                        $estado = $inscripciones[$idMat]->estado;
                    } else {
                        $prereqs = $prereqMap[$idMat] ?? [];
                        $prereqsCumplidos = empty($prereqs) || collect($prereqs)->every(fn ($pid) => $aprobadas->contains($pid));
                        $estado = $prereqsCumplidos ? 'habilitada' : 'bloqueada';
                    }

                    return [
                        'id_materia'          => $idMat,
                        'nombre'              => $m->nombre,
                        'semestre_academico'  => $m->semestre_academico,
                        'anio_academico'      => $m->anio_academico,
                        'estado'              => $estado,
                        'id_inscripcion'      => $inscripciones[$idMat]?->id_inscripcion ?? null,
                        'id_historial'        => $historial[$idMat]?->id_historial ?? null,
                    ];
                })->values(),
            ])->values();

        return response()->json([
            'data' => [
                'estudiante'  => [
                    'id_estudiante'   => $estudiante->id_estudiante,
                    'nombre'          => trim("{$estudiante->nombre} {$estudiante->apellido}"),
                    'correo'          => $estudiante->correo,
                    'cohorte_ingreso' => $estudiante->cohorte_ingreso,
                    'es_traspaso'     => (bool) $estudiante->es_traspaso,
                ],
                'semestres' => $materiasPorSemestre,
            ],
        ]);
    }

    public function headConvalidarMateria(Request $request, int $idEstudiante, int $idMateria): JsonResponse
    {
        Materia::findOrFail($idMateria);
        Estudiante::findOrFail($idEstudiante);

        DB::transaction(function () use ($idEstudiante, $idMateria) {
            // Marcar en historial como convalidada
            HistorialMateria::updateOrCreate(
                ['id_estudiante' => $idEstudiante, 'id_materia' => $idMateria],
                ['convalidada' => true]
            );

            // Marcar inscripcion como aprobada si existe, o crear una
            $inscripcion = Inscripcion::where('id_estudiante', $idEstudiante)
                ->where('id_materia', $idMateria)
                ->first();

            if ($inscripcion) {
                $inscripcion->update(['estado' => 'aprobada']);
            } else {
                Inscripcion::create([
                    'id_estudiante'     => $idEstudiante,
                    'id_materia'        => $idMateria,
                    'id_modulo'         => null,
                    'estado'            => 'aprobada',
                    'intentos'          => 1,
                    'fecha_inscripcion' => now(),
                ]);
            }
        });

        return response()->json(['message' => 'Materia convalidada correctamente.']);
    }

    public function headDesconvalidarMateria(Request $request, int $idEstudiante, int $idMateria): JsonResponse
    {
        DB::transaction(function () use ($idEstudiante, $idMateria) {
            HistorialMateria::where('id_estudiante', $idEstudiante)
                ->where('id_materia', $idMateria)
                ->update(['convalidada' => false]);

            Inscripcion::where('id_estudiante', $idEstudiante)
                ->where('id_materia', $idMateria)
                ->where('estado', 'aprobada')
                ->delete();
        });

        return response()->json(['message' => 'Convalidación revertida.']);
    }

    public function headCreateDocente(Request $request): JsonResponse
    {
        $request->validate([
            'nombre'   => 'required|string|max:80',
            'apellido' => 'nullable|string|max:80',
            'correo'   => 'required|email|unique:docentes,correo',
        ]);

        $docente = Docente::create([
            'nombre'          => trim($request->nombre),
            'apellido'        => trim($request->apellido ?? ''),
            'correo'          => strtolower(trim($request->correo)),
            'password'        => Hash::make('UPB123'),
            'es_jefe_carrera' => false,
        ]);

        return response()->json([
            'message' => 'Docente creado correctamente.',
            'docente' => [
                'id_docente' => $docente->id_docente,
                'nombre'     => $docente->nombre,
                'apellido'   => $docente->apellido,
                'correo'     => $docente->correo,
            ],
        ], 201);
    }
}
