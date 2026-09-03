<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centro_metricas', function (Blueprint $table) {
            $table->id();

            // Clave principal del negocio — cédula venezolana (7-8 dígitos)
            $table->string('cedula', 8)->unique();

            $table->string('nombres_apellidos')->nullable();
            $table->string('cargo')->nullable();
            $table->string('ubicacion_administrativa')->nullable();
            $table->string('planta', 100)->nullable();
            $table->string('filial', 100)->nullable();
            $table->string('estado_ubicacion_fisica', 100)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('estado', 100)->nullable();
            $table->string('municipio', 100)->nullable();
            $table->string('parroquia', 100)->nullable();
            $table->string('centro_votacion')->nullable();
            $table->text('direccion_centro_votacion')->nullable();

            // Resultado del cruce con el archivo de votos (Task 7)
            $table->enum('estatus_voto', ['Si', 'No'])->nullable();

            $table->timestamps();

            // Índices para las consultas del dashboard (Req 6.1, 6.2, 6.3, 6.4)
            $table->index('planta');
            $table->index('filial');
            $table->index('estado');
            $table->index('estatus_voto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centro_metricas');
    }
};
