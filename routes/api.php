<?php

use App\Http\Controllers\Api\DocenteController;
use App\Http\Controllers\Api\DocenteMateriaController;
use App\Http\Controllers\Api\EstudianteController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AsignacionPlanningController;
use App\Http\Controllers\Api\BloqueHorarioController;
use App\Http\Controllers\Api\CarreraController;
use App\Http\Controllers\Api\DetalleHorarioController;
use App\Http\Controllers\Api\DisponibilidadDocenteController;
use App\Http\Controllers\Api\HistorialMateriaController;
use App\Http\Controllers\Api\HorarioController;
use App\Http\Controllers\Api\HorarioGeneradoController;
use App\Http\Controllers\Api\HorarioPlanningController;
use App\Http\Controllers\Api\HorasLibresDocController;
use App\Http\Controllers\Api\InscripcionController;
use App\Http\Controllers\Api\MateriaController;
use App\Http\Controllers\Api\ModuloController;
use App\Http\Controllers\Api\PrerequisitoController;
use App\Http\Controllers\Api\SemestreController;
use App\Http\Controllers\Api\UniversidadController;
use Illuminate\Support\Facades\Route;

Route::middleware('force.json')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('refresh', [AuthController::class, 'refresh']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
        });
    });

    Route::apiResource('universidades', UniversidadController::class)->parameters(['universidades' => 'id']);
    Route::apiResource('carreras', CarreraController::class)->parameters(['carreras' => 'id']);
    Route::apiResource('semestres', SemestreController::class)->parameters(['semestres' => 'id']);
    Route::apiResource('modulos', ModuloController::class)->parameters(['modulos' => 'id']);
    Route::apiResource('horarios', HorarioController::class)->parameters(['horarios' => 'id']);
    Route::apiResource('materias', MateriaController::class)->parameters(['materias' => 'id']);
    Route::post('materias/ingesta', [MateriaController::class, 'ingesta']);
    Route::apiResource('docentes', DocenteController::class)->parameters(['docentes' => 'id']);
    Route::apiResource('estudiantes', EstudianteController::class)->parameters(['estudiantes' => 'id']);
    Route::apiResource('docente-materias', DocenteMateriaController::class)->parameters(['docente-materias' => 'id']);
    Route::apiResource('horas-libres-docentes', HorasLibresDocController::class)->parameters(['horas-libres-docentes' => 'id']);
    Route::apiResource('inscripciones', InscripcionController::class)->parameters(['inscripciones' => 'id']);
    Route::apiResource('prerequisitos', PrerequisitoController::class)->parameters(['prerequisitos' => 'id']);
    Route::apiResource('disponibilidad-docentes', DisponibilidadDocenteController::class)->parameters(['disponibilidad-docentes' => 'id']);
    Route::apiResource('bloques-horarios', BloqueHorarioController::class)->parameters(['bloques-horarios' => 'id']);
    Route::apiResource('horarios-generados', HorarioGeneradoController::class)->parameters(['horarios-generados' => 'id']);
    Route::apiResource('detalle-horarios', DetalleHorarioController::class)->parameters(['detalle-horarios' => 'id']);
    Route::apiResource('historial-materias', HistorialMateriaController::class)->parameters(['historial-materias' => 'id']);

    Route::get('horarios/configuracion-ideal', [HorarioPlanningController::class, 'ideal']);
    Route::get('horarios/exportar', [HorarioPlanningController::class, 'exportar']);
    Route::get('asignaciones/sugeridas', [AsignacionPlanningController::class, 'sugeridas']);
    Route::post('inscripciones/cohorte', [AsignacionPlanningController::class, 'inscribirCohorte']);
});
