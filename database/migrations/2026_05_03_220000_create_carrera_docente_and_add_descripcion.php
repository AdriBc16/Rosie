<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('docente', 'descripcion')) {
            Schema::table('docente', function (Blueprint $table): void {
                $table->text('descripcion')->nullable()->after('es_jefe_carrera');
            });
        }

        if (!Schema::hasTable('carrera_docente')) {
            Schema::create('carrera_docente', function (Blueprint $table): void {
                $table->unsignedInteger('id_carrera');
                $table->integer('id_docente');
                $table->primary(['id_carrera', 'id_docente']);

                $table->foreign('id_carrera')
                    ->references('id_carrera')
                    ->on('carrera')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreign('id_docente')
                    ->references('id_docente')
                    ->on('docente')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });
        }

        DB::statement(
            'INSERT IGNORE INTO carrera_docente (id_carrera, id_docente)
             SELECT id_carrera, id_docente
             FROM docente
             WHERE id_carrera IS NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('carrera_docente');

        Schema::table('docente', function (Blueprint $table): void {
            $table->dropColumn('descripcion');
        });
    }
};
