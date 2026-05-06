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
            if (!Schema::hasColumn('modulos', 'creditos')) {
                $table->tinyInteger('creditos')->default(3)->after('id_semestre');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modulos', function (Blueprint $table) {
            if (Schema::hasColumn('modulos', 'creditos')) {
                $table->dropColumn('creditos');
            }
        });
    }
};
