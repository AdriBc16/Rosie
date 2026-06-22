<?php

namespace Database\Seeders;

use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Docente;
use App\Models\DocenteMateria;
use App\Models\Estudiante;
use App\Models\HistorialMateria;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\Modulo;
use App\Models\Prerequisito;
use App\Models\Semestre;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->resetAcademicData();

        $bloques = collect([
            ['nombre' => 'A', 'hora_inicio' => '07:45:00', 'hora_fin' => '09:45:00', 'dia_semana' => null, 'orden' => 1],
            ['nombre' => 'B', 'hora_inicio' => '10:00:00', 'hora_fin' => '12:00:00', 'dia_semana' => null, 'orden' => 2],
            ['nombre' => 'C', 'hora_inicio' => '12:15:00', 'hora_fin' => '14:15:00', 'dia_semana' => null, 'orden' => 3],
            ['nombre' => 'D', 'hora_inicio' => '14:30:00', 'hora_fin' => '16:30:00', 'dia_semana' => null, 'orden' => 4],
            ['nombre' => 'E', 'hora_inicio' => '16:45:00', 'hora_fin' => '18:45:00', 'dia_semana' => null, 'orden' => 5],
            ['nombre' => 'F', 'hora_inicio' => '19:00:00', 'hora_fin' => '21:00:00', 'dia_semana' => null, 'orden' => 6],
        ])->map(fn ($b) => BloqueHorario::create($b));

        $aulas = collect(['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'LAB 1', 'LAB 2'])
            ->mapWithKeys(fn ($n) => [$n => Aula::create(['nombre' => $n, 'capacidad' => 40])]);

        $modulosBySemNum = [];
        $start = Carbon::create(2026, 1, 12);

        for ($sem = 1; $sem <= 10; $sem++) {
            $semStart = $start->copy()->addMonths(($sem - 1) * 5);
            $semEnd = $semStart->copy()->addMonths(4)->subDay();
            $semestre = Semestre::create([
                'nombre' => "Semestre {$sem}",
                'numero' => $sem,
                'fecha_inicio' => $semStart->toDateString(),
                'fecha_final' => $semEnd->toDateString(),
            ]);

            for ($mod = 1; $mod <= 3; $mod++) {
                $mStart = $semStart->copy()->addDays(($mod - 1) * 40);
                $mEnd = $mStart->copy()->addDays(34);
                $modulo = Modulo::create([
                    'nombre' => "Modulo {$mod}",
                    'numero_en_semestre' => $mod,
                    'id_semestre' => $semestre->id_semestre,
                    'fecha_inicio' => $mStart->toDateString(),
                    'fecha_final' => $mEnd->toDateString(),
                ]);
                $modulosBySemNum["{$sem}-{$mod}"] = $modulo;
            }
        }

        // Regla: 2 materias por modulo -> 6 por semestre
        $materiasPorSemestre = [
            1 => ['Algebra Lineal', 'Matematicas para Ingenieria I', 'Programacion I', 'Introduccion a la EDTI', 'Arquitectura y Tecnologia de Computadoras', 'English Beginners'],
            2 => ['Probabilidad y Estadistica', 'Matematicas para Ingenieria II', 'Programacion II', 'Logica Formal', 'Sistemas Logicos', 'English Intermediate'],
            3 => ['Ecuaciones Diferenciales', 'Fisica I', 'Programacion III', 'Algoritmica I', 'Automatas y Calculabilidad', 'English High Intermediate'],
            4 => ['Metodos Numericos', 'Fisica II', 'Programacion Funcional', 'Algoritmica II', 'Compilacion', 'English Advanced'],
            5 => ['Bases de Datos Relacionales', 'Sistemas Operativos I', 'Ingenieria de Software', 'Certificacion I', 'Innovacion y Creatividad', 'Metodos y Tecnicas de Investigacion'],
            6 => ['Teleinformatica', 'Aplicaciones con Redes', 'Patrones de Diseno', 'Certificacion II', 'Analisis del Entorno', 'Practica de Induccion Profesional'],
            7 => ['Proyecto de Ingenieria de Software', 'Bases de Datos Avanzadas', 'Sistemas Distribuidos', 'Certificacion III', 'Inteligencia Artificial', 'Infografia'],
            8 => ['Gestion de Proyectos Informaticos', 'Topicos Selectos en TIC', 'Topicos Selectos en Inteligencia Artificial', 'Preparacion y Evaluacion de Proyectos', 'Practica Interna', 'Practica Profesional I'],
            9 => ['Topicos Selectos en Ingenieria de Software', 'Robotica', 'Electiva I', 'Electiva II', 'Liderazgo y Etica', 'Practica Profesional II'],
            10 => ['Electiva III', 'Electiva IV', 'Electiva V', 'Emprendedurismo', 'Seminario de Grado', 'Proyecto Final Integrador'],
        ];

        $creditosPorMateria = [
            'Algebra Lineal' => 4,
            'Probabilidad y Estadistica' => 4,
            'Ecuaciones Diferenciales' => 4,
            'Metodos Numericos' => 4,
            'Teleinformatica' => 4,
            'Electiva I' => 3,
            'Electiva II' => 3,
            'Electiva III' => 3,
            'Electiva V' => 3,
            'Matematicas para Ingenieria I' => 4,
            'Matematicas para Ingenieria II' => 4,
            'Fisica II' => 4,
            'Sistemas Operativos I' => 4,
            'Patrones de Diseno' => 4,
            'Aplicaciones con Redes' => 4,
            'Sistemas Distribuidos' => 4,
            'Electiva IV' => 3,
            'Topicos Selectos en Ingenieria de Software' => 3,
            'Programacion I' => 4,
            'Fisica I' => 4,
            'Bases de Datos Relacionales' => 4,
            'Ingenieria de Software' => 4,
            'Certificacion I' => 4,
            'Proyecto de Ingenieria de Software' => 4,
            'Bases de Datos Avanzadas' => 4,
            'Gestion de Proyectos Informaticos' => 3,
            'Robotica' => 3,
            'Introduccion a la EDTI' => 3,
            'Programacion II' => 4,
            'Programacion III' => 4,
            'Programacion Funcional' => 4,
            'Compilacion' => 4,
            'Certificacion II' => 4,
            'Certificacion III' => 4,
            'Topicos Selectos en TIC' => 4,
            'Seminario de Grado' => 3,
            'Arquitectura y Tecnologia de Computadoras' => 4,
            'Logica Formal' => 4,
            'Algoritmica I' => 4,
            'Algoritmica II' => 4,
            'Innovacion y Creatividad' => 3,
            'Analisis del Entorno' => 3,
            'Inteligencia Artificial' => 4,
            'Topicos Selectos en Inteligencia Artificial' => 3,
            'Tecnicas de Comunicacion Escrita' => 3,
            'Sistemas Logicos' => 4,
            'Automatas y Calculabilidad' => 4,
            'English Advanced' => 6,
            'Metodos y Tecnicas de Investigacion' => 3,
            'Liderazgo y Etica' => 3,
            'Infografia' => 4,
            'Preparacion y Evaluacion de Proyectos' => 3,
            'English Beginners' => 6,
            'English Intermediate' => 6,
            'English High Intermediate' => 6,
            'Practica de Induccion Profesional' => 3,
            'Practica Interna' => 3,
            'Practica Profesional I' => 3,
            'Practica Profesional II' => 3,
            'Emprendedurismo' => 3,
            'Proyecto Final Integrador' => 3,
        ];

        $materiasByName = [];
        $materiaSemestreById = [];
        $materiaPosicionEnSemestreById = [];

        foreach ($materiasPorSemestre as $sem => $lista) {
            $anio = (int) ceil($sem / 2);
            foreach ($lista as $pos => $nombre) {
                $materia = Materia::create([
                    'nombre' => $nombre,
                    'creditos' => $creditosPorMateria[$nombre] ?? 3,
                    'horas_semanales' => 4,
                    'anio_academico' => $anio,
                    'semestre_academico' => $sem,
                ]);

                $materiasByName[$nombre] = $materia;
                $materiaSemestreById[$materia->id_materia] = $sem;
                $materiaPosicionEnSemestreById[$materia->id_materia] = $pos + 1;

                DB::table('malla_materias')->insert([
                    'id_materia' => $materia->id_materia,
                    'nivel_plan' => $sem,
                    'orden_en_nivel' => $pos + 1,
                ]);
            }
        }

        $edges = [
            ['Algebra Lineal', 'Probabilidad y Estadistica'],
            ['Matematicas para Ingenieria I', 'Matematicas para Ingenieria II'],
            ['Matematicas para Ingenieria II', 'Ecuaciones Diferenciales'],
            ['Ecuaciones Diferenciales', 'Metodos Numericos'],
            ['Matematicas para Ingenieria II', 'Fisica I'],
            ['Fisica I', 'Fisica II'],
            ['Programacion I', 'Programacion II'],
            ['Programacion II', 'Programacion III'],
            ['Programacion III', 'Programacion Funcional'],
            ['Programacion II', 'Algoritmica I'],
            ['Algoritmica I', 'Algoritmica II'],
            ['Algoritmica II', 'Compilacion'],
            ['Programacion II', 'Bases de Datos Relacionales'],
            ['Bases de Datos Relacionales', 'Ingenieria de Software'],
            ['Ingenieria de Software', 'Patrones de Diseno'],
            ['Patrones de Diseno', 'Proyecto de Ingenieria de Software'],
            ['Proyecto de Ingenieria de Software', 'Bases de Datos Avanzadas'],
            ['Bases de Datos Avanzadas', 'Gestion de Proyectos Informaticos'],
            ['Gestion de Proyectos Informaticos', 'Topicos Selectos en Ingenieria de Software'],
            ['Sistemas Operativos I', 'Teleinformatica'],
            ['Teleinformatica', 'Aplicaciones con Redes'],
            ['Aplicaciones con Redes', 'Sistemas Distribuidos'],
            ['Programacion Funcional', 'Certificacion I'],
            ['Certificacion I', 'Certificacion II'],
            ['Certificacion II', 'Certificacion III'],
            ['Compilacion', 'Inteligencia Artificial'],
            ['Inteligencia Artificial', 'Topicos Selectos en Inteligencia Artificial'],
            ['Topicos Selectos en Inteligencia Artificial', 'Robotica'],
            ['English Beginners', 'English Intermediate'],
            ['English Intermediate', 'English High Intermediate'],
            ['English High Intermediate', 'English Advanced'],
            ['Metodos y Tecnicas de Investigacion', 'Preparacion y Evaluacion de Proyectos'],
            ['Practica de Induccion Profesional', 'Practica Interna'],
            ['Practica Interna', 'Practica Profesional I'],
            ['Practica Profesional I', 'Practica Profesional II'],
            ['Practica Profesional II', 'Seminario de Grado'],
        ];

        $prereqByMateriaId = [];
        foreach ($edges as [$req, $mat]) {
            if (!isset($materiasByName[$req], $materiasByName[$mat])) {
                continue;
            }
            $toId = $materiasByName[$mat]->id_materia;
            $fromId = $materiasByName[$req]->id_materia;
            Prerequisito::create([
                'id_materia' => $toId,
                'id_materia_prerrequisito' => $fromId,
                'descripcion' => "Requiere {$req}",
            ]);
            $prereqByMateriaId[$toId][] = $fromId;
        }

        $jefe = Docente::create([
            'nombre' => 'Carlos',
            'apellido' => 'Mendoza',
            'es_jefe_carrera' => true,
            'correo' => 'jefe@goodorder.test',
            'password' => Hash::make('UPB123'),
        ]);

        $doc1 = Docente::create(['nombre' => 'Ana', 'apellido' => 'Lopez', 'es_jefe_carrera' => false, 'correo' => 'docente1@goodorder.test', 'password' => Hash::make('UPB123')]);
        $doc2 = Docente::create(['nombre' => 'Pedro', 'apellido' => 'Gutierrez', 'es_jefe_carrera' => false, 'correo' => 'docente2@goodorder.test', 'password' => Hash::make('UPB123')]);
        $doc3 = Docente::create(['nombre' => 'Maria', 'apellido' => 'Flores', 'es_jefe_carrera' => false, 'correo' => 'docente3@goodorder.test', 'password' => Hash::make('UPB123')]);

        // Disponibilidad por módulo: cada docente tiene disponibilidad en todos los módulos
        $todosMod = collect($modulosBySemNum)->values();
        foreach ([[$doc1, 'A'], [$doc1, 'B'], [$doc2, 'C'], [$doc2, 'D'], [$doc3, 'E'], [$doc3, 'F']] as [$doc, $bloqueNombre]) {
            $bloque = $bloques->firstWhere('nombre', $bloqueNombre);
            foreach ($todosMod as $modulo) {
                \App\Models\DisponibilidadDocente::create([
                    'id_docente' => $doc->id_docente,
                    'id_bloque'  => $bloque->id_bloque,
                    'id_modulo'  => $modulo->id_modulo,
                ]);
            }
        }

        // Asignaciones demo
        $asignaciones = [
            ['Programacion I', $doc1, '1-1', 'A', 'A1'],
            ['Programacion II', $doc1, '2-2', 'B', 'B1'],
            ['Bases de Datos Relacionales', $doc1, '5-1', 'A', 'LAB 1'],
            ['Certificacion I', $doc1, '5-2', 'B', 'A2'],
            ['Sistemas Operativos I', $doc2, '5-2', 'C', 'C1'],
            ['Aplicaciones con Redes', $doc2, '6-2', 'D', 'C2'],
            ['Ingenieria de Software', $doc3, '5-3', 'E', 'LAB 2'],
            ['Inteligencia Artificial', $doc3, '7-2', 'F', 'LAB 1'],
        ];

        foreach ($asignaciones as [$materiaNombre, $docente, $modKey, $bloqueNombre, $aulaNombre]) {
            $bloque = $bloques->firstWhere('nombre', $bloqueNombre);
            if (!isset($materiasByName[$materiaNombre], $modulosBySemNum[$modKey], $aulas[$aulaNombre], $bloque)) {
                continue;
            }
            DocenteMateria::create([
                'id_materia' => $materiasByName[$materiaNombre]->id_materia,
                'id_docente' => $docente->id_docente,
                'id_modulo' => $modulosBySemNum[$modKey]->id_modulo,
                'id_bloque' => $bloque->id_bloque,
                'id_aula' => $aulas[$aulaNombre]->id_aula,
            ]);
        }

        // Cohortes por inscripcion (desde 2022):
        // avance = 6 materias por semestre completado, respetando prerrequisitos.
        $cohortStartYear = 2022;
        $currentYear = now()->year;
        $cohortYears = range($cohortStartYear, max($cohortStartYear, $currentYear));

        $orderedMaterias = collect($materiasPorSemestre)
            ->sortKeys()
            ->flatMap(fn ($list) => $list)
            ->map(fn ($n) => $materiasByName[$n])
            ->values();

        // foreach ($cohortYears as $idx => $cohortYear) {
        //     $studentNumber = $idx + 1;
        //
        //     $est = Estudiante::create([
        //         'nombre' => "Estudiante{$studentNumber}",
        //         'apellido' => "Cohorte{$cohortYear}",
        //         'correo' => "estudiante{$studentNumber}@goodorder.test",
        //         'cohorte_ingreso' => $cohortYear,
        //         'password' => Hash::make('UPB123'),
        //         'es_traspaso' => false,
        //     ]);
        //
        //     // Ej: 5to año => 4 años completados => 4*2*6 = 48 materias aprobadas.
        //     $nivelActual = max(1, min(5, ($currentYear - $cohortYear) + 1));
        //     $semestresCompletados = max(0, min(10, ($nivelActual - 1) * 2));
        //     $materiasCompletadasObjetivo = $semestresCompletados * 6;
        //
        //     $completed = [];
        //     foreach ($orderedMaterias as $mat) {
        //         if (count($completed) >= $materiasCompletadasObjetivo) {
        //             break;
        //         }
        //         $reqs = $prereqByMateriaId[$mat->id_materia] ?? [];
        //         $allReqMet = empty(array_diff($reqs, $completed));
        //         if (!$allReqMet) {
        //             continue;
        //         }
        //
        //         $completed[] = $mat->id_materia;
        //
        //         HistorialMateria::create([
        //             'id_estudiante' => $est->id_estudiante,
        //             'id_materia' => $mat->id_materia,
        //             'convalidada' => false,
        //         ]);
        //
        //         $sem = $materiaSemestreById[$mat->id_materia] ?? null;
        //         $pos = $materiaPosicionEnSemestreById[$mat->id_materia] ?? null;
        //         if ($sem === null || $pos === null) {
        //             continue;
        //         }
        //
        //         $moduloNum = intdiv($pos - 1, 2) + 1;
        //         $modKey = "{$sem}-{$moduloNum}";
        //         if (!isset($modulosBySemNum[$modKey])) {
        //             continue;
        //         }
        //
        //         $inscYear = $cohortYear + intdiv($sem - 1, 2);
        //         $inscMonth = ($sem % 2 === 1) ? 2 : 8;
        //
        //         Inscripcion::create([
        //             'id_estudiante' => $est->id_estudiante,
        //             'id_modulo' => $modulosBySemNum[$modKey]->id_modulo,
        //             'id_materia' => $mat->id_materia,
        //             'estado' => 'aprobada',
        //             'intentos' => 1,
        //             'fecha_inscripcion' => Carbon::create($inscYear, $inscMonth, 10, 8, 0, 0),
        //         ]);
        //     }
        //
        //     // Materias en curso del nivel actual: hasta 29 créditos por semestre, 2 por módulo.
        //     $semA = $semestresCompletados + 1;
        //     $semB = $semestresCompletados + 2;
        //
        //     foreach ([$semA, $semB] as $sem) {
        //         if ($sem < 1 || $sem > 10 || !isset($materiasPorSemestre[$sem])) {
        //             continue;
        //         }
        //
        //         $creditosSemestre = 0;
        //         $matSem = array_slice($materiasPorSemestre[$sem], 0, 6);
        //         foreach ($matSem as $idxMat => $mName) {
        //             $mat = $materiasByName[$mName] ?? null;
        //             if (!$mat) {
        //                 continue;
        //             }
        //             $reqs = $prereqByMateriaId[$mat->id_materia] ?? [];
        //             $allReqMet = empty(array_diff($reqs, $completed));
        //             if (!$allReqMet) {
        //                 continue;
        //             }
        //
        //             $creditos = $creditosPorMateria[$mName] ?? 3;
        //             if ($creditosSemestre + $creditos > 29) {
        //                 continue; // Respetar límite de 29 créditos por semestre
        //             }
        //
        //             $moduloNum = intdiv($idxMat, 2) + 1;
        //             $modKey = "{$sem}-{$moduloNum}";
        //             if (!isset($modulosBySemNum[$modKey])) {
        //                 continue;
        //             }
        //
        //             $inscYear = $cohortYear + intdiv($sem - 1, 2);
        //             $inscMonth = ($sem % 2 === 1) ? 2 : 8;
        //
        //             Inscripcion::create([
        //                 'id_estudiante' => $est->id_estudiante,
        //                 'id_modulo' => $modulosBySemNum[$modKey]->id_modulo,
        //                 'id_materia' => $mat->id_materia,
        //                 'estado' => 'cursando',
        //                 'intentos' => 1,
        //                 'fecha_inscripcion' => Carbon::create($inscYear, $inscMonth, 10, 8, 0, 0),
        //             ]);
        //             $creditosSemestre += $creditos;
        //         }
        //     }
        // }

        $this->command->info('Base poblada con malla 2 materias/modulo y progreso por cohorte de inscripcion.');
        $this->command->info('Login jefe: jefe@goodorder.test / UPB123');
        // $this->call([AcademicDataSeeder::class]);
    }

    private function resetAcademicData(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        DB::table('detalle_horarios')->truncate();
        DB::table('horarios_generados')->truncate();
        DB::table('inscripciones')->truncate();
        DB::table('historial_materias')->truncate();
        DB::table('docente_materias')->truncate();
        DB::table('disponibilidad_docente')->truncate();
        DB::table('prerrequisitos')->truncate();
        DB::table('malla_materias')->truncate();
        DB::table('estudiantes')->truncate();
        DB::table('docentes')->truncate();
        DB::table('materias')->truncate();
        DB::table('modulos')->truncate();
        DB::table('semestres')->truncate();
        DB::table('aulas')->truncate();
        DB::table('bloques_horarios')->truncate();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
