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

    }

    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropIndex('estudiantes_cohorte_ingreso_idx');
            $table->dropColumn('cohorte_ingreso');
        });
    }
};

