<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('malla_materias', function (Blueprint $table) {
            $table->integer('id_malla_materia')->autoIncrement();
            $table->integer('id_materia');
            $table->unsignedTinyInteger('nivel_plan'); // 1..10 (semestres del plan)
            $table->unsignedTinyInteger('orden_en_nivel'); // 1..6

            $table->unique('id_materia', 'malla_materias_materia_unique');
            $table->unique(['nivel_plan', 'orden_en_nivel'], 'malla_materias_nivel_orden_unique');
            $table->index('nivel_plan', 'malla_materias_nivel_idx');

            $table->foreign('id_materia')->references('id_materia')->on('materias')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('malla_materias');
    }
};

