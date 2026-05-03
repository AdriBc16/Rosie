<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('docente', function (Blueprint $table): void {
            if (!Schema::hasColumn('docente', 'apellido')) {
                $table->string('apellido', 120)->nullable()->after('nombre');
            }
        });

        Schema::table('estudiante', function (Blueprint $table): void {
            if (!Schema::hasColumn('estudiante', 'apellido')) {
                $table->string('apellido', 120)->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('estudiante', 'es_traspaso')) {
                $table->boolean('es_traspaso')->default(0)->after('password');
            }
        });

        Schema::table('materia', function (Blueprint $table): void {
            if (!Schema::hasColumn('materia', 'horas_semanales')) {
                $table->tinyInteger('horas_semanales')->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('materia', 'ano_academico')) {
                $table->tinyInteger('ano_academico')->nullable()->after('horas_semanales');
            }
        });

        Schema::table('inscripcion', function (Blueprint $table): void {
            if (!Schema::hasColumn('inscripcion', 'id_materia')) {
                $table->integer('id_materia')->nullable()->after('id_modulo');
            }
            if (!Schema::hasColumn('inscripcion', 'estado')) {
                $table->enum('estado', ['bloqueada', 'pendiente', 'cursando', 'aprobada', 'reprobada', 'incompleta'])
                    ->default('pendiente')
                    ->after('id_materia');
            }
            if (!Schema::hasColumn('inscripcion', 'intentos')) {
                $table->tinyInteger('intentos')->default(1)->after('estado');
            }
            if (!Schema::hasColumn('inscripcion', 'fecha_inscripcion')) {
                $table->dateTime('fecha_inscripcion')->nullable()->after('intentos');
            }
        });

        try {
            Schema::table('inscripcion', function (Blueprint $table): void {
                $table->foreign('id_materia')
                    ->references('id_materia')
                    ->on('materia')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        } catch (\Throwable) {
            // already exists
        }

        DB::statement(
            "UPDATE inscripcion i
             JOIN docente_materia dm ON dm.id_dm = i.id_dm
             SET i.id_materia = dm.id_materia
             WHERE i.id_materia IS NULL"
        );

        DB::table('inscripcion')->whereNull('fecha_inscripcion')->update(['fecha_inscripcion' => now()]);

        if (!Schema::hasTable('prerequisito')) {
            Schema::create('prerequisito', function (Blueprint $table): void {
                $table->increments('id_prerrequisito');
                $table->integer('id_materia');
                $table->integer('id_materia_prerrequisito');
                $table->string('descripcion', 255)->nullable();

                $table->foreign('id_materia')
                    ->references('id_materia')
                    ->on('materia')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreign('id_materia_prerrequisito')
                    ->references('id_materia')
                    ->on('materia')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        if (!Schema::hasTable('disponibilidad_docente')) {
            Schema::create('disponibilidad_docente', function (Blueprint $table): void {
                $table->increments('id_disponibilidad');
                $table->integer('id_docente');
                $table->tinyInteger('dia_semana');
                $table->time('hora_inicio');
                $table->time('hora_fin');

                $table->foreign('id_docente')
                    ->references('id_docente')
                    ->on('docente')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('bloque_horario')) {
            Schema::create('bloque_horario', function (Blueprint $table): void {
                $table->increments('id_bloque');
                $table->string('nombre', 120);
                $table->tinyInteger('dia_semana');
                $table->time('hora_inicio');
                $table->time('hora_fin');
                $table->tinyInteger('orden')->nullable();
            });
        }

        if (!Schema::hasTable('horario_generado')) {
            Schema::create('horario_generado', function (Blueprint $table): void {
                $table->increments('id_horario');
                $table->integer('id_estudiante');
                $table->integer('id_modulo');
                $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');
                $table->dateTime('fecha_generacion')->nullable();

                $table->foreign('id_estudiante')
                    ->references('id_estudiante')
                    ->on('estudiante')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreign('id_modulo')
                    ->references('id_modulo')
                    ->on('modulo')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        if (!Schema::hasTable('detalle_horario')) {
            Schema::create('detalle_horario', function (Blueprint $table): void {
                $table->increments('id_detalle');
                $table->unsignedInteger('id_horario');
                $table->integer('id_materia');
                $table->integer('id_docente')->nullable();
                $table->unsignedInteger('id_horario_bloque');

                $table->foreign('id_horario')
                    ->references('id_horario')
                    ->on('horario_generado')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreign('id_materia')
                    ->references('id_materia')
                    ->on('materia')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $table->foreign('id_docente')
                    ->references('id_docente')
                    ->on('docente')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $table->foreign('id_horario_bloque')
                    ->references('id_bloque')
                    ->on('bloque_horario')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        if (!Schema::hasTable('historial_materia')) {
            Schema::create('historial_materia', function (Blueprint $table): void {
                $table->increments('id_historial');
                $table->integer('id_estudiante');
                $table->integer('id_materia');
                $table->boolean('convalidada')->default(0);

                $table->foreign('id_estudiante')
                    ->references('id_estudiante')
                    ->on('estudiante')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreign('id_materia')
                    ->references('id_materia')
                    ->on('materia')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_materia');
        Schema::dropIfExists('detalle_horario');
        Schema::dropIfExists('horario_generado');
        Schema::dropIfExists('bloque_horario');
        Schema::dropIfExists('disponibilidad_docente');
        Schema::dropIfExists('prerequisito');

        Schema::table('inscripcion', function (Blueprint $table): void {
            try { $table->dropForeign(['id_materia']); } catch (\Throwable) {}
            if (Schema::hasColumn('inscripcion', 'fecha_inscripcion')) {
                $table->dropColumn('fecha_inscripcion');
            }
            if (Schema::hasColumn('inscripcion', 'intentos')) {
                $table->dropColumn('intentos');
            }
            if (Schema::hasColumn('inscripcion', 'estado')) {
                $table->dropColumn('estado');
            }
            if (Schema::hasColumn('inscripcion', 'id_materia')) {
                $table->dropColumn('id_materia');
            }
        });

        Schema::table('materia', function (Blueprint $table): void {
            if (Schema::hasColumn('materia', 'ano_academico')) {
                $table->dropColumn('ano_academico');
            }
            if (Schema::hasColumn('materia', 'horas_semanales')) {
                $table->dropColumn('horas_semanales');
            }
        });

        Schema::table('estudiante', function (Blueprint $table): void {
            if (Schema::hasColumn('estudiante', 'es_traspaso')) {
                $table->dropColumn('es_traspaso');
            }
            if (Schema::hasColumn('estudiante', 'apellido')) {
                $table->dropColumn('apellido');
            }
        });

        Schema::table('docente', function (Blueprint $table): void {
            if (Schema::hasColumn('docente', 'apellido')) {
                $table->dropColumn('apellido');
            }
        });
    }
};
