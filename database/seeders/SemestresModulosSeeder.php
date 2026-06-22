<?php

namespace Database\Seeders;

use App\Models\Semestre;
use App\Models\Modulo;
use App\Models\BloqueHorario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SemestresModulosSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Semestres
        $semestre1 = Semestre::updateOrCreate(
            ['numero' => 1],
            [
                'nombre' => 'SEMESTRE I 2026',
                'fecha_inicio' => '2026-02-04',
                'fecha_final' => '2026-06-26'
            ]
        );

        $semestre2 = Semestre::updateOrCreate(
            ['numero' => 2],
            [
                'nombre' => 'SEMESTRE II 2026',
                'fecha_inicio' => '2026-08-03',
                'fecha_final' => '2026-12-20'
            ]
        );

        // 2. Crear Modulos para SEMESTRE I 2026
        Modulo::updateOrCreate(
            ['id_semestre' => $semestre1->id_semestre, 'numero_en_semestre' => 1],
            ['nombre' => 'Módulo 1 — Sem. I', 'fecha_inicio' => '2026-02-04', 'fecha_final' => '2026-03-23', 'creditos' => 3]
        );

        Modulo::updateOrCreate(
            ['id_semestre' => $semestre1->id_semestre, 'numero_en_semestre' => 2],
            ['nombre' => 'Módulo 2 — Sem. I', 'fecha_inicio' => '2026-03-24', 'fecha_final' => '2026-05-08', 'creditos' => 3]
        );

        Modulo::updateOrCreate(
            ['id_semestre' => $semestre1->id_semestre, 'numero_en_semestre' => 3],
            ['nombre' => 'Módulo 3 — Sem. I', 'fecha_inicio' => '2026-05-11', 'fecha_final' => '2026-06-26', 'creditos' => 3]
        );

        // 3. Crear Modulos para SEMESTRE II 2026
        Modulo::updateOrCreate(
            ['id_semestre' => $semestre2->id_semestre, 'numero_en_semestre' => 1],
            ['nombre' => 'Módulo 1 — Sem. II', 'fecha_inicio' => '2026-08-03', 'fecha_final' => '2026-09-20', 'creditos' => 3]
        );

        Modulo::updateOrCreate(
            ['id_semestre' => $semestre2->id_semestre, 'numero_en_semestre' => 2],
            ['nombre' => 'Módulo 2 — Sem. II', 'fecha_inicio' => '2026-09-21', 'fecha_final' => '2026-11-08', 'creditos' => 3]
        );

        Modulo::updateOrCreate(
            ['id_semestre' => $semestre2->id_semestre, 'numero_en_semestre' => 3],
            ['nombre' => 'Módulo 3 — Sem. II', 'fecha_inicio' => '2026-11-09', 'fecha_final' => '2026-12-20', 'creditos' => 3]
        );

        // 4. Crear Horarios (Bloques Horarios)
        $bloques = [
            ['nombre' => 'A', 'hora_inicio' => '07:45:00', 'hora_fin' => '09:45:00', 'orden' => 1],
            ['nombre' => 'B', 'hora_inicio' => '10:00:00', 'hora_fin' => '12:00:00', 'orden' => 2],
            ['nombre' => 'C', 'hora_inicio' => '12:15:00', 'hora_fin' => '14:15:00', 'orden' => 3],
            ['nombre' => 'D', 'hora_inicio' => '14:30:00', 'hora_fin' => '16:30:00', 'orden' => 4],
            ['nombre' => 'E', 'hora_inicio' => '16:45:00', 'hora_fin' => '18:45:00', 'orden' => 5],
            ['nombre' => 'F', 'hora_inicio' => '19:00:00', 'hora_fin' => '21:00:00', 'orden' => 6],
        ];

        foreach ($bloques as $b) {
            BloqueHorario::updateOrCreate(
                ['nombre' => $b['nombre']],
                ['hora_inicio' => $b['hora_inicio'], 'hora_fin' => $b['hora_fin'], 'orden' => $b['orden']]
            );
        }
    }
}
