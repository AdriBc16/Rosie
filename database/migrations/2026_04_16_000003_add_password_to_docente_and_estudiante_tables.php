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
        Schema::table('docente', function (Blueprint $table): void {
            if (!Schema::hasColumn('docente', 'password')) {
                $table->string('password')->nullable()->after('correo');
            }
        });

        Schema::table('estudiante', function (Blueprint $table): void {
            if (!Schema::hasColumn('estudiante', 'password')) {
                $table->string('password')->nullable()->after('correo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('docente', function (Blueprint $table): void {
            if (Schema::hasColumn('docente', 'password')) {
                $table->dropColumn('password');
            }
        });

        Schema::table('estudiante', function (Blueprint $table): void {
            if (Schema::hasColumn('estudiante', 'password')) {
                $table->dropColumn('password');
            }
        });
    }
};
