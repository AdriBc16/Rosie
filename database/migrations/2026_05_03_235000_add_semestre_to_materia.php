<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('materia', 'id_semestre')) {
            Schema::table('materia', function (Blueprint $table): void {
                $table->integer('id_semestre')->nullable()->after('id_carrera');
            });
        }

        try {
            Schema::table('materia', function (Blueprint $table): void {
                $table->foreign('id_semestre')
                    ->references('id_semestre')
                    ->on('semestre')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        } catch (\Throwable) {
            // already exists
        }

        DB::statement(
            'UPDATE materia m
             JOIN modulo mo ON mo.id_modulo = (
                SELECT MIN(dm.id_modulo)
                FROM docente_materia dm
                WHERE dm.id_materia = m.id_materia
             )
             SET m.id_semestre = mo.id_semestre
             WHERE m.id_semestre IS NULL'
        );

        $defaultSemestreId = DB::table('semestre')->min('id_semestre');
        if ($defaultSemestreId) {
            DB::table('materia')->whereNull('id_semestre')->update(['id_semestre' => $defaultSemestreId]);
        }
    }

    public function down(): void
    {
        Schema::table('materia', function (Blueprint $table): void {
            try {
                $table->dropForeign(['id_semestre']);
            } catch (\Throwable) {
            }

            if (Schema::hasColumn('materia', 'id_semestre')) {
                $table->dropColumn('id_semestre');
            }
        });
    }
};
