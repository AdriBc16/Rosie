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
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropForeign(['id_modulo']);
            $table->integer('id_modulo')->nullable()->change();
            $table->foreign('id_modulo')->references('id_modulo')->on('modulos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropForeign(['id_modulo']);
            $table->integer('id_modulo')->nullable(false)->change();
            $table->foreign('id_modulo')->references('id_modulo')->on('modulos')->onDelete('cascade');
        });
    }
};
