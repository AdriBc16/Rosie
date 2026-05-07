<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Eliminar créditos de módulos si existe
        if (Schema::hasColumn('modulos', 'creditos')) {
            Schema::table('modulos', function (Blueprint $table) {
                $table->dropColumn('creditos');
            });
        }

        // 2. Agregar créditos a materias (renombrando o agregando si no existe)
        Schema::table('materias', function (Blueprint $table) {
            // Si ya existe horas_semanales, podemos mantenerla o agregar 'creditos'
            if (!Schema::hasColumn('materias', 'creditos')) {
                $table->tinyInteger('creditos')->default(3)->after('nombre');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            if (Schema::hasColumn('materias', 'creditos')) {
                $table->dropColumn('creditos');
            }
        });

        Schema::table('modulos', function (Blueprint $table) {
            $table->tinyInteger('creditos')->default(3);
        });
    }
};
