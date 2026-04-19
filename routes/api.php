<?php

use App\Http\Controllers\Api\DocenteController;
use App\Http\Controllers\Api\DocenteMateriaController;
use App\Http\Controllers\Api\EstudianteController;
use App\Http\Controllers\Api\HorarioController;
use App\Http\Controllers\Api\HorarioPlanningController;
use App\Http\Controllers\Api\HorasLibresDocController;
use App\Http\Controllers\Api\InscripcionController;
use App\Http\Controllers\Api\MateriaController;
use App\Http\Controllers\Api\ModuloController;
use App\Http\Controllers\Api\SemestreController;
use App\Http\Controllers\Api\UniversidadController;
use Illuminate\Support\Facades\Route;

Route::middleware('force.json')->group(function (): void {
    Route::apiResource('universidades', UniversidadController::class)->parameters(['universidades' => 'id']);
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

    Route::get('horarios/configuracion-ideal', [HorarioPlanningController::class, 'ideal']);
    Route::get('horarios/exportar', [HorarioPlanningController::class, 'exportar']);
});
