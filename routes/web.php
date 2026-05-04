<?php

use App\Http\Controllers\Web\PortalAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'API Backend']);
});

Route::prefix('/portal/api')->group(function (): void {
    Route::get('/login', function () {
        return response()->json(['message' => 'No autenticado'], 401);
    })->name('portal.login');

    Route::middleware('guest.portal')->group(function (): void {
        Route::post('/login', [PortalAuthController::class, 'login']);
    });

    Route::post('/logout', [PortalAuthController::class, 'logout'])
        ->middleware('auth.portal')
        ->name('portal.logout');
});

Route::get('/portal/api/me', [PortalAuthController::class, 'me'])
    ->middleware('auth.portal')
    ->name('portal.api.me');

Route::middleware(['auth.portal', 'portal.role:docente'])->group(function (): void {
    Route::prefix('/portal/api/docente')->group(function (): void {
        Route::get('/materias', [PortalAuthController::class, 'teacherAssignments'])->name('portal.api.teacher.assignments');
        Route::get('/materias/{idDm}/estudiantes', [PortalAuthController::class, 'teacherAssignmentStudents'])->name('portal.api.teacher.assignment.students');
        Route::get('/disponibilidad', [PortalAuthController::class, 'getDisponibilidad'])->name('portal.api.teacher.get_disponibilidad');
        Route::post('/disponibilidad', [PortalAuthController::class, 'saveDisponibilidad'])->name('portal.api.teacher.save_disponibilidad');
    });
});

Route::middleware(['auth.portal', 'portal.role:estudiante'])->group(function (): void {
    Route::prefix('/portal/api/estudiante')->group(function (): void {
        Route::post('/horario/generar', [PortalAuthController::class, 'generateSchedule'])->name('portal.api.student.generate_schedule');
    });
});

Route::middleware(['auth.portal', 'portal.role:jefe'])->group(function (): void {
    Route::prefix('/portal/api/jefe')->group(function (): void {
        Route::get('/catalogo', [PortalAuthController::class, 'headCatalog'])->name('portal.api.head.catalog');
        Route::get('/docentes/{idDocente}/disponibilidad', [PortalAuthController::class, 'headTeacherAvailability'])->name('portal.api.head.teacher.availability');
        Route::get('/personas/{tipo}/{idPersona}/materias', [PortalAuthController::class, 'headPersonSubjects'])->name('portal.api.head.person.subjects');
        Route::post('/materias', [PortalAuthController::class, 'headCreateMateria'])->name('portal.api.head.subject.store');
        Route::post('/asignaciones', [PortalAuthController::class, 'headAssignMateria'])->name('portal.api.head.assignment.store');
        Route::post('/inscripciones', [PortalAuthController::class, 'headEnrollStudent'])->name('portal.api.head.enrollment.store');
    });
});
