<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semestres', function (Blueprint $table) {
            $table->unsignedTinyInteger('numero')->nullable()->after('id_semestre');
        });

        Schema::table('modulos', function (Blueprint $table) {
            $table->unsignedTinyInteger('numero_en_semestre')->nullable()->after('id_modulo');
        });

        // Backfill semestres.numero por orden cronologico de inicio
        $semestres = DB::table('semestres')
            ->orderBy('fecha_inicio')
            ->orderBy('id_semestre')
            ->get(['id_semestre']);

        $n = 1;
        foreach ($semestres as $semestre) {
            DB::table('semestres')
                ->where('id_semestre', $semestre->id_semestre)
                ->update(['numero' => $n]);
            $n++;
        }

        // Backfill modulos.numero_en_semestre por semestre y fecha de inicio
        $modulos = DB::table('modulos')
            ->orderBy('id_semestre')
            ->orderBy('fecha_inicio')
            ->orderBy('id_modulo')
            ->get(['id_modulo', 'id_semestre']);

        $actualSemestre = null;
        $k = 0;

        foreach ($modulos as $modulo) {
            if ($actualSemestre !== $modulo->id_semestre) {
                $actualSemestre = $modulo->id_semestre;
                $k = 1;
            } else {
                $k++;
            }

            DB::table('modulos')
                ->where('id_modulo', $modulo->id_modulo)
                ->update(['numero_en_semestre' => $k]);
        }

        Schema::table('semestres', function (Blueprint $table) {
            $table->unique('numero');
        });

        Schema::table('modulos', function (Blueprint $table) {
            $table->index(['id_semestre', 'numero_en_semestre'], 'modulos_semestre_numero_idx');
        });
    }

    public function down(): void
    {
        Schema::table('modulos', function (Blueprint $table) {
            $table->dropIndex('modulos_semestre_numero_idx');
            $table->dropColumn('numero_en_semestre');
        });

        Schema::table('semestres', function (Blueprint $table) {
            $table->dropUnique(['numero']);
            $table->dropColumn('numero');
        });
    }
};
