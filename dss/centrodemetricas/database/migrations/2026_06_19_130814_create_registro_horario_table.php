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
        Schema::create('registro_horario', function (Blueprint $table) {
            $table->id();
            $table->string('cedula');
            $table->string('intervalo');
            $table->date('fecha_registro');
            $table->unsignedInteger('upload_id');
            $table->timestamps();

            // Índice para velocidad en reportes
            $table->index(['cedula', 'intervalo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_horario');
    }
};