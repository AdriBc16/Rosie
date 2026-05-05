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
                    'nombre'       => $a->modulo?->nombre,
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
}
