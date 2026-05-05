<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\HorarioExportRequest;
use App\Http\Requests\Api\HorarioIdealRequest;
use App\Models\DocenteMateria;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HorarioPlanningController extends Controller
{
    public function ideal(HorarioIdealRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = DocenteMateria::with(['materia', 'docente', 'horario', 'modulo']);

        if (isset($validated['id_modulo'])) {
            $query->where('id_modulo', $validated['id_modulo']);
        }

        $asignaciones = $query->get();

        $ranking = $asignaciones
            ->groupBy('id_modulo')
            ->map(function ($grupo) {
                $ordenadas = $grupo->sortBy(fn ($item) => $item->horario?->hora_inicio)->values();
                $puentesMinutos = 0;

                for ($i = 1; $i < $ordenadas->count(); $i++) {
                    $previo = $ordenadas[$i - 1]->horario;
                    $actual = $ordenadas[$i]->horario;

                    if (!$previo || !$actual) {
                        continue;
                    }

                    $finPrevio = Carbon::createFromFormat('H:i:s', $previo->hora_fin);
                    $inicioActual = Carbon::createFromFormat('H:i:s', $actual->hora_inicio);

                    if ($inicioActual->greaterThan($finPrevio)) {
                        $puentesMinutos += $finPrevio->diffInMinutes($inicioActual);
                    }
                }

                return [
                    'modulo' => $ordenadas->first()?->modulo,
                    'indice_puentes_minutos' => $puentesMinutos,
                    'nivel' => $this->nivelPuente($puentesMinutos),
                    'asignaciones' => $ordenadas->map(fn ($item) => [
                        'id_dm' => $item->id_dm,
                        'materia' => $item->materia,
                        'docente' => $item->docente,
                        'horario' => $item->horario,
                    ])->values(),
                ];
            })
            ->sortBy('indice_puentes_minutos')
            ->values();

        return response()->json([
            'message' => 'Configuracion ideal generada (estimada por continuidad de bloques horarios).',
            'generated_at' => now()->toDateTimeString(),
            'total_asignaciones' => $asignaciones->count(),
            'recomendacion_ideal' => $ranking->first(),
            'ranking_modulos' => $ranking,
        ]);
    }

    public function exportar(HorarioExportRequest $request): JsonResponse|StreamedResponse
    {
        $validated = $request->validated();

        $query = DocenteMateria::with(['materia', 'docente', 'horario', 'modulo']);

        if (isset($validated['id_modulo'])) {
            $query->where('id_modulo', $validated['id_modulo']);
        }

        $data = $query->get();
        $formato = $validated['formato'] ?? 'json';

        if ($formato === 'csv') {
            return response()->streamDownload(function () use ($data): void {
                $output = fopen('php://output', 'w');
                fputcsv($output, ['id_dm', 'modulo', 'materia', 'docente', 'bloque', 'hora_inicio', 'hora_fin']);

                foreach ($data as $row) {
                    fputcsv($output, [
                        $row->id_dm,
                        $row->modulo?->nombre,
                        $row->materia?->nombre,
                        $row->docente?->nombre,
                        $row->horario?->nombre,
                        $row->horario?->hora_inicio,
                        $row->horario?->hora_fin,
                    ]);
                }

                fclose($output);
            }, 'horarios_export.csv', [
                'Content-Type' => 'text/csv',
            ]);
        }

        return response()->json([
            'generated_at' => now()->toDateTimeString(),
            'total' => $data->count(),
            'data' => $data,
        ]);
    }

    private function nivelPuente(int $puentesMinutos): string
    {
        if ($puentesMinutos <= 30) {
            return 'ideal';
        }

        if ($puentesMinutos <= 120) {
            return 'aceptable';
        }

        return 'alto';
    }
}
