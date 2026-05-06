<?php

namespace Database\Seeders;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Modulo;
use App\Models\Semestre;
use App\Models\Universidad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AcademicDataSeeder extends Seeder
{
    /**
     * Seed demo academic data:
     * - 20 docentes (2 jefes de carrera)
     * - 100 estudiantes
     * - 8 materias (sin asignar)
     * - universidades, semestres y modulos
     */
    public function run(): void
    {
        $universidades = [
            Universidad::firstOrCreate(['nombre' => 'Universidad GoodOrder']),
            Universidad::firstOrCreate(['nombre' => 'Instituto Tecnologico GoodOrder']),
        ];

        $semestres = [
            Semestre::updateOrCreate(
                ['nombre' => 'Semestre 1-2026'],
                ['fecha_inicio' => '2026-01-20', 'fecha_final' => '2026-06-30']
            ),
            Semestre::updateOrCreate(
                ['nombre' => 'Semestre 2-2026'],
                ['fecha_inicio' => '2026-07-20', 'fecha_final' => '2026-12-05']
            ),
            Semestre::updateOrCreate(
                ['nombre' => 'Semestre Verano 2027'],
                ['fecha_inicio' => '2027-01-10', 'fecha_final' => '2027-03-30']
            ),
        ];

        $modulosPlan = [
            ['nombre' => 'Modulo A1', 'fecha_inicio' => '2026-01-20', 'fecha_final' => '2026-03-10', 'id_semestre' => $semestres[0]->id_semestre],
            ['nombre' => 'Modulo A2', 'fecha_inicio' => '2026-03-11', 'fecha_final' => '2026-04-30', 'id_semestre' => $semestres[0]->id_semestre],
            ['nombre' => 'Modulo A3', 'fecha_inicio' => '2026-05-01', 'fecha_final' => '2026-06-30', 'id_semestre' => $semestres[0]->id_semestre],
            ['nombre' => 'Modulo B1', 'fecha_inicio' => '2026-07-20', 'fecha_final' => '2026-09-10', 'id_semestre' => $semestres[1]->id_semestre],
            ['nombre' => 'Modulo B2', 'fecha_inicio' => '2026-09-11', 'fecha_final' => '2026-10-25', 'id_semestre' => $semestres[1]->id_semestre],
            ['nombre' => 'Modulo B3', 'fecha_inicio' => '2026-10-26', 'fecha_final' => '2026-12-05', 'id_semestre' => $semestres[1]->id_semestre],
            ['nombre' => 'Modulo V1', 'fecha_inicio' => '2027-01-10', 'fecha_final' => '2027-02-15', 'id_semestre' => $semestres[2]->id_semestre],
            ['nombre' => 'Modulo V2', 'fecha_inicio' => '2027-02-16', 'fecha_final' => '2027-03-30', 'id_semestre' => $semestres[2]->id_semestre],
        ];

        $modulos = [];
        foreach ($modulosPlan as $moduloData) {
            $modulos[] = Modulo::updateOrCreate(
                ['nombre' => $moduloData['nombre']],
                $moduloData
            );
        }

        $materias = [
            'Programacion I',
            'Programacion II',
            'Base de Datos I',
            'Base de Datos II',
            'Arquitectura de Computadoras',
            'Redes de Computadoras',
            'Ingenieria de Software',
            'Gestion de Proyectos',
        ];

        foreach ($materias as $nombreMateria) {
            Materia::firstOrCreate(['nombre' => $nombreMateria]);
        }

        $nombres = ['Carlos', 'Maria', 'Jose', 'Ana', 'Luis', 'Paola', 'Javier', 'Daniela', 'Miguel', 'Andrea','Josue ', 'Sofia', 'Diego', 'Valentina', 'Fernando', 'Camila', 'Ricardo', 'Isabella', 'Alberto', 'Gabriela', 'Enrique'];
        $apellidos = ['Cabrera', 'Lopez', 'Fernandez', 'Rojas', 'Vargas', 'Salazar', 'Flores', 'Mendez', 'Rivera', 'Gomez'];

        for ($i = 1; $i <= 20; $i++) {
            $nombre = $nombres[($i - 1) % count($nombres)].' '.$apellidos[($i * 2) % count($apellidos)].' '.$apellidos[($i * 3) % count($apellidos)];
            $correo = sprintf('docente%02d@goodorder.edu.bo', $i);
            $idUniversidad = $i <= 14 ? $universidades[0]->id_universidad : $universidades[1]->id_universidad;
            $esJefe = $i <= 2 ? 1 : 0;

            Docente::updateOrCreate(
                ['correo' => $correo],
                [
                    'nombre' => $nombre,
                    'id_universidad' => $idUniversidad,
                    'es_jefe_carrera' => $esJefe,
                    'correo' => $correo,
                    'password' => Hash::make('UPB123'),
                ]
            );
        }

        for ($i = 1; $i <= 100; $i++) {
            $nombre = 'Estudiante '.$nombres[$i % count($nombres)].' '.$apellidos[$i % count($apellidos)].' '.$i;
            $correo = sprintf('estudiante%03d@goodorder.edu.bo', $i);
            $universidad = $i <= 70 ? $universidades[0] : $universidades[1];
            $modulo = $modulos[($i - 1) % count($modulos)];

            Estudiante::updateOrCreate(
                ['correo' => $correo],
                [
                    'nombre' => $nombre,
                    'id_universidad' => $universidad->id_universidad,
                    'correo' => $correo,
                    'id_modulo' => $modulo->id_modulo,
                    'password' => Hash::make('UPB123'),
                ]
            );
        }
    }
}
