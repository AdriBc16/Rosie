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

        $materiasSemanales = ['English Beginners', 'English Intermediate', 'English High Intermediate', 'English Advanced'];

        // Resolver oferta para cada inscripción; las materias semestrales tienen una oferta por módulo
        $inscList = $inscripciones->flatMap(function ($insc) use ($materiasSemanales) {
            $ofertas = DocenteMateria::where('id_materia', $insc->id_materia)->get();
            if ($ofertas->isEmpty()) return [];
            // Materias semestrales: incluir todas las ofertas (una por módulo)
            if (in_array($insc->materia->nombre, $materiasSemanales)) {
                return $ofertas->map(fn ($o) => ['insc' => $insc, 'oferta' => $o])->all();
            }
            return [['insc' => $insc, 'oferta' => $ofertas->first()]];
        })->values();

        // Separar en fijas (ya tienen módulo y bloque asignados) y libres
        $fijas = $inscList->filter(fn ($item) => $item['oferta']->id_modulo && $item['oferta']->id_bloque);
        $libres = $inscList->filter(fn ($item) => !$item['oferta']->id_modulo || !$item['oferta']->id_bloque)->shuffle($seed);

        // Primero procesar las fijas para reservar sus slots
        foreach ($fijas as $item) {
            $oferta = $item['oferta'];
            // Solo agregar si el slot no está ya ocupado (evitar duplicados de datos)
            if (!isset($usedSlots[$oferta->id_modulo][$oferta->id_bloque])) {
                $entry = $this->formatScheduleItem($oferta);
                $entry['fijo'] = true;
                $schedule[] = $entry;
                $usedSlots[$oferta->id_modulo][$oferta->id_bloque] = true;
            }
        }

        // Luego asignar las libres evitando slots ya ocupados
        $moduloIndex = 0;
        foreach ($libres->values() as $idx => $item) {
            $insc = $item['insc'];
            $oferta = $item['oferta'];
            $targetModulo = $modulos[$moduloIndex % $modulos->count()];

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

    public function headGetDocentesConMaterias(Request $request): JsonResponse
    {
        $docentes = \App\Models\Docente::where('es_jefe_carrera', false)
            ->with(['materias' => function($q) {
                $q->with(['modulo:id_modulo,nombre', 'aula:id_aula,nombre', 'bloque:id_bloque,nombre,hora_inicio,hora_fin', 'docente:id_docente,nombre,apellido']);
            }])
            ->orderBy('nombre')
            ->get();

        $result = $docentes->map(function($docente) {
            $materiasAgrupadas = [];
            foreach ($docente->materias as $dm) {
                $keyMateria = $dm->id_materia;
                if (!isset($materiasAgrupadas[$keyMateria])) {
                    $materiasAgrupadas[$keyMateria] = [
                        'id_materia' => $dm->id_materia,
                        'nombre' => $dm->materia?->nombre,
                        'creditos' => $dm->materia?->creditos,
                        'modulos' => [],
                        'estudiantes' => []
                    ];
                }

                $moduloInfo = [
                    'id_modulo' => $dm->id_modulo,
                    'nombre' => $dm->modulo?->nombre,
                    'aula' => $dm->aula?->nombre,
                    'bloque' => $dm->bloque?->nombre,
                    'horario' => $dm->bloque ? (substr($dm->bloque->hora_inicio, 0, 5) . ' - ' . substr($dm->bloque->hora_fin, 0, 5)) : null
                ];

                if (!in_array($moduloInfo, $materiasAgrupadas[$keyMateria]['modulos'])) {
                    $materiasAgrupadas[$keyMateria]['modulos'][] = $moduloInfo;
                }
            }

            // Obtener estudiantes inscritos en las materias de este docente
            $inscripciones = Inscripcion::whereIn('id_materia', array_keys($materiasAgrupadas))
                ->with(['estudiante:id_estudiante,nombre,apellido,correo'])
                ->get();

            foreach ($inscripciones as $insc) {
                if (isset($materiasAgrupadas[$insc->id_materia])) {
                    $estudianteData = [
                        'id_estudiante' => $insc->estudiante->id_estudiante,
                        'nombre' => $insc->estudiante->nombre,
                        'apellido' => $insc->estudiante->apellido,
                        'correo' => $insc->estudiante->correo,
                        'estado' => $insc->estado
                    ];

                    $found = false;
                    foreach ($materiasAgrupadas[$insc->id_materia]['estudiantes'] as $est) {
                        if ($est['id_estudiante'] === $estudianteData['id_estudiante']) {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $materiasAgrupadas[$insc->id_materia]['estudiantes'][] = $estudianteData;
                    }
                }
            }

            return [
                'id_docente' => $docente->id_docente,
                'nombre' => $docente->nombre,
                'apellido' => $docente->apellido,
                'correo' => $docente->correo,
                'materias' => array_values($materiasAgrupadas)
            ];
        });

        return response()->json(['data' => $result]);
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
