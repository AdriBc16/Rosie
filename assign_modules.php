<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use App\Models\Semestre;
use App\Models\Modulo;

$semestres = Semestre::doesntHave('modulos')->get();
foreach ($semestres as $sem) {
    $start = Carbon::parse($sem->fecha_inicio);
    $end = Carbon::parse($sem->fecha_final);
    $diffDays = $start->diffInDays($end);
    $modDays = floor($diffDays / 3);
    
    // Modulo 1
    $m1_start = $start->copy();
    $m1_end = $start->copy()->addDays($modDays);
    Modulo::updateOrCreate(
        ['id_semestre' => $sem->id_semestre, 'numero_en_semestre' => 1],
        ['nombre' => 'Modulo 1', 'fecha_inicio' => $m1_start->toDateString(), 'fecha_final' => $m1_end->toDateString(), 'creditos' => 3]
    );
    
    // Modulo 2
    $m2_start = $m1_end->copy()->addDay();
    $m2_end = $m2_start->copy()->addDays($modDays);
    Modulo::updateOrCreate(
        ['id_semestre' => $sem->id_semestre, 'numero_en_semestre' => 2],
        ['nombre' => 'Modulo 2', 'fecha_inicio' => $m2_start->toDateString(), 'fecha_final' => $m2_end->toDateString(), 'creditos' => 3]
    );
    
    // Modulo 3
    $m3_start = $m2_end->copy()->addDay();
    $m3_end = $end->copy();
    Modulo::updateOrCreate(
        ['id_semestre' => $sem->id_semestre, 'numero_en_semestre' => 3],
        ['nombre' => 'Modulo 3', 'fecha_inicio' => $m3_start->toDateString(), 'fecha_final' => $m3_end->toDateString(), 'creditos' => 3]
    );
}
echo "Modulos creados para ". $semestres->count() ." semestres.\n";
