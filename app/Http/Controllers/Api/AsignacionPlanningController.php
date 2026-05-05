<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AsignacionSugeridaRequest;
use App\Http\Requests\InscripcionCohorteRequest;
use App\Models\DisponibilidadDocente;
use App\Models\DocenteMateria;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\Materia;
use Illuminate\Http\JsonResponse;

class AsignacionPlanningController extends Controller
{
    public function sugeridas(AsignacionSugeridaRequest $request): JsonResponse
    {
        $data = $request->validated();

        $materia = Materia::query()
            ->with(['carrera'])
            ->where('id_materia', $data['id_materia'])
            ->where('id_carrera', $data['id_carrera'])
            ->where('id_semestre', $data['id_semestre'])
            ->firstOrFail();

        $disponibilidades = DisponibilidadDocente::query()
            ->with(['docente.carreras'])
            ->get()
            ->filter(function (DisponibilidadDocente $slot) use ($data): bool {
                $docente = $slot->docente;
                if (!$docente) {
                    return false;
                }

                return $docente->carreras->contains('id_carrera', (int) $data['id_carrera']);
            })
            ->values();

        $ocupadas = DocenteMateria::query()
            ->with(['horario'])
            ->where('id_modulo', $data['id_modulo'])
            ->get();

        $docentes = $disponibilidades
            ->groupBy('id_docente')
            ->map(function ($slots, $idDocente) use ($ocupadas): array {
                $docente = $slots->first()->docente;

                $slotsPayload = $slots
                    ->sortBy(['dia_semana', 'hora_inicio'])
                    ->values()
                    ->map(function (DisponibilidadDocente $slot, int $index) use ($ocupadas, $idDocente): array {
                        $label = chr(65 + $index);

                        $busy = $ocupadas->contains(function (DocenteMateria $asignacion) use ($slot, $idDocente): bool {
                            if ((int) $asignacion->id_docente !== (int) $idDocente) {
                                return false;
                            }

                            $inicioA = (string) $slot->hora_inicio;
                            $finA = (string) $slot->hora_fin;
                            $inicioB = (string) ($asignacion->horario->hora_inicio ?? '00:00:00');
                            $finB = (string) ($asignacion->horario->hora_fin ?? '00:00:00');

                            return $inicioA < $finB && $finA > $inicioB;
                        });

                        return [
                            'label' => $label,
                            'dia_semana' => (int) $slot->dia_semana,
                            'hora_inicio' => (string) $slot->hora_inicio,
                            'hora_fin' => (string) $slot->hora_fin,
                            'ocupado' => $busy,
                        ];
                    });

                return [
                    'id_docente' => (int) $docente->id_docente,
                    'nombre' => $docente->nombre,
                    'apellido' => $docente->apellido,
                    'descripcion' => $docente->descripcion,
                    'slots' => $slotsPayload,
                ];
            })
            ->values();

        return response()->json([
            'contexto' => [
                'id_carrera' => (int) $data['id_carrera'],
                'id_semestre' => (int) $data['id_semestre'],
                'id_modulo' => (int) $data['id_modulo'],
                'id_materia' => (int) $data['id_materia'],
                'materia' => $materia->nombre,
            ],
            'docentes' => $docentes,
        ]);
    }

    public function inscribirCohorte(InscripcionCohorteRequest $request): JsonResponse
    {
        $data = $request->validated();

        $materia = Materia::query()
            ->where('id_materia', $data['id_materia'])
            ->where('id_carrera', $data['id_carrera'])
            ->where('id_semestre', $data['id_semestre'])
            ->firstOrFail();

        $asignacion = DocenteMateria::query()
            ->where('id_modulo', $data['id_modulo'])
            ->where('id_materia', $materia->id_materia)
            ->first();

        if (!$asignacion) {
            return response()->json([
                'message' => 'No existe una asignacion docente-materia para este modulo.',
            ], 422);
        }

        $estudiantes = Estudiante::query()
            ->where('id_modulo', $data['id_modulo'])
            ->where('id_universidad', function ($query) use ($data) {
                $query->from('carrera')
                    ->select('id_universidad')
                    ->where('id_carrera', $data['id_carrera'])
                    ->limit(1);
            })
            ->get();

        $estado = $data['estado'] ?? 'pendiente';
        $created = 0;
        $skipped = 0;

        foreach ($estudiantes as $estudiante) {
            $exists = Inscripcion::query()
                ->where('id_estudiante', $estudiante->id_estudiante)
                ->where('id_modulo', $data['id_modulo'])
                ->where('id_materia', $materia->id_materia)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            Inscripcion::query()->create([
                'id_estudiante' => $estudiante->id_estudiante,
                'id_modulo' => $data['id_modulo'],
                'id_dm' => $asignacion->id_dm,
                'id_materia' => $materia->id_materia,
                'estado' => $estado,
                'intentos' => 1,
                'fecha_inscripcion' => now(),
            ]);

            $created++;
        }

        return response()->json([
            'message' => 'Proceso de inscripcion de cohorte completado.',
            'creadas' => $created,
            'omitidas' => $skipped,
            'total_evaluadas' => $estudiantes->count(),
        ]);
    }
}
