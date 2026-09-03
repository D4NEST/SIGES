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
    Schema::create('audit_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
        $table->string('usuario_nombre')->nullable(); // Respaldo por si el usuario es eliminado
        $table->string('evento');          // Ejemplo: 'LOGIN', 'LOGOUT', 'CARGA_ARCHIVO', 'CREACION_USUARIO'
        $table->text('descripcion');      // Texto legible: 'El usuario Nestor cargó el archivo encuesta_junio.csv'
        $table->json('detalles_extra')->nullable(); // Para meter hashes, nombres de archivos, tamaños, etc.
        $table->string('url_solicitada');  // URL donde ocurrió el evento
        $table->string('direccion_ip');    // IP del cliente para auditorías de seguridad
        $table->string('user_agent')->nullable(); // Navegador/Sistema operativo del usuario
        $table->timestamps();              // Registra automáticamente la fecha y hora exacta (created_at)
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
