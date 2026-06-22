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

    private static array $MATERIAS_SEMESTRALES = [
        'English Beginners',
        'English Intermediate',
        'English High Intermediate',
        'English Advanced',
    ];

    private function esSemestral(int $idMateria): bool
    {
        $materia = Materia::find($idMateria);
        return $materia && in_array($materia->nombre, self::$MATERIAS_SEMESTRALES);
    }

    public function headAssignMateria(Request $request): JsonResponse
    {
        $esSemestral = $request->has('id_semestre') && !$request->has('id_modulo');

        $rules = [
            'id_docente' => 'required|integer|exists:docentes,id_docente',
            'id_materia' => 'required|integer|exists:materias,id_materia',
            'id_aula'    => 'required|integer|exists:aulas,id_aula',
            'id_bloque'  => 'required|integer|exists:bloques_horarios,id_bloque',
        ];

        if ($esSemestral) {
            $rules['id_semestre'] = 'required|integer|exists:semestres,id_semestre';
        } else {
            $rules['id_modulo'] = 'required|integer|exists:modulos,id_modulo';
        }

        $request->validate($rules);

        $idDocente = $request->id_docente;
        $idMateria = $request->id_materia;
        $idAula    = $request->id_aula;
        $idBloque  = $request->id_bloque;

        if ($esSemestral) {
            return $this->assignSemestral($idDocente, $idMateria, $idAula, $idBloque, $request->id_semestre);
        }

        $idModulo = $request->id_modulo;

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
            ['id_materia' => $idMateria, 'id_modulo' => $idModulo],
            [
                'id_docente' => $idDocente,
                'id_aula'    => $idAula,
                'id_bloque'  => $idBloque,
            ]
        );

        return response()->json([
            'message' => "Docente vinculado correctamente a la materia en el módulo y horario seleccionados.",
            'data'    => $dm
        ], 201);
    }

    private function assignSemestral(int $idDocente, int $idMateria, int $idAula, int $idBloque, int $idSemestre): JsonResponse
    {
        $modulos = Modulo::where('id_semestre', $idSemestre)->get();

        if ($modulos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron módulos para el semestre seleccionado.'], 422);
        }

        $docente = Docente::find($idDocente);
        $bloque  = BloqueHorario::find($idBloque);
        $aula    = Aula::find($idAula);
        $created = [];

        foreach ($modulos as $modulo) {
            // Verificar disponibilidad del docente en cada módulo
            $tieneDisponibilidad = DisponibilidadDocente::where('id_docente', $idDocente)
                ->where('id_modulo', $modulo->id_modulo)
                ->where('id_bloque', $idBloque)
                ->exists();

            if (!$tieneDisponibilidad) {
                return response()->json([
                    'message' => "El docente {$docente->nombre} {$docente->apellido} no tiene disponibilidad en el Bloque {$bloque->nombre} para el {$modulo->nombre}."
                ], 422);
            }

            // Verificar que el aula no esté ocupada
            $aulaOcupada = DocenteMateria::where('id_aula', $idAula)
                ->where('id_modulo', $modulo->id_modulo)
                ->where('id_bloque', $idBloque)
                ->where('id_materia', '!=', $idMateria)
                ->exists();

            if ($aulaOcupada) {
                return response()->json([
                    'message' => "El aula {$aula->nombre} ya está ocupada en el Bloque {$bloque->nombre} del {$modulo->nombre}."
                ], 422);
            }

            // Verificar que el docente no tenga otra materia en ese módulo+bloque
            $docenteOcupado = DocenteMateria::where('id_docente', $idDocente)
                ->where('id_modulo', $modulo->id_modulo)
                ->where('id_bloque', $idBloque)
                ->where('id_materia', '!=', $idMateria)
                ->exists();

            if ($docenteOcupado) {
                return response()->json([
                    'message' => "El docente {$docente->nombre} {$docente->apellido} ya tiene otra materia en el Bloque {$bloque->nombre} del {$modulo->nombre}."
                ], 422);
            }
        }

        // Eliminar asignaciones previas de esta materia para limpiar antes de recrear
        DocenteMateria::where('id_materia', $idMateria)->delete();

        foreach ($modulos as $modulo) {
            $created[] = DocenteMateria::create([
                'id_materia' => $idMateria,
                'id_docente' => $idDocente,
                'id_aula'    => $idAula,
                'id_modulo'  => $modulo->id_modulo,
                'id_bloque'  => $idBloque,
            ]);
        }

        return response()->json([
            'message' => "Docente vinculado a la materia semestral en los {$modulos->count()} módulos del semestre.",
            'data'    => $created
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
