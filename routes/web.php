<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DocenteController;
use App\Http\Controllers\Web\EstudianteController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\MateriaController;
use App\Http\Controllers\Web\InscripcionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'API Backend']);
});

Route::prefix('/portal/api')->group(function (): void {
    Route::get('/login', function () {
        return response()->json(['message' => 'No autenticado'], 401);
    })->name('portal.login');

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);


    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth.portal')
        ->name('portal.logout');

    Route::put('/perfil', [AuthController::class, 'updateProfile'])
        ->middleware('auth.portal')
        ->name('portal.api.profile.update');
});

Route::get('/portal/api/me', [AuthController::class, 'me'])
    ->middleware('auth.portal')
    ->name('portal.api.me');

Route::middleware(['auth.portal', 'portal.role:docente'])->group(function (): void {
    Route::prefix('/portal/api/docente')->group(function (): void {
        Route::get('/materias', [DocenteController::class, 'teacherAssignments'])->name('portal.api.teacher.assignments');
        Route::get('/materias/{idDm}/estudiantes', [DocenteController::class, 'teacherAssignmentStudents'])->name('portal.api.teacher.assignment.students');
        Route::get('/disponibilidad', [DocenteController::class, 'getDisponibilidad'])->name('portal.api.teacher.get_disponibilidad');
        Route::post('/disponibilidad', [DocenteController::class, 'saveDisponibilidad'])->name('portal.api.teacher.save_disponibilidad');
    });
});

Route::middleware(['auth.portal', 'portal.role:estudiante'])->group(function (): void {
    Route::prefix('/portal/api/estudiante')->group(function (): void {
        Route::get('/horario/sugerencias', [EstudianteController::class, 'suggestSchedules'])->name('portal.api.student.schedule.suggestions');
        Route::post('/horario/generar', [EstudianteController::class, 'generateSchedule'])->name('portal.api.student.generate_schedule');
    });
});

Route::middleware(['auth.portal', 'portal.role:jefe'])->group(function (): void {
    Route::prefix('/portal/api/jefe')->group(function (): void {
        Route::get('/catalogo', [DashboardController::class, 'headCatalog'])->name('portal.api.head.catalog');
        Route::get('/docentes/{idDocente}/disponibilidad', [DashboardController::class, 'headTeacherAvailability'])->name('portal.api.head.teacher.availability');
        Route::get('/personas/{tipo}/{idPersona}/materias', [DashboardController::class, 'headPersonSubjects'])->name('portal.api.head.person.subjects');
        Route::post('/materias', [MateriaController::class, 'headCreateMateria'])->name('portal.api.head.subject.store');
        Route::post('/asignaciones', [MateriaController::class, 'headAssignMateria'])->name('portal.api.head.assignment.store');
        Route::post('/inscripciones', [InscripcionController::class, 'headEnrollStudent'])->name('portal.api.head.enrollment.store');
        Route::post('/estudiantes/{idEstudiante}/horario/generar', [EstudianteController::class, 'jefeGenerateScheduleForStudent'])->name('portal.api.head.student.schedule.generate');
    });
});
