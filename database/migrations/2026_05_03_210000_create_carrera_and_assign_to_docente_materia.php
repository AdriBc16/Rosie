<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('carrera')) {
            Schema::create('carrera', function (Blueprint $table): void {
                $table->increments('id_carrera');
                $table->string('nombre', 120);
                $table->integer('id_universidad');
            });
        }

        try {
            Schema::table('carrera', function (Blueprint $table): void {
                $table->foreign('id_universidad')
                    ->references('id_universidad')
                    ->on('universidad')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        } catch (\Throwable) {
            // FK may already exist from a partial migration run.
        }

        if (!Schema::hasColumn('materia', 'id_carrera')) {
            Schema::table('materia', function (Blueprint $table): void {
                $table->integer('id_carrera')->nullable()->after('nombre');
            });
        }

        if (!Schema::hasColumn('docente', 'id_carrera')) {
            Schema::table('docente', function (Blueprint $table): void {
                $table->integer('id_carrera')->nullable()->after('id_universidad');
            });
        }

        try {
            Schema::table('materia', function (Blueprint $table): void {
                $table->foreign('id_carrera')
                    ->references('id_carrera')
                    ->on('carrera')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        } catch (\Throwable) {
            // FK may already exist.
        }

        try {
            Schema::table('docente', function (Blueprint $table): void {
                $table->foreign('id_carrera')
                    ->references('id_carrera')
                    ->on('carrera')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        } catch (\Throwable) {
            // FK may already exist.
        }

        $universidades = DB::table('universidad')->get();

        foreach ($universidades as $universidad) {
            $idCarrera = DB::table('carrera')->insertGetId([
                'nombre' => 'Ingenieria de Sistemas',
                'id_universidad' => $universidad->id_universidad,
            ]);

            DB::table('docente')
                ->where('id_universidad', $universidad->id_universidad)
                ->whereNull('id_carrera')
                ->update(['id_carrera' => $idCarrera]);

            DB::table('materia')
                ->whereNull('id_carrera')
                ->update(['id_carrera' => $idCarrera]);
        }
    }

    public function down(): void
    {
        Schema::table('docente', function (Blueprint $table): void {
            $table->dropForeign(['id_carrera']);
            $table->dropColumn('id_carrera');
        });

        Schema::table('materia', function (Blueprint $table): void {
            $table->dropForeign(['id_carrera']);
            $table->dropColumn('id_carrera');
        });

        Schema::dropIfExists('carrera');
    }
};
