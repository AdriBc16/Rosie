<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Docente;
use App\Models\DocenteMateria;
use App\Models\DisponibilidadDocente;
use App\Models\Materia;
use App\Models\Modulo;
use App\Models\Prerequisito;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MateriaController extends Controller
{
    public function headCreateMateria(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:120',
            'horas_semanales' => 'nullable|integer|min:1|max:20',
            'anio_academico' => 'nullable|integer|min:1|max:10',
            'semestre_academico' => 'nullable|integer|min:1|max:20',
            'prerrequisitos' => 'nullable|array',
            'prerrequisitos.*' => 'integer|exists:materias,id_materia',
        ]);

        $materia = DB::transaction(function () use ($data) {
            $created = Materia::query()->create([
                'nombre' => trim($data['nombre']),
                'horas_semanales' => $data['horas_semanales'] ?? 1,
                'anio_academico' => $data['anio_academico'] ?? 1,
                'semestre_academico' => $data['semestre_academico'] ?? 1,
            ]);

            foreach (($data['prerrequisitos'] ?? []) as $idPrerequisito) {
                Prerequisito::query()->create([
                    'id_materia' => $created->id_materia,
                    'id_materia_prerrequisito' => (int) $idPrerequisito,
                    'descripcion' => null,
                ]);
            }

            return $created;
        });

        return response()->json(['message' => 'Materia creada.', 'data' => $materia], 201);
    }

    public function headAssignMateria(Request $request): JsonResponse
    {
        $request->validate([
            'id_docente' => 'required|integer|exists:docentes,id_docente',
            'id_materia' => 'required|integer|exists:materias,id_materia',
            'id_aula'    => 'required|integer|exists:aulas,id_aula',
            'id_modulo'  => 'required|integer|exists:modulos,id_modulo',
            'id_bloque'  => 'required|integer|exists:bloques_horarios,id_bloque',
        ]);

        $idDocente = $request->id_docente;
        $idMateria = $request->id_materia;
        $idAula    = $request->id_aula;
        $idModulo  = $request->id_modulo;
        $idBloque  = $request->id_bloque;

        // Verificar que el docente tiene disponibilidad en ese módulo y bloque
        $tieneDisponibilidad = DisponibilidadDocente::where('id_docente', $idDocente)
            ->where('id_modulo', $idModulo)
            ->where('id_bloque', $idBloque)
            ->exists();

        if (!$tieneDisponibilidad) {
            $docente = Docente::find($idDocente);
            return response()->json([
                'message' => "El docente {$docente->nombre} {$docente->apellido} no tiene disponibilidad en el horario seleccionado para ese módulo."
            ], 422);
        }

        // Verificar que el aula no esté ocupada en ese módulo+bloque (por otra materia)
        $aulaOcupada = DocenteMateria::where('id_aula', $idAula)
            ->where('id_modulo', $idModulo)
            ->where('id_bloque', $idBloque)
            ->where('id_materia', '!=', $idMateria)
            ->exists();

        if ($aulaOcupada) {
            $aula = Aula::find($idAula);
            $bloque = BloqueHorario::find($idBloque);
            $modulo = Modulo::find($idModulo);
            return response()->json([
                'message' => "El aula {$aula->nombre} ya está ocupada en el Bloque {$bloque->nombre} del {$modulo->nombre}."
            ], 422);
        }

        // Verificar que el docente no tenga otra materia en ese módulo+bloque
        $docenteOcupado = DocenteMateria::where('id_docente', $idDocente)
            ->where('id_modulo', $idModulo)
            ->where('id_bloque', $idBloque)
            ->where('id_materia', '!=', $idMateria)
            ->exists();

        if ($docenteOcupado) {
            $docente = Docente::find($idDocente);
            $bloque = BloqueHorario::find($idBloque);
            $modulo = Modulo::find($idModulo);
            return response()->json([
                'message' => "El docente {$docente->nombre} {$docente->apellido} ya tiene otra materia asignada en el Bloque {$bloque->nombre} del {$modulo->nombre}."
            ], 422);
        }

        $dm = DocenteMateria::updateOrCreate(
            ['id_materia' => $idMateria],
            [
                'id_docente' => $idDocente,
                'id_aula'    => $idAula,
                'id_modulo'  => $idModulo,
                'id_bloque'  => $idBloque,
            ]
        );

        return response()->json([
            'message' => "Docente vinculado correctamente a la materia en el módulo y horario seleccionados.",
            'data'    => $dm
        ], 201);
    }

    private function getActiveModuloId(): ?int
    {
        $modulo = Modulo::where('fecha_inicio', '<=', now())
            ->where('fecha_final', '>=', now())
            ->first();

        if ($modulo) return $modulo->id_modulo;

        $modulo = Modulo::where('fecha_inicio', '>', now())
            ->orderBy('fecha_inicio', 'asc')
            ->first();

        if ($modulo) return $modulo->id_modulo;

        $modulo = Modulo::orderBy('fecha_final', 'desc')->first();

        return $modulo?->id_modulo;
    }
}
