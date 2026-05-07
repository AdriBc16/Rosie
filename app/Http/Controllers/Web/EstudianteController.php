<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DetalleHorario;
use App\Models\DocenteMateria;
use App\Models\HistorialMateria;
use App\Models\HorarioGenerado;
use App\Models\Inscripcion;
use App\Models\Modulo;
use App\Models\Prerequisito;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EstudianteController extends Controller
{
    private const CREDIT_LIMIT = 40;
    private const TARGET_MATERIAS_POR_MODULO = 2;
    private const MAX_MATERIAS_POR_MODULO = 3;

    public function jefeGenerateScheduleForStudent(Request $request): JsonResponse
    {
        $request->validate([
            'id_estudiante' => 'required|integer|exists:estudiantes,id_estudiante',
        ]);
        $idEstudiante = (int) $request->id_estudiante;

        $inscripciones = Inscripcion::query()
            ->with(['materia', 'modulo'])
            ->where('id_estudiante', $idEstudiante)
            ->whereIn('estado', ['cursando', 'pendiente'])
            ->get();

        if ($inscripciones->isEmpty()) {
            return response()->json(['message' => 'El estudiante no tiene materias inscritas para generar horario.'], 422);
        }

        $totalCredits = $inscripciones->sum(fn ($i) => $i->modulo?->creditos ?? 0);
        if ($totalCredits > self::CREDIT_LIMIT) {
            return response()->json(['message' => 'El estudiante excede el limite de creditos.'], 422);
        }

        return DB::transaction(function () use ($idEstudiante, $inscripciones) {
            HorarioGenerado::where('id_estudiante', $idEstudiante)->delete();

            $horario = HorarioGenerado::create([
                'id_estudiante' => $idEstudiante,
                'id_modulo' => $inscripciones->first()->id_modulo,
                'estado' => 'confirmado',
                'fecha_generacion' => now(),
            ]);

            $bloquesOcupados = [];

            foreach ($inscripciones as $i) {
                $asignacion = DocenteMateria::where('id_materia', $i->id_materia)
                    ->where('id_modulo', $i->id_modulo)
                    ->first();

                if ($asignacion && $asignacion->id_bloque) {
                    $key = "{$asignacion->id_bloque}_{$asignacion->id_modulo}";
                    if (isset($bloquesOcupados[$key])) {
                        throw new \Exception('Colision de bloque en horario.');
                    }
                    $bloquesOcupados[$key] = true;

                    DetalleHorario::create([
                        'id_horario' => $horario->id_horario,
                        'id_materia' => $i->id_materia,
                        'id_docente' => $asignacion->id_docente,
                        'id_bloque' => $asignacion->id_bloque,
                        'id_aula' => $asignacion->id_aula,
                    ]);
                }
            }

            return response()->json(['message' => 'Horario del estudiante generado correctamente.', 'id_horario' => $horario->id_horario]);
        });
    }

    public function generateSchedule(Request $request): JsonResponse
    {
        // Mantenido por compatibilidad. El flujo nuevo usa sugerencias.
        return response()->json(['message' => 'Usa el endpoint de sugerencias de horario.'], 200);
    }

    public function suggestSchedules(Request $request): JsonResponse
    {
        $request->validate([
            'id_modulo' => 'nullable|integer|exists:modulos,id_modulo',
        ]);

        $portalUser = $request->session()->get('portal_user');
        $idEstudiante = (int) $portalUser['id'];
        $requestedModulo = $request->integer('id_modulo');
        $activeModulo = $this->getActiveModuloId();
        $baseModuloId = (int) ($requestedModulo ?: $activeModulo);

        $approvedIds = $this->approvedMateriaIds($idEstudiante);
        $prereqByMateria = Prerequisito::query()
            ->get(['id_materia', 'id_materia_prerrequisito'])
            ->groupBy('id_materia')
            ->map(fn (Collection $rows) => $rows->pluck('id_materia_prerrequisito')->map(fn ($id) => (int) $id)->all());

        $offersAll = DocenteMateria::query()
            ->with([
                'materia:id_materia,nombre',
                'docente:id_docente,nombre,apellido',
                'bloque:id_bloque,nombre,hora_inicio,hora_fin',
                'aula:id_aula,nombre',
                'modulo:id_modulo,nombre,numero_en_semestre,fecha_inicio,fecha_final,id_semestre',
            ])
            ->whereNotNull('id_bloque')
            ->get();

        if ($offersAll->isEmpty()) {
            return response()->json([
                'data' => [
                    'id_semestre' => null,
                    'target_materias_por_modulo' => self::TARGET_MATERIAS_POR_MODULO,
                    'max_materias_por_modulo' => self::MAX_MATERIAS_POR_MODULO,
                    'modulos' => [],
                ],
            ]);
        }

        $currentYear = (int) now()->format('Y');
        $currentMonth = (int) now()->format('n');
        $isFirstCalendarSemester = $currentMonth <= 7;
        $periodLabel = $isFirstCalendarSemester ? 'enero-julio' : 'julio-diciembre';
        $windowStart = $isFirstCalendarSemester ? "{$currentYear}-01-01" : "{$currentYear}-07-01";
        $windowEnd = $isFirstCalendarSemester ? "{$currentYear}-07-31" : "{$currentYear}-12-31";

        $semModuloIds = Modulo::query()
            ->whereBetween('fecha_inicio', [$windowStart, $windowEnd])
            ->orderBy('fecha_inicio')
            ->orderBy('numero_en_semestre')
            ->limit(3)
            ->pluck('id_modulo')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Fallback: mismo semestre calendario en otros anios (si en el actual no hay 3 modulos).
        if (count($semModuloIds) < 3) {
            $fallback = Modulo::query()
                ->when($isFirstCalendarSemester, function ($q) {
                    $q->whereMonth('fecha_inicio', '>=', 1)->whereMonth('fecha_inicio', '<=', 7);
                }, function ($q) {
                    $q->whereMonth('fecha_inicio', '>=', 7)->whereMonth('fecha_inicio', '<=', 12);
                })
                ->orderByRaw('ABS(YEAR(fecha_inicio) - ?)', [$currentYear])
                ->orderBy('fecha_inicio')
                ->orderBy('numero_en_semestre')
                ->limit(3)
                ->pluck('id_modulo')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (!empty($fallback)) {
                $semModuloIds = $fallback;
            }
        }

        if (empty($semModuloIds)) {
            return response()->json([
                'data' => [
                    'id_semestre' => null,
                    'periodo' => $periodLabel,
                    'target_materias_por_modulo' => self::TARGET_MATERIAS_POR_MODULO,
                    'max_materias_por_modulo' => self::MAX_MATERIAS_POR_MODULO,
                    'modulos' => [],
                ],
            ]);
        }

        $modulosPayload = [];
        foreach ($semModuloIds as $idModulo) {
            $modulosPayload[] = $this->buildModuloSuggestion(
                $idEstudiante,
                $idModulo,
                $offersAll->where('id_modulo', $idModulo)->values(),
                $approvedIds,
                $prereqByMateria
            );
        }

        return response()->json([
            'data' => [
                'id_semestre' => null,
                'periodo' => $periodLabel,
                'target_materias_por_modulo' => self::TARGET_MATERIAS_POR_MODULO,
                'max_materias_por_modulo' => self::MAX_MATERIAS_POR_MODULO,
                'modulos' => $modulosPayload,
            ],
        ]);
    }

    private function buildModuloSuggestion(int $idEstudiante, int $idModulo, Collection $offers, array $approvedIds, Collection $prereqByMateria): array
    {
        $moduloInfo = Modulo::query()->find($idModulo, ['id_modulo', 'nombre', 'numero_en_semestre', 'fecha_inicio', 'fecha_final']);

        $alreadyEnrolled = Inscripcion::query()
            ->where('id_estudiante', $idEstudiante)
            ->where('id_modulo', $idModulo)
            ->pluck('id_materia')
            ->map(fn ($id) => (int) $id)
            ->all();
        $alreadySet = array_fill_keys($alreadyEnrolled, true);

        $eligibleMateriaIds = $offers
            ->pluck('id_materia')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter(function (int $idMateria) use ($approvedIds, $prereqByMateria, $alreadySet): bool {
                if (isset($alreadySet[$idMateria])) {
                    return false;
                }
                $prereqs = $prereqByMateria[$idMateria] ?? [];
                foreach ($prereqs as $idReq) {
                    if (!isset($approvedIds[$idReq])) {
                        return false;
                    }
                }
                return true;
            })
            ->values()
            ->all();

        // Fallback operativo: si no hay elegibles estrictos, usar oferta del modulo
        // para evitar sugerencias vacias mientras se completan prerrequisitos/historial.
        if (count($eligibleMateriaIds) === 0) {
            $eligibleMateriaIds = $offers
                ->pluck('id_materia')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $eligibleSet = array_fill_keys($eligibleMateriaIds, true);
        $offersByMateria = $offers
            ->filter(fn (DocenteMateria $o) => isset($eligibleSet[(int) $o->id_materia]))
            ->groupBy('id_materia')
            ->map(fn (Collection $g) => $g->sortBy(fn (DocenteMateria $o) => (int) $o->id_bloque)->values());

        $subjects = $offersByMateria
            ->map(fn (Collection $list, $idMateria) => ['id_materia' => (int) $idMateria, 'offers' => $list, 'count' => $list->count()])
            ->sortBy('count')
            ->values();

        $picked = [];
        $usedBlocks = [];

        foreach ($subjects as $subject) {
            if (count($picked) >= self::TARGET_MATERIAS_POR_MODULO) {
                break;
            }

            /** @var Collection<int,DocenteMateria> $subjectOffers */
            $subjectOffers = $subject['offers'];
            foreach ($subjectOffers as $offer) {
                $idBloque = (int) $offer->id_bloque;
                if (isset($usedBlocks[$idBloque])) {
                    continue;
                }

                $usedBlocks[$idBloque] = true;
                $picked[] = [
                    'id_dm' => (int) $offer->id_dm,
                    'materia' => [
                        'id_materia' => (int) $offer->id_materia,
                        'nombre' => $offer->materia?->nombre,
                    ],
                    'docente' => [
                        'id_docente' => (int) $offer->id_docente,
                        'nombre' => trim(($offer->docente?->nombre ?? '').' '.($offer->docente?->apellido ?? '')),
                    ],
                    'bloque' => [
                        'id_bloque' => $idBloque,
                        'nombre' => $offer->bloque?->nombre,
                        'hora_inicio' => $offer->bloque?->hora_inicio,
                        'hora_fin' => $offer->bloque?->hora_fin,
                    ],
                    'aula' => [
                        'id_aula' => (int) ($offer->id_aula ?? 0),
                        'nombre' => $offer->aula?->nombre,
                    ],
                ];
                break;
            }
        }

        return [
            'modulo' => [
                'id_modulo' => (int) ($moduloInfo->id_modulo ?? $idModulo),
                'nombre' => $moduloInfo->nombre ?? null,
                'numero_en_semestre' => (int) ($moduloInfo->numero_en_semestre ?? 0),
                'fecha_inicio' => $moduloInfo->fecha_inicio ?? null,
                'fecha_final' => $moduloInfo->fecha_final ?? null,
            ],
            'eligible_count' => count($eligibleMateriaIds),
            'suggested_count' => count($picked),
            'items' => $picked,
        ];
    }

    private function approvedMateriaIds(int $idEstudiante): array
    {
        $approvedFromInscripciones = Inscripcion::query()
            ->where('id_estudiante', $idEstudiante)
            ->where('estado', 'aprobada')
            ->pluck('id_materia')
            ->map(fn ($id) => (int) $id)
            ->all();

        $approvedFromHistorial = HistorialMateria::query()
            ->where('id_estudiante', $idEstudiante)
            ->where('convalidada', true)
            ->pluck('id_materia')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_fill_keys(array_unique(array_merge($approvedFromInscripciones, $approvedFromHistorial)), true);
    }

    private function getActiveModuloId(): ?int
    {
        $modulo = Modulo::where('fecha_inicio', '<=', now())
            ->where('fecha_final', '>=', now())
            ->first();

        if ($modulo) {
            return (int) $modulo->id_modulo;
        }

        $modulo = Modulo::where('fecha_inicio', '>', now())
            ->orderBy('fecha_inicio', 'asc')
            ->first();

        if ($modulo) {
            return (int) $modulo->id_modulo;
        }

        $modulo = Modulo::orderBy('fecha_final', 'desc')->first();
        return $modulo ? (int) $modulo->id_modulo : null;
    }
}
