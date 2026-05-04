<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PortalLoginRequest;
use App\Models\Docente;
use App\Models\DocenteMateria;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\Modulo;
use App\Models\Materia;
use App\Models\BloqueHorario;
use App\Models\DisponibilidadDocente;
use App\Models\HorarioGenerado;
use App\Models\DetalleHorario;
use App\Models\Aula;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class PortalAuthController extends Controller
{
    public function login(PortalLoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $role     = $credentials['role'];
        $correo   = $credentials['correo'];
        $password = $credentials['password'];

        if ($role === 'estudiante') {
            $user = Estudiante::query()->where('correo', $correo)->first();

            if (!$user || !Hash::check($password, $user->password)) {
                return response()->json(['message' => 'Credenciales invalidas.'], 401);
            }

            $this->storePortalSession($request, [
                'role'  => 'estudiante',
                'id'    => $user->id_estudiante,
                'name'  => trim("{$user->nombre} {$user->apellido}"),
                'email' => $user->correo,
                'es_traspaso' => $user->es_traspaso,
            ]);

            return response()->json(['role' => 'estudiante']);
        }

        $teacher = Docente::query()->where('correo', $correo)->first();

        if (!$teacher || !Hash::check($password, $teacher->password)) {
            return response()->json(['message' => 'Credenciales invalidas.'], 401);
        }

        if ($role === 'jefe' && (int) $teacher->es_jefe_carrera !== 1) {
            return response()->json(['message' => 'El docente no es jefe de carrera.'], 403);
        }

        if ($role === 'docente' && (int) $teacher->es_jefe_carrera === 1) {
            return response()->json(['message' => 'Este usuario es jefe de carrera. Ingresa desde ese perfil.'], 403);
        }

        $this->storePortalSession($request, [
            'role'    => $role,
            'id'      => $teacher->id_docente,
            'name'    => trim("{$teacher->nombre} {$teacher->apellido}"),
            'email'   => $teacher->correo,
            'is_head' => (int) $teacher->es_jefe_carrera === 1,
        ]);

        return response()->json(['role' => $role]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->forget('portal_user');
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sesion cerrada correctamente.']);
    }

    public function me(Request $request): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');

        if (!$portalUser) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $data = ['user' => $portalUser];

        if ($portalUser['role'] === 'jefe') {
            $data['teachersCount'] = Docente::query()->where('es_jefe_carrera', false)->count();
            $data['studentsCount'] = Estudiante::count();
            $data['modulosCount']  = Modulo::count();
        }

        if ($portalUser['role'] === 'estudiante') {
            $inscripciones = Inscripcion::query()
                ->with(['materia:id_materia,nombre', 'modulo:id_modulo,fecha_inicio,fecha_final,creditos'])
                ->where('id_estudiante', $portalUser['id'])
                ->whereIn('estado', ['cursando', 'pendiente'])
                ->get();

            $data['inscripciones'] = $inscripciones->map(fn (Inscripcion $i) => [
                'id_inscripcion'   => $i->id_inscripcion,
                'materia'          => $i->materia?->nombre,
                'modulo'           => [
                    'id'           => $i->modulo?->id_modulo,
                    'fecha_inicio' => $i->modulo?->fecha_inicio,
                    'fecha_final'  => $i->modulo?->fecha_final,
                    'creditos'     => $i->modulo?->creditos,
                ],
                'estado'           => $i->estado,
                'intentos'         => $i->intentos,
            ]);

            $data['totalCredits'] = $inscripciones->sum(fn ($i) => $i->modulo?->creditos ?? 0);
            
            // Horario generado
            $data['horario'] = HorarioGenerado::query()
                ->with(['detalles.materia', 'detalles.docente', 'detalles.bloque', 'detalles.aula'])
                ->where('id_estudiante', $portalUser['id'])
                ->orderBy('fecha_generacion', 'desc')
                ->first();
        }

        return response()->json(['data' => $data]);
    }

    // ─── DOCENTE ─────────────────────────────────────────────────────────────

    public function teacherAssignments(Request $request): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');

        $assignments = DocenteMateria::query()
            ->with([
                'materia:id_materia,nombre',
                'modulo:id_modulo,fecha_inicio,fecha_final',
                'bloque',
                'aula'
            ])
            ->withCount(['inscripciones' => fn ($q) => $q->whereColumn('inscripciones.id_modulo', 'docente_materias.id_modulo')])
            ->where('id_docente', $portalUser['id'])
            ->orderBy('id_modulo')
            ->get();

        return response()->json([
            'data' => $assignments->map(fn (DocenteMateria $a) => [
                'id_dm'              => $a->id_dm,
                'materia'            => ['id' => $a->materia?->id_materia, 'nombre' => $a->materia?->nombre],
                'modulo'             => [
                    'id'           => $a->modulo?->id_modulo,
                    'fecha_inicio' => $a->modulo?->fecha_inicio,
                    'fecha_final'  => $a->modulo?->fecha_final,
                ],
                'bloque'             => $a->bloque?->nombre,
                'aula'               => $a->aula?->nombre,
                'estudiantes_count'  => $a->inscripciones_count,
            ]),
        ]);
    }

    public function teacherAssignmentStudents(Request $request, int $idDm): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');

        $assignment = DocenteMateria::query()
            ->with([
                'materia:id_materia,nombre',
                'modulo:id_modulo,fecha_inicio,fecha_final',
            ])
            ->where('id_docente', $portalUser['id'])
            ->where('id_dm', $idDm)
            ->first();

        if (!$assignment) {
            return response()->json(['message' => 'Asignacion no encontrada.'], 404);
        }

        $students = Inscripcion::query()
            ->with('estudiante:id_estudiante,nombre,apellido,correo')
            ->where('id_materia', $assignment->id_materia)
            ->where('id_modulo', $assignment->id_modulo)
            ->get()
            ->map(fn (Inscripcion $i) => [
                'id_estudiante' => $i->estudiante?->id_estudiante,
                'nombre'        => trim("{$i->estudiante?->nombre} {$i->estudiante?->apellido}"),
                'correo'        => $i->estudiante?->correo,
                'estado'        => $i->estado,
            ])
            ->sortBy('nombre')
            ->values();

        return response()->json([
            'data' => [
                'asignacion' => [
                    'id_dm'        => $assignment->id_dm,
                    'materia'      => $assignment->materia?->nombre,
                    'modulo'       => $assignment->modulo?->id_modulo,
                    'fecha_inicio' => $assignment->modulo?->fecha_inicio,
                    'fecha_fin'    => $assignment->modulo?->fecha_final,
                ],
                'estudiantes' => $students,
            ],
        ]);
    }

    public function getDisponibilidad(Request $request): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');
        $bloques = BloqueHorario::orderBy('orden')->get();
        $misBloques = DisponibilidadDocente::where('id_docente', $portalUser['id'])->pluck('id_bloque')->toArray();

        return response()->json([
            'bloques' => $bloques,
            'misBloques' => $misBloques
        ]);
    }

    public function saveDisponibilidad(Request $request): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');
        $request->validate([
            'bloques' => 'array',
            'bloques.*' => 'exists:bloques_horarios,id_bloque'
        ]);

        DB::transaction(function() use ($portalUser, $request) {
            DisponibilidadDocente::where('id_docente', $portalUser['id'])->delete();
            foreach ($request->bloques as $idBloque) {
                DisponibilidadDocente::create([
                    'id_docente' => $portalUser['id'],
                    'id_bloque' => $idBloque
                ]);
            }
        });

        return response()->json(['message' => 'Disponibilidad guardada correctamente.']);
    }

    // ─── ESTUDIANTE ──────────────────────────────────────────────────────────

    public function generateSchedule(Request $request): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');
        $idEstudiante = $portalUser['id'];

        // Obtener inscripciones vigentes (cursando o pendiente)
        $inscripciones = Inscripcion::query()
            ->with(['materia', 'modulo'])
            ->where('id_estudiante', $idEstudiante)
            ->whereIn('estado', ['cursando', 'pendiente'])
            ->get();

        if ($inscripciones->isEmpty()) {
            return response()->json(['message' => 'No tienes materias inscritas para generar horario.'], 422);
        }

        // Validar créditos totales
        $totalCredits = $inscripciones->sum(fn($i) => $i->modulo?->creditos ?? 0);
        if ($totalCredits > 29) {
            return response()->json(['message' => "Excediste el límite de 29 créditos (Total: $totalCredits). No se puede generar el horario."], 422);
        }

        return DB::transaction(function() use ($idEstudiante, $inscripciones) {
            // Eliminar horario previo para este estudiante
            HorarioGenerado::where('id_estudiante', $idEstudiante)->delete();

            $horario = HorarioGenerado::create([
                'id_estudiante' => $idEstudiante,
                'id_modulo' => $inscripciones->first()->id_modulo, // Simplificación: toma el módulo de la primera
                'estado' => 'confirmado',
                'fecha_generacion' => now()
            ]);

            foreach ($inscripciones as $i) {
                // Buscar la asignación docente (DocenteMateria) que corresponde a esta materia y módulo
                $asignacion = DocenteMateria::where('id_materia', $i->id_materia)
                    ->where('id_modulo', $i->id_modulo)
                    ->first();

                if ($asignacion && $asignacion->id_bloque) {
                    DetalleHorario::create([
                        'id_horario' => $horario->id_horario,
                        'id_materia' => $i->id_materia,
                        'id_docente' => $asignacion->id_docente,
                        'id_bloque' => $asignacion->id_bloque,
                        'id_aula' => $asignacion->id_aula
                    ]);
                }
            }

            return response()->json(['message' => 'Horario generado con éxito.']);
        });
    }

    // ─── JEFE ─────────────────────────────────────────────────────────────────

    public function headCatalog(Request $request): JsonResponse
    {
        $teachers = Docente::query()
            ->orderBy('nombre')
            ->get(['id_docente', 'nombre', 'apellido', 'correo', 'es_jefe_carrera']);

        $materias = Materia::query()
            ->orderBy('nombre')
            ->get(['id_materia', 'nombre', 'horas_semanales', 'año_academico']);

        $modulos = Modulo::query()
            ->orderBy('fecha_inicio')
            ->get(['id_modulo', 'fecha_inicio', 'fecha_final', 'id_semestre', 'creditos']);

        $estudiantes = Estudiante::query()
            ->orderBy('nombre')
            ->get(['id_estudiante', 'nombre', 'apellido', 'correo']);

        $aulas = Aula::orderBy('nombre')->get();
        $bloques = BloqueHorario::orderBy('orden')->get();

        return response()->json([
            'data' => [
                'docentes'    => $teachers,
                'materias'    => $materias,
                'modulos'     => $modulos,
                'estudiantes' => $estudiantes,
                'aulas'       => $aulas,
                'bloques'     => $bloques
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
                ->withCount(['inscripciones' => fn ($q) => $q->whereColumn('inscripciones.id_modulo', 'docente_materias.id_modulo')])
                ->where('id_docente', $idPersona)
                ->orderBy('id_modulo');

            if ($moduleId) {
                $query->where('id_modulo', $moduleId);
            }

            $subjects = $query->get()->map(fn (DocenteMateria $a) => [
                'id'               => $a->id_dm,
                'materia'          => $a->materia?->nombre,
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

        $subjects = $query->get()->map(fn (Inscripcion $i) => [
            'id'          => $i->id_inscripcion,
            'materia'     => $i->materia?->nombre,
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

        return response()->json([
            'disponibilidad' => $bloquesDisponibles
        ]);
    }

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
            'id_modulo'  => 'required|integer|exists:modulos,id_modulo',
            'id_bloque'  => 'nullable|integer|exists:bloques_horarios,id_bloque',
            'id_aula'    => 'nullable|integer|exists:aulas,id_aula',
        ]);

        // Verificar si el bloque ya está ocupado en ese módulo por el mismo docente o aula
        if ($request->id_bloque) {
            $clashDocente = DocenteMateria::where('id_docente', $request->id_docente)
                ->where('id_modulo', $request->id_modulo)
                ->where('id_bloque', $request->id_bloque)
                ->exists();
            if ($clashDocente) return response()->json(['message' => 'El docente ya tiene una materia en ese bloque/modulo.'], 422);

            if ($request->id_aula) {
                $clashAula = DocenteMateria::where('id_aula', $request->id_aula)
                    ->where('id_modulo', $request->id_modulo)
                    ->where('id_bloque', $request->id_bloque)
                    ->exists();
                if ($clashAula) return response()->json(['message' => 'El aula ya está ocupada en ese bloque/modulo.'], 422);
            }
        }

        $assignment = DocenteMateria::query()->create($request->all());

        return response()->json(['message' => 'Materia asignada correctamente.', 'data' => $assignment], 201);
    }

    public function headEnrollStudent(Request $request): JsonResponse
    {
        $request->validate([
            'id_estudiante' => 'required|integer|exists:estudiantes,id_estudiante',
            'id_materia'    => 'required|integer|exists:materias,id_materia',
            'id_modulo'     => 'required|integer|exists:modulos,id_modulo',
        ]);

        // Verificar si ya está inscrito
        $exists = Inscripcion::where('id_estudiante', $request->id_estudiante)
            ->where('id_materia', $request->id_materia)
            ->where('id_modulo', $request->id_modulo)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'El estudiante ya está inscrito en esta materia en este módulo.'], 422);
        }

        $inscripcion = Inscripcion::create([
            'id_estudiante' => $request->id_estudiante,
            'id_materia'    => $request->id_materia,
            'id_modulo'     => $request->id_modulo,
            'estado'        => 'cursando',
            'fecha_inscripcion' => now(),
            'intentos'      => 1
        ]);

        return response()->json(['message' => 'Estudiante inscrito correctamente.', 'data' => $inscripcion], 201);
    }

    private function storePortalSession(Request $request, array $payload): void
    {
        $request->session()->regenerate();
        $request->session()->put('portal_user', $payload);
    }
}
