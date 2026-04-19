<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\DocenteMateria;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\HorasLibresDoc;
use App\Models\Materia;
use App\Models\Modulo;
use App\Models\Inscripcion;
use App\Models\Universidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PortalAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('portal.login');
    }

    /**
     * @throws ValidationException
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'role' => ['required', 'in:estudiante,docente,jefe'],
            'correo' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ]);

        $role = $credentials['role'];
        $correo = mb_strtolower(trim($credentials['correo']));
        $password = $credentials['password'];

        if ($role === 'estudiante') {
            $user = Estudiante::query()->where('correo', $correo)->first();

            if (!$user || !$user->password || !Hash::check($password, $user->password)) {
                return back()->withInput()->with('auth_error', 'Credenciales invalidas para estudiante.');
            }

            $this->storePortalSession($request, [
                'role' => 'estudiante',
                'id' => $user->id_estudiante,
                'name' => $user->nombre,
                'email' => $user->correo,
                'university_id' => $user->id_universidad,
                'module_id' => $user->id_modulo,
            ]);

            return redirect()->route('portal.student');
        }

        $teacher = Docente::query()->where('correo', $correo)->first();

        if (!$teacher || !$teacher->password || !Hash::check($password, $teacher->password)) {
            return back()->withInput()->with('auth_error', 'Credenciales invalidas para docente/jefe.');
        }

        if ($role === 'jefe' && (int) $teacher->es_jefe_carrera !== 1) {
            return back()->withInput()->with('auth_error', 'El docente no esta registrado como jefe de carrera.');
        }

        if ($role === 'docente' && (int) $teacher->es_jefe_carrera === 1) {
            return back()->withInput()->with('auth_error', 'Este usuario es jefe de carrera. Ingresa desde ese perfil.');
        }

        $this->storePortalSession($request, [
            'role' => $role,
            'id' => $teacher->id_docente,
            'name' => $teacher->nombre,
            'email' => $teacher->correo,
            'university_id' => $teacher->id_universidad,
            'is_head' => (int) $teacher->es_jefe_carrera === 1,
        ]);

        return $role === 'jefe'
            ? redirect()->route('portal.head')
            : redirect()->route('portal.teacher');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('portal_user');
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')->with('auth_ok', 'Sesion cerrada correctamente.');
    }

    public function studentDashboard(Request $request): View
    {
        $portalUser = $request->session()->get('portal_user');

        return view('portal.student', [
            'portalUser' => $portalUser,
            'modulo' => Modulo::query()->find($portalUser['module_id']),
            'universidad' => Universidad::query()->find($portalUser['university_id']),
        ]);
    }

    public function teacherDashboard(Request $request): View
    {
        $portalUser = $request->session()->get('portal_user');

        return view('portal.teacher', [
            'portalUser' => $portalUser,
            'universidad' => Universidad::query()->find($portalUser['university_id']),
        ]);
    }

    public function headDashboard(Request $request): View
    {
        $portalUser = $request->session()->get('portal_user');

        $teachersCount = Docente::query()
            ->where('id_universidad', $portalUser['university_id'])
            ->count();

        $studentsCount = Estudiante::query()
            ->where('id_universidad', $portalUser['university_id'])
            ->count();

        return view('portal.head', [
            'portalUser' => $portalUser,
            'teachersCount' => $teachersCount,
            'studentsCount' => $studentsCount,
            'modulosCount' => Modulo::query()->count(),
            'universidad' => Universidad::query()->find($portalUser['university_id']),
        ]);
    }

    public function teacherAssignments(Request $request): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');

        $assignments = DocenteMateria::query()
            ->with(['materia:id_materia,nombre', 'horario:id_horario,nombre,hora_inicio,hora_fin', 'modulo:id_modulo,nombre,fecha_inicio,fecha_final'])
            ->withCount('inscripciones')
            ->where('id_docente', $portalUser['id'])
            ->orderBy('id_modulo')
            ->orderBy('id_horario')
            ->get();

        return response()->json([
            'data' => $assignments->map(function (DocenteMateria $assignment): array {
                return [
                    'id_dm' => $assignment->id_dm,
                    'materia' => [
                        'id' => $assignment->materia?->id_materia,
                        'nombre' => $assignment->materia?->nombre,
                    ],
                    'modulo' => [
                        'id' => $assignment->modulo?->id_modulo,
                        'nombre' => $assignment->modulo?->nombre,
                        'fecha_inicio' => $assignment->modulo?->fecha_inicio,
                        'fecha_final' => $assignment->modulo?->fecha_final,
                    ],
                    'horario' => [
                        'id' => $assignment->horario?->id_horario,
                        'nombre' => $assignment->horario?->nombre,
                        'hora_inicio' => $assignment->horario?->hora_inicio,
                        'hora_fin' => $assignment->horario?->hora_fin,
                    ],
                    'estudiantes_count' => $assignment->inscripciones_count,
                ];
            }),
        ]);
    }

    public function teacherAssignmentStudents(Request $request, int $idDm): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');

        $assignment = DocenteMateria::query()
            ->with([
                'materia:id_materia,nombre',
                'horario:id_horario,nombre,hora_inicio,hora_fin',
                'modulo:id_modulo,nombre,fecha_inicio,fecha_final',
                'inscripciones.estudiante:id_estudiante,nombre,correo,id_modulo',
            ])
            ->where('id_docente', $portalUser['id'])
            ->where('id_dm', $idDm)
            ->first();

        if (!$assignment) {
            return response()->json([
                'message' => 'No se encontro la asignacion para este docente.',
            ], 404);
        }

        $students = $assignment->inscripciones
            ->map(function (Inscripcion $inscripcion): array {
                $student = $inscripcion->estudiante;

                return [
                    'id_estudiante' => $student?->id_estudiante,
                    'nombre' => $student?->nombre,
                    'correo' => $student?->correo,
                ];
            })
            ->sortBy('nombre')
            ->values();

        return response()->json([
            'data' => [
                'asignacion' => [
                    'id_dm' => $assignment->id_dm,
                    'materia' => $assignment->materia?->nombre,
                    'modulo' => $assignment->modulo?->nombre,
                    'horario' => $assignment->horario?->nombre,
                    'hora_inicio' => $assignment->horario?->hora_inicio,
                    'hora_fin' => $assignment->horario?->hora_fin,
                ],
                'estudiantes' => $students,
            ],
        ]);
    }

    public function headCatalog(Request $request): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');

        $teachers = Docente::query()
            ->where('id_universidad', $portalUser['university_id'])
            ->orderBy('nombre')
            ->get(['id_docente', 'nombre', 'correo', 'es_jefe_carrera']);

        $materias = Materia::query()
            ->orderBy('nombre')
            ->get(['id_materia', 'nombre']);

        $modulos = Modulo::query()
            ->orderBy('fecha_inicio')
            ->get(['id_modulo', 'nombre', 'fecha_inicio', 'fecha_final']);

        $horarios = Horario::query()
            ->orderBy('hora_inicio')
            ->get(['id_horario', 'nombre', 'hora_inicio', 'hora_fin']);

        $students = Estudiante::query()
            ->where('id_universidad', $portalUser['university_id'])
            ->orderBy('nombre')
            ->get(['id_estudiante', 'nombre', 'correo', 'id_modulo']);

        return response()->json([
            'data' => [
                'docentes' => $teachers,
                'estudiantes' => $students,
                'materias' => $materias,
                'modulos' => $modulos,
                'horarios' => $horarios,
            ],
        ]);
    }

    public function headPersonSubjects(Request $request, string $tipo, int $idPersona): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');

        if (!in_array($tipo, ['docente', 'estudiante'], true)) {
            return response()->json([
                'message' => 'Tipo de persona invalido. Usa docente o estudiante.',
            ], 422);
        }

        $moduleId = $request->query('id_modulo');
        $moduleId = $moduleId !== null ? (int) $moduleId : null;

        if ($tipo === 'docente') {
            $teacher = Docente::query()
                ->where('id_universidad', $portalUser['university_id'])
                ->where('id_docente', $idPersona)
                ->first();

            if (!$teacher) {
                return response()->json([
                    'message' => 'Docente no encontrado en tu universidad.',
                ], 404);
            }

            $query = DocenteMateria::query()
                ->with([
                    'materia:id_materia,nombre',
                    'modulo:id_modulo,nombre,fecha_inicio,fecha_final',
                    'horario:id_horario,nombre,hora_inicio,hora_fin',
                ])
                ->withCount('inscripciones')
                ->where('id_docente', $idPersona)
                ->orderBy('id_modulo')
                ->orderBy('id_horario');

            if ($moduleId) {
                $query->where('id_modulo', $moduleId);
            }

            $subjects = $query->get()->map(function (DocenteMateria $assignment): array {
                return [
                    'id' => $assignment->id_dm,
                    'materia' => $assignment->materia?->nombre,
                    'modulo' => $assignment->modulo?->nombre,
                    'fecha_inicio' => $assignment->modulo?->fecha_inicio,
                    'fecha_final' => $assignment->modulo?->fecha_final,
                    'horario' => $assignment->horario?->nombre,
                    'hora_inicio' => $assignment->horario?->hora_inicio,
                    'hora_fin' => $assignment->horario?->hora_fin,
                    'estudiantes_count' => $assignment->inscripciones_count,
                ];
            });

            return response()->json([
                'data' => [
                    'tipo' => 'docente',
                    'persona' => [
                        'id' => $teacher->id_docente,
                        'nombre' => $teacher->nombre,
                        'correo' => $teacher->correo,
                    ],
                    'materias' => $subjects,
                ],
            ]);
        }

        $student = Estudiante::query()
            ->where('id_universidad', $portalUser['university_id'])
            ->where('id_estudiante', $idPersona)
            ->first();

        if (!$student) {
            return response()->json([
                'message' => 'Estudiante no encontrado en tu universidad.',
            ], 404);
        }

        $query = Inscripcion::query()
            ->with([
                'docenteMateria.materia:id_materia,nombre',
                'docenteMateria.modulo:id_modulo,nombre,fecha_inicio,fecha_final',
                'docenteMateria.horario:id_horario,nombre,hora_inicio,hora_fin',
                'docenteMateria.docente:id_docente,nombre,correo',
            ])
            ->where('id_estudiante', $idPersona)
            ->orderBy('id_modulo');

        if ($moduleId) {
            $query->where('id_modulo', $moduleId);
        }

        $subjects = $query->get()->map(function (Inscripcion $inscripcion): array {
            $assignment = $inscripcion->docenteMateria;

            return [
                'id' => $inscripcion->id_inscripcion,
                'materia' => $assignment?->materia?->nombre,
                'modulo' => $assignment?->modulo?->nombre,
                'fecha_inicio' => $assignment?->modulo?->fecha_inicio,
                'fecha_final' => $assignment?->modulo?->fecha_final,
                'horario' => $assignment?->horario?->nombre,
                'hora_inicio' => $assignment?->horario?->hora_inicio,
                'hora_fin' => $assignment?->horario?->hora_fin,
                'docente' => $assignment?->docente?->nombre,
                'docente_correo' => $assignment?->docente?->correo,
            ];
        });

        return response()->json([
            'data' => [
                'tipo' => 'estudiante',
                'persona' => [
                    'id' => $student->id_estudiante,
                    'nombre' => $student->nombre,
                    'correo' => $student->correo,
                ],
                'materias' => $subjects,
            ],
        ]);
    }

    public function headTeacherAvailability(Request $request, int $idDocente): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');

        $teacher = Docente::query()
            ->where('id_universidad', $portalUser['university_id'])
            ->where('id_docente', $idDocente)
            ->first();

        if (!$teacher) {
            return response()->json([
                'message' => 'Docente no encontrado en tu universidad.',
            ], 404);
        }

        $busySlots = DocenteMateria::query()
            ->with(['materia:id_materia,nombre', 'horario:id_horario,nombre,hora_inicio,hora_fin', 'modulo:id_modulo,nombre,fecha_inicio,fecha_final'])
            ->where('id_docente', $idDocente)
            ->orderBy('id_modulo')
            ->orderBy('id_horario')
            ->get();

        $preferredSlots = HorasLibresDoc::query()
            ->with(['horario:id_horario,nombre,hora_inicio,hora_fin', 'modulo:id_modulo,nombre,fecha_inicio,fecha_final'])
            ->where('id_docente', $idDocente)
            ->orderBy('id_modulo')
            ->orderBy('id_horario')
            ->get();

        return response()->json([
            'data' => [
                'docente' => [
                    'id_docente' => $teacher->id_docente,
                    'nombre' => $teacher->nombre,
                    'correo' => $teacher->correo,
                ],
                'ocupados' => $busySlots->map(function (DocenteMateria $slot): array {
                    return [
                        'id_dm' => $slot->id_dm,
                        'materia' => $slot->materia?->nombre,
                        'modulo' => $slot->modulo?->nombre,
                        'fecha_inicio' => $slot->modulo?->fecha_inicio,
                        'fecha_final' => $slot->modulo?->fecha_final,
                        'horario' => $slot->horario?->nombre,
                        'hora_inicio' => $slot->horario?->hora_inicio,
                        'hora_fin' => $slot->horario?->hora_fin,
                    ];
                }),
                'preferencias' => $preferredSlots->map(function (HorasLibresDoc $slot): array {
                    return [
                        'id_hld' => $slot->id_hld,
                        'modulo' => $slot->modulo?->nombre,
                        'fecha_inicio' => $slot->modulo?->fecha_inicio,
                        'fecha_final' => $slot->modulo?->fecha_final,
                        'horario' => $slot->horario?->nombre,
                        'hora_inicio' => $slot->horario?->hora_inicio,
                        'hora_fin' => $slot->horario?->hora_fin,
                    ];
                }),
            ],
        ]);
    }

    public function headCreateMateria(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:120', 'unique:materia,nombre'],
        ]);

        $materia = Materia::query()->create([
            'nombre' => trim($data['nombre']),
        ]);

        return response()->json([
            'message' => 'Materia creada correctamente.',
            'data' => $materia,
        ], 201);
    }

    public function headAssignMateria(Request $request): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');

        $data = $request->validate([
            'id_materia' => ['required', 'integer', 'exists:materia,id_materia'],
            'id_docente' => ['required', 'integer', 'exists:docente,id_docente'],
            'id_horario' => ['required', 'integer', 'exists:horario,id_horario'],
            'id_modulo' => ['required', 'integer', 'exists:modulo,id_modulo'],
        ]);

        $teacher = Docente::query()
            ->where('id_docente', $data['id_docente'])
            ->where('id_universidad', $portalUser['university_id'])
            ->first();

        if (!$teacher) {
            return response()->json([
                'message' => 'Solo puedes asignar materias a docentes de tu universidad.',
            ], 422);
        }

        $slotInUse = DocenteMateria::query()
            ->where('id_docente', $data['id_docente'])
            ->where('id_modulo', $data['id_modulo'])
            ->where('id_horario', $data['id_horario'])
            ->exists();

        if ($slotInUse) {
            return response()->json([
                'message' => 'El docente ya tiene un horario ocupado en ese modulo.',
            ], 422);
        }

        $duplicateAssignment = DocenteMateria::query()
            ->where('id_docente', $data['id_docente'])
            ->where('id_materia', $data['id_materia'])
            ->where('id_modulo', $data['id_modulo'])
            ->exists();

        if ($duplicateAssignment) {
            return response()->json([
                'message' => 'La materia ya fue asignada a este docente en ese modulo.',
            ], 422);
        }

        $assignment = DocenteMateria::query()->create($data);
        $assignment->load(['materia:id_materia,nombre', 'docente:id_docente,nombre,correo', 'horario:id_horario,nombre,hora_inicio,hora_fin', 'modulo:id_modulo,nombre,fecha_inicio,fecha_final']);

        return response()->json([
            'message' => 'Materia asignada correctamente.',
            'data' => $assignment,
        ], 201);
    }

    private function storePortalSession(Request $request, array $payload): void
    {
        $request->session()->regenerate();
        $request->session()->put('portal_user', $payload);
    }
}
