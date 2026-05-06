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
        Schema::create('semestres', function (Blueprint $table) {
            $table->integer('id_semestre')->autoIncrement();
            $table->string('nombre', 120);
            $table->date('fecha_inicio');
            $table->date('fecha_final');
        });

        Schema::create('modulos', function (Blueprint $table) {
            $table->integer('id_modulo')->autoIncrement();
            $table->date('fecha_inicio');
            $table->date('fecha_final');
            $table->integer('id_semestre');
            $table->tinyInteger('creditos')->default(3);
            
            $table->foreign('id_semestre')->references('id_semestre')->on('semestres')->onDelete('cascade');
        });

        Schema::create('docentes', function (Blueprint $table) {
            $table->integer('id_docente')->autoIncrement();
            $table->string('nombre', 120);
            $table->string('apellido', 120)->nullable();
            $table->boolean('es_jefe_carrera')->default(false);
            $table->string('correo', 150)->unique();
            $table->string('password', 191);
        });

        Schema::create('materias', function (Blueprint $table) {
            $table->integer('id_materia')->autoIncrement();
            $table->string('nombre', 120);
            $table->tinyInteger('horas_semanales');
            $table->tinyInteger('anio_academico');
        });

        Schema::create('prerrequisitos', function (Blueprint $table) {
            $table->integer('id_prerrequisito')->autoIncrement();
            $table->integer('id_materia');
            $table->integer('id_materia_prerrequisito');
            $table->string('descripcion', 255)->nullable();

            $table->foreign('id_materia')->references('id_materia')->on('materias')->onDelete('cascade');
            $table->foreign('id_materia_prerrequisito')->references('id_materia')->on('materias')->onDelete('cascade');
        });

        Schema::create('bloques_horarios', function (Blueprint $table) {
            $table->integer('id_bloque')->autoIncrement();
            $table->string('nombre', 120);
            $table->tinyInteger('dia_semana')->nullable();
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->tinyInteger('orden');
        });

        Schema::create('disponibilidad_docente', function (Blueprint $table) {
            $table->integer('id_disponibilidad')->autoIncrement();
            $table->integer('id_docente');
            $table->integer('id_bloque');
            $table->integer('id_modulo');

            $table->foreign('id_docente')->references('id_docente')->on('docentes')->onDelete('cascade');
            $table->foreign('id_bloque')->references('id_bloque')->on('bloques_horarios')->onDelete('cascade');
            $table->foreign('id_modulo')->references('id_modulo')->on('modulos')->onDelete('cascade');
        });

        Schema::create('aulas', function (Blueprint $table) {
            $table->integer('id_aula')->autoIncrement();
            $table->string('nombre', 120);
            $table->integer('capacidad')->default(30);
        });

        Schema::create('docente_materias', function (Blueprint $table) {
            $table->integer('id_dm')->autoIncrement();
            $table->integer('id_materia');
            $table->integer('id_docente');
            $table->integer('id_modulo');
            $table->integer('id_bloque')->nullable();
            $table->integer('id_aula')->nullable();

            $table->foreign('id_materia')->references('id_materia')->on('materias')->onDelete('cascade');
            $table->foreign('id_docente')->references('id_docente')->on('docentes')->onDelete('cascade');
            $table->foreign('id_modulo')->references('id_modulo')->on('modulos')->onDelete('cascade');
            $table->foreign('id_bloque')->references('id_bloque')->on('bloques_horarios')->onDelete('set null');
            $table->foreign('id_aula')->references('id_aula')->on('aulas')->onDelete('set null');
        });

        Schema::create('estudiantes', function (Blueprint $table) {
            $table->integer('id_estudiante')->autoIncrement();
            $table->string('nombre', 120);
            $table->string('apellido', 120)->nullable();
            $table->string('correo', 150)->unique();
            $table->string('password', 191);
            $table->boolean('es_traspaso')->default(false);
        });

        Schema::create('historial_materias', function (Blueprint $table) {
            $table->integer('id_historial')->autoIncrement();
            $table->integer('id_estudiante');
            $table->integer('id_materia');
            $table->boolean('convalidada')->default(false);

            $table->foreign('id_estudiante')->references('id_estudiante')->on('estudiantes')->onDelete('cascade');
            $table->foreign('id_materia')->references('id_materia')->on('materias')->onDelete('cascade');
        });

        Schema::create('inscripciones', function (Blueprint $table) {
            $table->integer('id_inscripcion')->autoIncrement();
            $table->integer('id_estudiante');
            $table->integer('id_modulo');
            $table->integer('id_materia');
            $table->enum('estado', ['bloqueada', 'pendiente', 'cursando', 'aprobada', 'reprobada', 'incompleta']);
            $table->tinyInteger('intentos')->default(0);
            $table->dateTime('fecha_inscripcion');

            $table->foreign('id_estudiante')->references('id_estudiante')->on('estudiantes')->onDelete('cascade');
            $table->foreign('id_modulo')->references('id_modulo')->on('modulos')->onDelete('cascade');
            $table->foreign('id_materia')->references('id_materia')->on('materias')->onDelete('cascade');
        });

        Schema::create('horarios_generados', function (Blueprint $table) {
            $table->integer('id_horario')->autoIncrement();
            $table->integer('id_estudiante');
            $table->integer('id_modulo');
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');
            $table->dateTime('fecha_generacion');

            $table->foreign('id_estudiante')->references('id_estudiante')->on('estudiantes')->onDelete('cascade');
            $table->foreign('id_modulo')->references('id_modulo')->on('modulos')->onDelete('cascade');
        });

        Schema::create('detalle_horarios', function (Blueprint $table) {
            $table->integer('id_detalle')->autoIncrement();
            $table->integer('id_horario');
            $table->integer('id_materia');
            $table->integer('id_docente');
            $table->integer('id_bloque');
            $table->integer('id_aula')->nullable();

            $table->foreign('id_horario')->references('id_horario')->on('horarios_generados')->onDelete('cascade');
            $table->foreign('id_materia')->references('id_materia')->on('materias')->onDelete('cascade');
            $table->foreign('id_docente')->references('id_docente')->on('docentes')->onDelete('cascade');
            $table->foreign('id_bloque')->references('id_bloque')->on('bloques_horarios')->onDelete('cascade');
            $table->foreign('id_aula')->references('id_aula')->on('aulas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_horarios');
        Schema::dropIfExists('horarios_generados');
        Schema::dropIfExists('inscripciones');
        Schema::dropIfExists('historial_materias');
        Schema::dropIfExists('estudiantes');
        Schema::dropIfExists('docente_materias');
        Schema::dropIfExists('aulas');
        Schema::dropIfExists('disponibilidad_docente');
        Schema::dropIfExists('bloques_horarios');
        Schema::dropIfExists('prerrequisitos');
        Schema::dropIfExists('materias');
        Schema::dropIfExists('docentes');
        Schema::dropIfExists('modulos');
        Schema::dropIfExists('semestres');
    }
};
