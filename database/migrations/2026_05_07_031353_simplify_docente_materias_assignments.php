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
        Schema::table('docente_materias', function (Blueprint $table) {
            // Hacemos id_modulo e id_bloque nullables porque ahora el algoritmo los decidirá
            $table->integer('id_modulo')->nullable()->change();
            $table->integer('id_bloque')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('docente_materias', function (Blueprint $table) {
            $table->integer('id_modulo')->nullable(false)->change();
            $table->integer('id_bloque')->nullable(false)->change();
        });
    }
};
