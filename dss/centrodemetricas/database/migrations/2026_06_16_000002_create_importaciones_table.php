<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones', function (Blueprint $table) {
            $table->id();

            // SHA-256 del archivo — previene importaciones duplicadas (Req 3.2)
            $table->string('hash_archivo', 64)->unique();
            $table->string('nombre_archivo');

            // Quién subió el archivo
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // Ciclo de vida del Job de importación
            $table->enum('estado', ['pendiente', 'procesando', 'completado', 'fallido'])
                ->default('pendiente');

            // Contadores finales (se llenan cuando el Job completa, Req 4.5)
            $table->unsignedInteger('total_filas')->default(0);
            $table->unsignedInteger('filas_insertadas')->default(0);
            $table->unsignedInteger('filas_actualizadas')->default(0);
            $table->unsignedInteger('filas_rechazadas')->default(0);

            // JSON con las filas inválidas: [{"fila": 5, "cedula": "abc", "motivo": "..."}]
            $table->json('log_errores')->nullable();

            $table->timestamps();

            $table->index('estado');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones');
    }
};
