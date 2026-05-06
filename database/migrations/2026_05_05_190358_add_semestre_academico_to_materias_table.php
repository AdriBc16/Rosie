<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->unsignedTinyInteger('semestre_academico')->nullable()->after('anio_academico');
            $table->index('semestre_academico');
        });
    }

    public function down(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->dropIndex(['semestre_academico']);
            $table->dropColumn('semestre_academico');
        });
    }
};
