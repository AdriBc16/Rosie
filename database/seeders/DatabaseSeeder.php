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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── BLOQUES HORARIOS FIJOS (A–F) ─────────────────────────────────────
        $bloques = [
            ['nombre' => 'A', 'hora_inicio' => '07:45:00', 'hora_fin' => '09:45:00', 'dia_semana' => null, 'orden' => 1],
            ['nombre' => 'B', 'hora_inicio' => '10:00:00', 'hora_fin' => '12:00:00', 'dia_semana' => null, 'orden' => 2],
            ['nombre' => 'C', 'hora_inicio' => '12:15:00', 'hora_fin' => '14:15:00', 'dia_semana' => null, 'orden' => 3],
            ['nombre' => 'D', 'hora_inicio' => '14:30:00', 'hora_fin' => '16:30:00', 'dia_semana' => null, 'orden' => 4],
            ['nombre' => 'E', 'hora_inicio' => '16:45:00', 'hora_fin' => '18:45:00', 'dia_semana' => null, 'orden' => 5],
            ['nombre' => 'F', 'hora_inicio' => '19:00:00', 'hora_fin' => '21:00:00', 'dia_semana' => null, 'orden' => 6],
        ];
        foreach ($bloques as $b) {
            BloqueHorario::create($b);
        }
        [$blqA, $blqB, $blqC, $blqD, $blqE, $blqF] = BloqueHorario::orderBy('orden')->get()->all();

        // ── AULAS ─────────────────────────────────────────────────────────────
        $aulasNames = [
            'A1', 'A2', 'A3', 'A4', 
            'B1', 'B2', 'B3', 'B4', 
            'LAB FINANZAS', 'LAB 1', 
            'C1', 'C2', 'C3', 
            'E1', 'E2', 'E3', 'E4', 'E5', 'E6', 'E7', 'E8'
        ];
        foreach ($aulasNames as $name) {
            Aula::create(['nombre' => $name, 'capacidad' => 40]);
        }
        $aulA = Aula::where('nombre', 'A1')->first();
        $aulB = Aula::where('nombre', 'B1')->first();
        $aulC = Aula::where('nombre', 'C1')->first();
        $aulD = Aula::where('nombre', 'LAB 1')->first();

        // ── SEMESTRES ─────────────────────────────────────────────────────────
        $sem1 = Semestre::create(['nombre' => 'Semestre 2025-I',  'fecha_inicio' => '2025-02-01', 'fecha_final' => '2025-06-30']);
        $sem2 = Semestre::create(['nombre' => 'Semestre 2025-II', 'fecha_inicio' => '2025-07-01', 'fecha_final' => '2025-11-30']);

        // ── MODULOS (con créditos) ────────────────────────────────────────────
        // 1 mes ≈ 3 créditos | 1.5 meses ≈ 4 créditos
        $mod1 = Modulo::create(['id_semestre' => $sem1->id_semestre, 'fecha_inicio' => '2025-02-01', 'fecha_final' => '2025-02-28', 'creditos' => 3]);
        $mod2 = Modulo::create(['id_semestre' => $sem1->id_semestre, 'fecha_inicio' => '2025-03-01', 'fecha_final' => '2025-04-15', 'creditos' => 4]); // 1.5 meses
        $mod3 = Modulo::create(['id_semestre' => $sem1->id_semestre, 'fecha_inicio' => '2025-05-01', 'fecha_final' => '2025-05-31', 'creditos' => 3]);
        $mod4 = Modulo::create(['id_semestre' => $sem2->id_semestre, 'fecha_inicio' => '2025-07-01', 'fecha_final' => '2025-07-31', 'creditos' => 3]);
        $mod5 = Modulo::create(['id_semestre' => $sem2->id_semestre, 'fecha_inicio' => '2025-09-01', 'fecha_final' => '2025-10-15', 'creditos' => 4]);

        // ── MATERIAS ──────────────────────────────────────────────────────────
        $matProg  = Materia::create(['nombre' => 'Programación I',         'horas_semanales' => 4, 'año_academico' => 1]);
        $matAlgo  = Materia::create(['nombre' => 'Algoritmos',             'horas_semanales' => 4, 'año_academico' => 1]);
        $matBD    = Materia::create(['nombre' => 'Bases de Datos',         'horas_semanales' => 4, 'año_academico' => 2]);
        $matRedes = Materia::create(['nombre' => 'Redes de Computadoras',  'horas_semanales' => 3, 'año_academico' => 2]);
        $matIA    = Materia::create(['nombre' => 'Inteligencia Artificial', 'horas_semanales' => 4, 'año_academico' => 3]);
        $matWeb   = Materia::create(['nombre' => 'Desarrollo Web',         'horas_semanales' => 4, 'año_academico' => 2]);

        Prerequisito::create(['id_materia' => $matBD->id_materia, 'id_materia_prerrequisito' => $matProg->id_materia, 'descripcion' => 'Debe haber aprobado Programación I']);
        Prerequisito::create(['id_materia' => $matIA->id_materia, 'id_materia_prerrequisito' => $matAlgo->id_materia, 'descripcion' => 'Debe haber aprobado Algoritmos']);

        // ── DOCENTES ──────────────────────────────────────────────────────────
        $jefe = Docente::create([
            'nombre' => 'Carlos', 'apellido' => 'Mendoza',
            'es_jefe_carrera' => true,
            'correo' => 'jefe@goodorder.test',
            'password' => Hash::make('password123'),
        ]);
        $doc1 = Docente::create([
            'nombre' => 'Ana', 'apellido' => 'López',
            'es_jefe_carrera' => false,
            'correo' => 'docente1@goodorder.test',
            'password' => Hash::make('password123'),
        ]);
        $doc2 = Docente::create([
            'nombre' => 'Pedro', 'apellido' => 'Gutiérrez',
            'es_jefe_carrera' => false,
            'correo' => 'docente2@goodorder.test',
            'password' => Hash::make('password123'),
        ]);
        $doc3 = Docente::create([
            'nombre' => 'María', 'apellido' => 'Flores',
            'es_jefe_carrera' => false,
            'correo' => 'docente3@goodorder.test',
            'password' => Hash::make('password123'),
        ]);

        // ── DISPONIBILIDAD DOCENTE ────────────────────────────────────────────
        // doc1 disponible en bloques A y B
        \App\Models\DisponibilidadDocente::create(['id_docente' => $doc1->id_docente, 'id_bloque' => $blqA->id_bloque]);
        \App\Models\DisponibilidadDocente::create(['id_docente' => $doc1->id_docente, 'id_bloque' => $blqB->id_bloque]);

        // doc2 disponible en bloques C y D
        \App\Models\DisponibilidadDocente::create(['id_docente' => $doc2->id_docente, 'id_bloque' => $blqC->id_bloque]);
        \App\Models\DisponibilidadDocente::create(['id_docente' => $doc2->id_docente, 'id_bloque' => $blqD->id_bloque]);

        // doc3 disponible en bloques E y F
        \App\Models\DisponibilidadDocente::create(['id_docente' => $doc3->id_docente, 'id_bloque' => $blqE->id_bloque]);
        \App\Models\DisponibilidadDocente::create(['id_docente' => $doc3->id_docente, 'id_bloque' => $blqF->id_bloque]);

        // ── ASIGNACIONES (docente + materia + modulo + bloque + aula) ─────────
        // doc1: Prog I en mod1 bloque A, aula A1
        $dm1 = DocenteMateria::create(['id_docente' => $doc1->id_docente, 'id_materia' => $matProg->id_materia,  'id_modulo' => $mod1->id_modulo, 'id_bloque' => $blqA->id_bloque, 'id_aula' => $aulA->id_aula]);
        // doc1: Algoritmos en mod2 bloque B, aula B1
        $dm2 = DocenteMateria::create(['id_docente' => $doc1->id_docente, 'id_materia' => $matAlgo->id_materia,  'id_modulo' => $mod2->id_modulo, 'id_bloque' => $blqB->id_bloque, 'id_aula' => $aulB->id_aula]);

        // doc2: BD en mod2 bloque C, aula C1
        $dm3 = DocenteMateria::create(['id_docente' => $doc2->id_docente, 'id_materia' => $matBD->id_materia,    'id_modulo' => $mod2->id_modulo, 'id_bloque' => $blqC->id_bloque, 'id_aula' => $aulC->id_aula]);
        // doc2: Redes en mod3 bloque D, aula A1
        $dm4 = DocenteMateria::create(['id_docente' => $doc2->id_docente, 'id_materia' => $matRedes->id_materia, 'id_modulo' => $mod3->id_modulo, 'id_bloque' => $blqD->id_bloque, 'id_aula' => $aulA->id_aula]);

        // doc3: Desarrollo Web en mod1 bloque E, lab L1
        $dm5 = DocenteMateria::create(['id_docente' => $doc3->id_docente, 'id_materia' => $matWeb->id_materia,   'id_modulo' => $mod1->id_modulo, 'id_bloque' => $blqE->id_bloque, 'id_aula' => $aulD->id_aula]);
        // doc3: IA en mod3 bloque F, aula B1
        $dm6 = DocenteMateria::create(['id_docente' => $doc3->id_docente, 'id_materia' => $matIA->id_materia,    'id_modulo' => $mod3->id_modulo, 'id_bloque' => $blqF->id_bloque, 'id_aula' => $aulB->id_aula]);

        // ── ESTUDIANTES ───────────────────────────────────────────────────────
        $est1 = Estudiante::create(['nombre' => 'Luis',   'apellido' => 'Ramírez',  'correo' => 'estudiante1@goodorder.test', 'password' => Hash::make('password123'), 'es_traspaso' => false]);
        $est2 = Estudiante::create(['nombre' => 'Sofia',  'apellido' => 'Castro',   'correo' => 'estudiante2@goodorder.test', 'password' => Hash::make('password123'), 'es_traspaso' => false]);
        $est3 = Estudiante::create(['nombre' => 'Andrés', 'apellido' => 'Vargas',   'correo' => 'estudiante3@goodorder.test', 'password' => Hash::make('password123'), 'es_traspaso' => true]);

        // ── INSCRIPCIONES ─────────────────────────────────────────────────────
        // est1: Prog I (mod1, 3 cr) + Algo (mod2, 4 cr) + BD (mod2, 4 cr) = 11 cr
        Inscripcion::create(['id_estudiante' => $est1->id_estudiante, 'id_modulo' => $mod1->id_modulo, 'id_materia' => $matProg->id_materia,  'estado' => 'aprobada',  'intentos' => 1, 'fecha_inscripcion' => '2025-02-01 08:00:00']);
        Inscripcion::create(['id_estudiante' => $est1->id_estudiante, 'id_modulo' => $mod2->id_modulo, 'id_materia' => $matAlgo->id_materia,  'estado' => 'cursando',  'intentos' => 1, 'fecha_inscripcion' => '2025-03-01 08:00:00']);
        Inscripcion::create(['id_estudiante' => $est1->id_estudiante, 'id_modulo' => $mod2->id_modulo, 'id_materia' => $matBD->id_materia,    'estado' => 'cursando',  'intentos' => 1, 'fecha_inscripcion' => '2025-03-01 08:00:00']);

        // est2: Prog I (mod1) + Web (mod1) + Redes (mod3) = 3+3+3 = 9 cr
        Inscripcion::create(['id_estudiante' => $est2->id_estudiante, 'id_modulo' => $mod1->id_modulo, 'id_materia' => $matProg->id_materia,  'estado' => 'aprobada',  'intentos' => 1, 'fecha_inscripcion' => '2025-02-01 09:00:00']);
        Inscripcion::create(['id_estudiante' => $est2->id_estudiante, 'id_modulo' => $mod1->id_modulo, 'id_materia' => $matWeb->id_materia,   'estado' => 'cursando',  'intentos' => 1, 'fecha_inscripcion' => '2025-02-01 09:00:00']);
        Inscripcion::create(['id_estudiante' => $est2->id_estudiante, 'id_modulo' => $mod3->id_modulo, 'id_materia' => $matRedes->id_materia, 'estado' => 'pendiente', 'intentos' => 0, 'fecha_inscripcion' => '2025-05-01 09:00:00']);

        // est3 (traspaso): Algo (mod2) + IA (mod3) = 4+3 = 7 cr
        Inscripcion::create(['id_estudiante' => $est3->id_estudiante, 'id_modulo' => $mod2->id_modulo, 'id_materia' => $matAlgo->id_materia,  'estado' => 'cursando',  'intentos' => 1, 'fecha_inscripcion' => '2025-03-01 10:00:00']);
        Inscripcion::create(['id_estudiante' => $est3->id_estudiante, 'id_modulo' => $mod3->id_modulo, 'id_materia' => $matIA->id_materia,    'estado' => 'pendiente', 'intentos' => 0, 'fecha_inscripcion' => '2025-05-01 10:00:00']);

        HistorialMateria::create(['id_estudiante' => $est3->id_estudiante, 'id_materia' => $matProg->id_materia, 'convalidada' => true]);

        $this->command->info('');
        $this->command->info('✅ Base de datos poblada correctamente.');
        $this->command->info('');
        $this->command->info('Credenciales de prueba (password: password123)');
        $this->command->info('  Jefe         → jefe@goodorder.test');
        $this->command->info('  Docente 1    → docente1@goodorder.test  (bloques A, B)');
        $this->command->info('  Docente 2    → docente2@goodorder.test  (bloques C, D)');
        $this->command->info('  Docente 3    → docente3@goodorder.test  (bloques E, F)');
        $this->command->info('  Estudiante 1 → estudiante1@goodorder.test');
        $this->command->info('  Estudiante 2 → estudiante2@goodorder.test');
        $this->command->info('  Estudiante 3 → estudiante3@goodorder.test');
    }
}
