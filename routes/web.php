<?php

use App\Http\Controllers\Web\PortalAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('portal.login');
});

Route::middleware('guest.portal')->group(function (): void {
    Route::get('/login', [PortalAuthController::class, 'showLogin'])->name('portal.login');
    Route::post('/login', [PortalAuthController::class, 'login'])->name('portal.login.submit');
});

Route::post('/logout', [PortalAuthController::class, 'logout'])
    ->middleware('auth.portal')
    ->name('portal.logout');

Route::middleware(['auth.portal', 'portal.role:estudiante'])->group(function (): void {
    Route::get('/panel/estudiante', [PortalAuthController::class, 'studentDashboard'])->name('portal.student');
});

Route::middleware(['auth.portal', 'portal.role:docente'])->group(function (): void {
    Route::get('/panel/docente', [PortalAuthController::class, 'teacherDashboard'])->name('portal.teacher');
    Route::prefix('/portal/api/docente')->group(function (): void {
        Route::get('/materias', [PortalAuthController::class, 'teacherAssignments'])->name('portal.api.teacher.assignments');
        Route::get('/materias/{idDm}/estudiantes', [PortalAuthController::class, 'teacherAssignmentStudents'])->name('portal.api.teacher.assignment.students');
    });
});

Route::middleware(['auth.portal', 'portal.role:jefe'])->group(function (): void {
    Route::get('/panel/jefe-carrera', [PortalAuthController::class, 'headDashboard'])->name('portal.head');
    Route::prefix('/portal/api/jefe')->group(function (): void {
        Route::get('/catalogo', [PortalAuthController::class, 'headCatalog'])->name('portal.api.head.catalog');
        Route::get('/docentes/{idDocente}/disponibilidad', [PortalAuthController::class, 'headTeacherAvailability'])->name('portal.api.head.teacher.availability');
        Route::get('/personas/{tipo}/{idPersona}/materias', [PortalAuthController::class, 'headPersonSubjects'])->name('portal.api.head.person.subjects');
        Route::post('/materias', [PortalAuthController::class, 'headCreateMateria'])->name('portal.api.head.subject.store');
        Route::post('/asignaciones', [PortalAuthController::class, 'headAssignMateria'])->name('portal.api.head.assignment.store');
    });
});
