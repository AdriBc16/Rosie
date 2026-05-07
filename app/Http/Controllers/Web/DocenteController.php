<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DocenteMateria;
use App\Models\Inscripcion;
use App\Models\BloqueHorario;
use App\Models\DisponibilidadDocente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocenteController extends Controller
{
    public function teacherAssignments(Request $request): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');

        // Consulta ultra-simplificada para diagnosticar
        $assignments = DocenteMateria::query()
            ->with([
                'materia',
                'modulo',
                'bloque',
                'aula'
            ])
            ->where('id_docente', $portalUser['id'])
            ->get();

        return response()->json([
            'data' => $assignments->map(fn (DocenteMateria $a) => [
                'id_dm'              => $a->id_dm,
                'materia'            => [
                    'id' => $a->materia?->id_materia, 
                    'nombre' => $a->materia?->nombre ?? "Materia #{$a->id_materia}"
                ],
                'modulo'             => $a->id_modulo ? [
                    'id'           => $a->modulo?->id_modulo,
                    'nombre'       => $a->modulo?->nombre ?? "Módulo {$a->id_modulo}",
                    'fecha_inicio' => $a->modulo?->fecha_inicio,
                    'fecha_final'  => $a->modulo?->fecha_final,
                    'creditos'     => $a->modulo?->creditos,
                ] : [
                    'id'           => null,
                    'nombre'       => 'Por definir (elección estudiante)',
                    'fecha_inicio' => null,
                    'fecha_final'  => null,
                    'creditos'     => null,
                ],

                'bloque'             => $a->bloque?->nombre ?? 'Por definir',
                'aula'               => $a->aula?->nombre ?? 'Sin aula',
                'estudiantes_count'  => Inscripcion::where('id_materia', $a->id_materia)->count(),
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
        $idModulo = $request->query('id_modulo');

        $bloques = BloqueHorario::orderBy('orden')->get();
        $modulos = \App\Models\Modulo::with('semestre')->orderBy('fecha_inicio')->get();

        if (!$idModulo && $modulos->isNotEmpty()) {
            $active = \App\Models\Modulo::where('fecha_inicio', '<=', now())
                ->where('fecha_final', '>=', now())
                ->first();
            $idModulo = $active ? $active->id_modulo : $modulos->first()->id_modulo;
        }

        $misBloques = DisponibilidadDocente::where('id_docente', $portalUser['id'])
            ->when($idModulo, fn($q) => $q->where('id_modulo', $idModulo))
            ->pluck('id_bloque')
            ->toArray();

        return response()->json([
            'bloques' => $bloques,
            'misBloques' => $misBloques,
            'modulos' => $modulos,
            'id_modulo_activo' => $idModulo
        ]);
    }

    public function saveDisponibilidad(Request $request): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');
        $request->validate([
            'id_modulo' => 'required|exists:modulos,id_modulo',
            'bloques' => 'array',
            'bloques.*' => 'exists:bloques_horarios,id_bloque'
        ]);

        DB::transaction(function() use ($portalUser, $request) {
            DisponibilidadDocente::where('id_docente', $portalUser['id'])
                ->where('id_modulo', $request->id_modulo)
                ->delete();
                
            foreach ($request->bloques as $idBloque) {
                DisponibilidadDocente::create([
                    'id_docente' => $portalUser['id'],
                    'id_modulo' => $request->id_modulo,
                    'id_bloque' => $idBloque
                ]);
            }
        });

        return response()->json(['message' => 'Disponibilidad guardada correctamente.']);
    }
}
