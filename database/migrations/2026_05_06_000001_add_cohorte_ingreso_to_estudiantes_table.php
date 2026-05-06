<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->unsignedSmallInteger('cohorte_ingreso')->nullable()->after('correo');
            $table->index('cohorte_ingreso', 'estudiantes_cohorte_ingreso_idx');
        });

        // Backfill desde la primera fecha de inscripción disponible por estudiante.
        DB::statement("
            UPDATE estudiantes e
            LEFT JOIN (
                SELECT id_estudiante, MIN(YEAR(fecha_inscripcion)) AS cohorte
                FROM inscripciones
                GROUP BY id_estudiante
            ) i ON i.id_estudiante = e.id_estudiante
            SET e.cohorte_ingreso = i.cohorte
            WHERE e.cohorte_ingreso IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropIndex('estudiantes_cohorte_ingreso_idx');
            $table->dropColumn('cohorte_ingreso');
        });
    }
};

