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
        Schema::table('modulos', function (Blueprint $table) {
            $table->string('nombre', 50)->nullable()->after('id_modulo');
        });

        // Poblar datos iniciales
        DB::table('modulos')->get()->each(function ($modulo) {
            DB::table('modulos')
                ->where('id_modulo', $modulo->id_modulo)
                ->update(['nombre' => 'Módulo ' . $modulo->id_modulo]);
        });
    }

    public function down(): void
    {
        Schema::table('modulos', function (Blueprint $table) {
            $table->dropColumn('nombre');
        });
    }

};
