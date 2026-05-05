<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\HorarioGenerado;
use App\Models\DocenteMateria;
use App\Models\DetalleHorario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstudianteController extends Controller
{
    public function generateSchedule(Request $request): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');
        $idEstudiante = $portalUser['id'];

        $inscripciones = Inscripcion::query()
            ->with(['materia', 'modulo'])
            ->where('id_estudiante', $idEstudiante)
            ->whereIn('estado', ['cursando', 'pendiente'])
            ->get();

        if ($inscripciones->isEmpty()) {
            return response()->json(['message' => 'No tienes materias inscritas para generar horario.'], 422);
        }

        $totalCredits = $inscripciones->sum(fn($i) => $i->modulo?->creditos ?? 0);
        if ($totalCredits > 29) {
            return response()->json(['message' => "Excediste el límite de 29 créditos (Total: $totalCredits). No se puede generar el horario."], 422);
        }

        return DB::transaction(function() use ($idEstudiante, $inscripciones) {
            HorarioGenerado::where('id_estudiante', $idEstudiante)->delete();

            $horario = HorarioGenerado::create([
                'id_estudiante' => $idEstudiante,
                'id_modulo' => $inscripciones->first()->id_modulo, 
                'estado' => 'confirmado',
                'fecha_generacion' => now()
            ]);

            $bloquesOcupados = [];

            foreach ($inscripciones as $i) {
                $asignacion = DocenteMateria::where('id_materia', $i->id_materia)
                    ->where('id_modulo', $i->id_modulo)
                    ->first();

                if ($asignacion && $asignacion->id_bloque) {
                    $key = "{$asignacion->id_bloque}_{$asignacion->id_modulo}";
                    
                    if (isset($bloquesOcupados[$key])) {
                        $materiaChoque = $bloquesOcupados[$key];
                        throw new \Exception("Colisión detectada: Las materias '{$i->materia->nombre}' y '{$materiaChoque}' coinciden en el mismo bloque y módulo.");
                    }

                    $bloquesOcupados[$key] = $i->materia->nombre;

                    DetalleHorario::create([
                        'id_horario' => $horario->id_horario,
                        'id_materia' => $i->id_materia,
                        'id_docente' => $asignacion->id_docente,
                        'id_bloque' => $asignacion->id_bloque,
                        'id_aula' => $asignacion->id_aula
                    ]);
                }
            }

            return response()->json(['message' => 'Horario generado con éxito, sincronizando múltiples módulos.']);
        });
    }
}
