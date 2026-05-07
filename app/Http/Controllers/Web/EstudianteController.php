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
    private const DEFAULT_TOP_SUGGESTIONS = 5;
    private const HARD_MAX_SUGGESTIONS = 20;

    public function jefeGenerateScheduleForStudent(Request $request): JsonResponse
    {
        $request->validate([
            'id_estudiante' => 'required|integer|exists:estudiantes,id_estudiante',
        ]);
        $idEstudiante = $request->id_estudiante;

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
            return response()->json(['message' => "El estudiante excede el limite de ".self::CREDIT_LIMIT." creditos (Total: $totalCredits)."], 422);
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
                        throw new \Exception("Colision: '{$i->materia->nombre}' y '{$bloquesOcupados[$key]}' coinciden en el mismo bloque.");
                    }
                    $bloquesOcupados[$key] = $i->materia->nombre;

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

        $totalCredits = $inscripciones->sum(fn ($i) => $i->modulo?->creditos ?? 0);
        if ($totalCredits > self::CREDIT_LIMIT) {
            return response()->json(['message' => "Excediste el limite de ".self::CREDIT_LIMIT." creditos (Total: $totalCredits). No se puede generar el horario."], 422);
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
                        $materiaChoque = $bloquesOcupados[$key];
                        throw new \Exception("Colision detectada: Las materias '{$i->materia->nombre}' y '{$materiaChoque}' coinciden en el mismo bloque y modulo.");
                    }

                    $bloquesOcupados[$key] = $i->materia->nombre;

                    DetalleHorario::create([
                        'id_horario' => $horario->id_horario,
                        'id_materia' => $i->id_materia,
                        'id_docente' => $asignacion->id_docente,
                        'id_bloque' => $asignacion->id_bloque,
                        'id_aula' => $asignacion->id_aula,
                    ]);
                }
            }

            return response()->json(['message' => 'Horario generado con exito, sincronizando multiples modulos.']);
        });
    }

    public function suggestSchedules(Request $request): JsonResponse
    {
        $request->validate([
            'id_modulo' => 'nullable|integer|exists:modulos,id_modulo',
            'top' => 'nullable|integer|min:1|max:20',
        ]);

        $portalUser = $request->session()->get('portal_user');
        $idEstudiante = (int) $portalUser['id'];
        $requestedModulo = $request->integer('id_modulo');
        $activeModulo = $this->getActiveModuloId();
        $idModulo = (int) ($requestedModulo ?: $activeModulo);
        $top = (int) min((int) ($request->integer('top') ?: self::DEFAULT_TOP_SUGGESTIONS), self::HARD_MAX_SUGGESTIONS);

        $approvedIds = $this->approvedMateriaIds($idEstudiante);
        $prereqByMateria = Prerequisito::query()
            ->get(['id_materia', 'id_materia_prerrequisito'])
            ->groupBy('id_materia')
            ->map(fn (Collection $rows) => $rows->pluck('id_materia_prerrequisito')->map(fn ($id) => (int) $id)->all());

        $offersAll = DocenteMateria::query()
            ->with(['materia:id_materia,nombre', 'docente:id_docente,nombre,apellido', 'bloque:id_bloque,nombre,hora_inicio,hora_fin', 'aula:id_aula,nombre', 'modulo:id_modulo,nombre,creditos,fecha_inicio,fecha_final'])
            ->whereNotNull('id_bloque')
            ->get();

        if ($offersAll->isEmpty()) {
            return response()->json([
                'data' => [
                    'id_modulo' => null,
                    'credit_limit' => self::CREDIT_LIMIT,
                    'eligible_materias' => [],
                    'suggestions' => [],
                    'unresolved' => [],
                ],
            ]);
        }

        // Si no envían módulo o el activo no sirve, elegir automáticamente el módulo con más materias habilitadas.
        $candidateModuleIds = $offersAll->pluck('id_modulo')->map(fn ($id) => (int) $id)->unique()->values()->all();
        if ($idModulo) {
            $candidateModuleIds = array_values(array_unique(array_merge([$idModulo], $candidateModuleIds)));
        }

        $bestModulo = null;
        $bestOffers = collect();
        $bestEligibleIds = [];

        foreach ($candidateModuleIds as $candidateModuloId) {
            $offers = $offersAll->where('id_modulo', $candidateModuloId)->values();
            if ($offers->isEmpty()) {
                continue;
            }

            $alreadyEnrolled = Inscripcion::query()
                ->where('id_estudiante', $idEstudiante)
                ->where('id_modulo', $candidateModuloId)
                ->pluck('id_materia')
                ->map(fn ($id) => (int) $id)
                ->all();
            $alreadySet = array_fill_keys($alreadyEnrolled, true);

            $eligibleIds = $offers
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

            if (count($eligibleIds) > count($bestEligibleIds)) {
                $bestModulo = (int) $candidateModuloId;
                $bestOffers = $offers;
                $bestEligibleIds = $eligibleIds;
            }
        }

        $idModulo = $bestModulo;
        $offers = $bestOffers;
        $eligibleMateriaIds = $bestEligibleIds;

        if (!$idModulo || $offers->isEmpty()) {
            return response()->json([
                'data' => [
                    'id_modulo' => null,
                    'credit_limit' => self::CREDIT_LIMIT,
                    'eligible_materias' => [],
                    'suggestions' => [],
                    'unresolved' => [],
                ],
            ]);
        }

        $eligibleSet = array_fill_keys($eligibleMateriaIds, true);
        $eligibleOffers = $offers
            ->filter(fn (DocenteMateria $o) => isset($eligibleSet[(int) $o->id_materia]))
            ->groupBy('id_materia')
            ->map(fn (Collection $group) => $group->sortBy(fn (DocenteMateria $o) => (int) $o->id_bloque)->values())
            ->all();

        $eligibleMaterias = [];
        foreach ($eligibleMateriaIds as $idMateria) {
            $sample = $eligibleOffers[$idMateria][0] ?? null;
            if ($sample && $sample->materia) {
                $eligibleMaterias[] = [
                    'id_materia' => (int) $idMateria,
                    'nombre' => $sample->materia->nombre,
                ];
            }
        }

        $subjects = collect($eligibleOffers)
            ->map(fn (Collection $list, $idMateria) => ['id_materia' => (int) $idMateria, 'offers' => $list, 'count' => $list->count()])
            ->sortBy('count')
            ->values()
            ->all();

        $solutions = [];
        $this->buildScheduleSuggestions($subjects, 0, [], [], 0, $solutions, $top);

        usort($solutions, function (array $a, array $b): int {
            if ($a['subjects_count'] !== $b['subjects_count']) {
                return $b['subjects_count'] <=> $a['subjects_count'];
            }
            if ($a['total_credits'] !== $b['total_credits']) {
                return $b['total_credits'] <=> $a['total_credits'];
            }
            return $a['block_span'] <=> $b['block_span'];
        });

        $unresolved = [];
        foreach ($eligibleMateriaIds as $idMateria) {
            if (!isset($eligibleOffers[$idMateria]) || count($eligibleOffers[$idMateria]) === 0) {
                $unresolved[] = ['id_materia' => $idMateria, 'reason' => 'sin_oferta_en_modulo'];
            }
        }

        return response()->json([
            'data' => [
                'id_modulo' => $idModulo,
                'credit_limit' => self::CREDIT_LIMIT,
                'eligible_materias' => $eligibleMaterias,
                'suggestions' => array_slice($solutions, 0, $top),
                'unresolved' => $unresolved,
            ],
        ]);
    }

    private function buildScheduleSuggestions(array $subjects, int $idx, array $picked, array $usedBlocks, int $totalCredits, array &$solutions, int $top): void
    {
        if (count($solutions) >= $top && $idx >= count($subjects)) {
            return;
        }

        if ($idx >= count($subjects)) {
            if (count($picked) === 0) {
                return;
            }
            $blocks = array_map(fn ($row) => (int) $row['bloque']['id_bloque'], $picked);
            sort($blocks);
            $solutions[] = [
                'subjects_count' => count($picked),
                'total_credits' => $totalCredits,
                'block_span' => ($blocks[count($blocks) - 1] ?? 0) - ($blocks[0] ?? 0),
                'items' => array_values($picked),
            ];
            return;
        }

        $subject = $subjects[$idx];
        /** @var Collection<int,DocenteMateria> $offers */
        $offers = $subject['offers'];

        foreach ($offers as $offer) {
            $idBloque = (int) $offer->id_bloque;
            if (isset($usedBlocks[$idBloque])) {
                continue;
            }

            $creditos = (int) ($offer->modulo?->creditos ?? 0);
            $nextCredits = $totalCredits + $creditos;
            if ($nextCredits > self::CREDIT_LIMIT) {
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
                'modulo' => [
                    'id_modulo' => (int) $offer->id_modulo,
                    'nombre' => $offer->modulo?->nombre,
                    'creditos' => $creditos,
                ],
            ];

            $this->buildScheduleSuggestions($subjects, $idx + 1, $picked, $usedBlocks, $nextCredits, $solutions, $top);
            array_pop($picked);
            unset($usedBlocks[$idBloque]);
        }

        // Permite sugerencias parciales (no todas las materias entran sin conflicto).
        $this->buildScheduleSuggestions($subjects, $idx + 1, $picked, $usedBlocks, $totalCredits, $solutions, $top);
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
