<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PortalLoginRequest;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\HistorialMateria;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\Modulo;
use App\Models\HorarioGenerado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'nombre'          => 'required|string|max:50',
            'apellido'        => 'required|string|max:50',
            'correo'          => 'required|email|unique:estudiantes,correo',
            'password'        => 'required|string|min:6|confirmed',
            'cohorte_ingreso' => 'nullable|integer|min:2000|max:2100',
            'es_traspaso'     => 'nullable|boolean',
        ]);

        $student = Estudiante::create([
            'nombre'          => trim($request->nombre),
            'apellido'        => trim($request->apellido),
            'correo'          => strtolower(trim($request->correo)),
            'password'        => Hash::make($request->password),
            'cohorte_ingreso' => $request->cohorte_ingreso,
            'es_traspaso'     => (bool) $request->input('es_traspaso', false),
        ]);

        return response()->json([
            'message' => 'Registro exitoso. Ahora puedes iniciar sesion.',
            'user'    => $student
        ], 201);
    }

    public function login(PortalLoginRequest $request): JsonResponse
    {
        // Clear any existing session before creating a new one
        $request->session()->forget('portal_user');

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
                ->with(['materia:id_materia,nombre,creditos', 'modulo:id_modulo,fecha_inicio,fecha_final'])
                ->where('id_estudiante', $portalUser['id'])
                ->whereIn('estado', ['cursando', 'pendiente'])
                ->get();

            $data['inscripciones'] = $inscripciones->map(fn (Inscripcion $i) => [
                'id_inscripcion'   => $i->id_inscripcion,
                'materia'          => $i->materia?->nombre,
                'modulo'           => [
                    'id'           => $i->modulo?->id_modulo,
                    'nombre'       => $i->modulo?->nombre,
                    'fecha_inicio' => $i->modulo?->fecha_inicio,
                    'fecha_final'  => $i->modulo?->fecha_final,
                    'creditos_materia' => $i->materia?->creditos,
                ],
                'estado'           => $i->estado,
                'intentos'         => $i->intentos,
            ]);

            $data['totalCredits'] = $inscripciones->sum(fn ($i) => $i->materia?->creditos ?? 0);
            
            $horarioRaw = HorarioGenerado::query()
                ->with([
                    'detalles.materia',
                    'detalles.docente', 
                    'detalles.bloque', 
                    'detalles.aula'
                ])
                ->where('id_estudiante', $portalUser['id'])
                ->orderBy('fecha_generacion', 'desc')
                ->first();

            if ($horarioRaw) {
                // Devolver en formato plano idéntico a las sugerencias
                $detallesFlat = $horarioRaw->detalles->map(function ($det) use ($portalUser) {
                    // Obtener el módulo desde la inscripción del alumno
                    $insc = Inscripcion::where('id_estudiante', $portalUser['id'])
                        ->where('id_materia', $det->id_materia)
                        ->first();
                    $modulo = $insc && $insc->id_modulo ? Modulo::find($insc->id_modulo) : null;

                    return [
                        'id_materia'     => $det->id_materia,
                        'materia_nombre' => $det->materia?->nombre,
                        'id_docente'     => $det->id_docente,
                        'docente_nombre' => trim(($det->docente?->nombre ?? '') . ' ' . ($det->docente?->apellido ?? '')),
                        'id_modulo'      => $modulo?->id_modulo,
                        'modulo_nombre'  => $modulo?->nombre ?? 'Sin módulo',
                        'id_bloque'      => $det->id_bloque,
                        'bloque_nombre'  => $det->bloque?->nombre,
                        'bloque_hora'    => substr($det->bloque?->hora_inicio ?? '00:00', 0, 5) . ' - ' . substr($det->bloque?->hora_fin ?? '00:00', 0, 5),
                        'id_aula'        => $det->id_aula,
                        'aula_nombre'    => $det->aula?->nombre,
                    ];
                })->values();

                $data['horario'] = [
                    'id_horario' => $horarioRaw->id_horario,
                    'estado'     => $horarioRaw->estado,
                    'items'      => $detallesFlat,
                ];
            } else {
                $data['horario'] = null;
            }
        }

        return response()->json(['data' => $data]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');
        $role = $portalUser['role'];

        $request->validate([
            'nombre'           => 'required|string|max:80',
            'apellido'         => 'nullable|string|max:80',
            'password'         => 'nullable|string|min:6|confirmed',
        ]);

        $nombre   = trim($request->nombre);
        $apellido = trim($request->apellido ?? '');

        if ($role === 'estudiante') {
            $user = Estudiante::findOrFail($portalUser['id']);
            $user->nombre   = $nombre;
            $user->apellido = $apellido ?: null;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->save();

            $this->storePortalSession($request, array_merge($portalUser, [
                'name' => trim("{$user->nombre} {$user->apellido}"),
            ]));
        } else {
            $user = Docente::findOrFail($portalUser['id']);
            $user->nombre   = $nombre;
            $user->apellido = $apellido ?: null;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->save();

            $this->storePortalSession($request, array_merge($portalUser, [
                'name' => trim("{$user->nombre} {$user->apellido}"),
            ]));
        }

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'name'    => trim("{$user->nombre} {$user->apellido}"),
        ]);
    }

    private function storePortalSession(Request $request, array $payload): void
    {
        $request->session()->regenerate();
        $request->session()->put('portal_user', $payload);
    }
}
