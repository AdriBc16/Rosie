<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DetalleHorario;
use App\Models\DocenteMateria;
use App\Models\DisponibilidadDocente;
use App\Models\HistorialMateria;
use App\Models\HorarioGenerado;
use App\Models\Inscripcion;
use App\Models\Modulo;
use App\Models\Prerequisito;
use App\Models\BloqueHorario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EstudianteController extends Controller
{
    private const CREDIT_LIMIT = 29;
    private const TARGET_MATERIAS_POR_MODULO = 2;
    private const MAX_MATERIAS_POR_MODULO = 2;

    public function suggestSchedules(Request $request): JsonResponse
    {
        $portalUser = $request->session()->get('portal_user');
        $idEstudiante = (int) $portalUser['id'];

        // 1. Obtener materias en las que el alumno está inscrito (estado pendiente/cursando)
        $inscripciones = Inscripcion::query()
            ->with(['materia'])
            ->where('id_estudiante', $idEstudiante)
            ->whereIn('estado', ['pendiente', 'cursando'])
            ->get();

        if ($inscripciones->isEmpty()) {
            return response()->json(['message' => 'No tienes materias inscritas.'], 422);
        }

        // 2. Obtener los 3 módulos del semestre más reciente
        // Primero buscar el semestre activo o el más reciente
        $semestre = \App\Models\Semestre::where('fecha_inicio', '<=', now())
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        if (!$semestre) {
            $semestre = \App\Models\Semestre::orderBy('fecha_inicio', 'desc')->first();
        }

        if (!$semestre) {
            return response()->json(['message' => 'No hay semestres configurados.'], 422);
        }

        $modulos = Modulo::where('id_semestre', $semestre->id_semestre)
            ->orderBy('fecha_inicio')
            ->get();

        if ($modulos->count() < 1) {
            return response()->json(['message' => 'No se encontraron módulos configurados para el semestre activo.'], 422);
        }

        // 3. Generar 3 opciones diferentes
        $opciones = [];
        for ($i = 1; $i <= 3; $i++) {
            $opciones[] = $this->generateOneOption($idEstudiante, $inscripciones, $modulos, $i);
        }

        return response()->json([
            'data' => [
                'opciones' => $opciones,
                'credit_limit' => self::CREDIT_LIMIT
            ]
        ]);
    }

    private function generateOneOption(int $idEstudiante, Collection $inscripciones, Collection $modulos, int $seed): array
    {
        $schedule = [];
        $usedSlots = []; // modulo_id => [bloque_id => true]
        
        // Barajar materias para dar variedad en cada opción si hay múltiples bloques
        $inscList = $inscripciones->shuffle($seed);
        
        // Agrupamos por módulos para intentar balancear 2 materias por módulo
        $moduloIndex = 0;
        foreach ($inscList as $idx => $insc) {
            $targetModulo = $modulos[$moduloIndex % 3];
            
            // Buscar oferta para esta materia
            $oferta = DocenteMateria::where('id_materia', $insc->id_materia)->first();
            if (!$oferta) continue;

            // Si la oferta YA tiene modulo y bloque fijos (porque alguien más ya eligió), debemos respetarlo
            if ($oferta->id_modulo && $oferta->id_bloque) {
                $item = $this->formatScheduleItem($oferta);
                $item['fijo'] = true;
                $schedule[] = $item;
                $usedSlots[$oferta->id_modulo][$oferta->id_bloque] = true;
            } else {
                // Si está libre, buscamos un bloque disponible según la DisponibilidadDocente
                $bloque = $this->findAvailableSlot($oferta->id_docente, $targetModulo->id_modulo, $usedSlots[$targetModulo->id_modulo] ?? [], $seed + $idx);
                
                if ($bloque) {
                    $schedule[] = [
                        'id_materia' => $insc->id_materia,
                        'materia_nombre' => $insc->materia->nombre,
                        'id_docente' => $oferta->id_docente,
                        'docente_nombre' => $oferta->docente?->nombre . ' ' . $oferta->docente?->apellido,
                        'id_modulo' => $targetModulo->id_modulo,
                        'modulo_nombre' => $targetModulo->nombre,
                        'id_bloque' => $bloque->id_bloque,
                        'bloque_nombre' => $bloque->nombre,
                        'bloque_hora' => substr($bloque->hora_inicio, 0, 5) . ' - ' . substr($bloque->hora_fin, 0, 5),
                        'id_aula' => $oferta->id_aula,
                        'aula_nombre' => $oferta->aula?->nombre,
                        'fijo' => false
                    ];
                    $usedSlots[$targetModulo->id_modulo][$bloque->id_bloque] = true;
                }
            }

            if (($idx + 1) % self::TARGET_MATERIAS_POR_MODULO === 0) {
                $moduloIndex++;
            }
        }

        return [
            'id_opcion' => $seed,
            'label' => "Opción " . chr(64 + $seed), // Opción A, B, C
            'items' => $schedule
        ];
    }

    private function findAvailableSlot(int $idDocente, int $idModulo, array $usedInModulo, int $seed): ?BloqueHorario
    {
        // Obtener disponibilidad del docente
        $disponibles = DisponibilidadDocente::where('id_docente', $idDocente)
            ->where('id_modulo', $idModulo)
            ->get();

        if ($disponibles->isEmpty()) return null;

        // Filtrar los que ya estamos usando en esta sugerencia para el alumno
        $validos = $disponibles->filter(fn($d) => !isset($usedInModulo[$d->id_bloque]));

        if ($validos->isEmpty()) return null;

        // Seleccionar uno aleatoriamente según el seed
        $pick = $validos->values()->get($seed % $validos->count());
        return BloqueHorario::find($pick->id_bloque);
    }

    private function formatScheduleItem(DocenteMateria $dm): array
    {
        return [
            'id_materia' => $dm->id_materia,
            'materia_nombre' => $dm->materia->nombre,
            'id_docente' => $dm->id_docente,
            'docente_nombre' => $dm->docente?->nombre . ' ' . $dm->docente?->apellido,
            'id_modulo' => $dm->id_modulo,
            'modulo_nombre' => $dm->modulo?->nombre,
            'id_bloque' => $dm->id_bloque,
            'bloque_nombre' => $dm->bloque?->nombre,
            'bloque_hora' => substr($dm->bloque?->hora_inicio, 0, 5) . ' - ' . substr($dm->bloque?->hora_fin, 0, 5),
            'id_aula' => $dm->id_aula,
            'aula_nombre' => $dm->aula?->nombre,
        ];
    }

    public function confirmSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id_materia' => 'required|integer',
            'items.*.id_modulo' => 'required|integer',
            'items.*.id_bloque' => 'required|integer',
        ]);

        $portalUser = $request->session()->get('portal_user');
        $idEstudiante = (int) $portalUser['id'];

        return DB::transaction(function () use ($idEstudiante, $request) {
            // 1. Limpiar horario anterior del alumno
            HorarioGenerado::where('id_estudiante', $idEstudiante)->delete();
            
            $horario = HorarioGenerado::create([
                'id_estudiante' => $idEstudiante,
                'id_modulo' => $this->getActiveModuloId(),
                'estado' => 'confirmado',
                'fecha_generacion' => now(),
            ]);

            foreach ($request->items as $item) {
                // 2. Fijar el horario en docente_materias si aún es NULL
                // Esto hace que la primera persona que elija, "gane" el horario para el grupo.
                $oferta = DocenteMateria::where('id_materia', $item['id_materia'])->first();
                if ($oferta && (!$oferta->id_modulo || !$oferta->id_bloque)) {
                    $oferta->update([
                        'id_modulo' => $item['id_modulo'],
                        'id_bloque' => $item['id_bloque']
                    ]);
                }

                // 3. Actualizar la inscripción del alumno
                Inscripcion::where('id_estudiante', $idEstudiante)
                    ->where('id_materia', $item['id_materia'])
                    ->update(['id_modulo' => $item['id_modulo']]);

                // 4. Crear detalle del horario
                DetalleHorario::create([
                    'id_horario' => $horario->id_horario,
                    'id_materia' => $item['id_materia'],
                    'id_docente' => $item['id_docente'],
                    'id_bloque'  => $item['id_bloque'],
                    'id_aula'    => $item['id_aula'],
                ]);
            }

            return response()->json(['message' => 'Horario confirmado con éxito.']);
        });
    }

    private function getActiveModuloId(): ?int
    {
        $modulo = Modulo::where('fecha_inicio', '<=', now())
            ->where('fecha_final', '>=', now())
            ->first();
        if ($modulo) return (int) $modulo->id_modulo;

        $modulo = Modulo::where('fecha_inicio', '>', now())
            ->orderBy('fecha_inicio', 'asc')
            ->first();
        if ($modulo) return (int) $modulo->id_modulo;

        return Modulo::orderBy('fecha_final', 'desc')->first()?->id_modulo;
    }
}
