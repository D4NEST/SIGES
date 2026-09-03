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
        Schema::create('votos_personal', function (Blueprint $table) {
            // Usamos la cédula como string y llave primaria física (Evita duplicados por diseño)
            $table->string('cedula', 20)->primary(); 
            
            $table->string('nombre_apellido');
            $table->string('cargo')->nullable();
            $table->string('ubicacion_administrativa')->nullable();
            $table->string('planta')->nullable();
            $table->string('filial')->nullable();
            $table->string('estado_fisico')->nullable();
            $table->string('telefono', 50)->nullable();
            
            // Estado del voto: Por defecto 'NO', el segundo Excel lo cambiará a 'SI'
            $table->enum('estado_voto', ['SI', 'NO'])->default('NO');
            
            // Datos político-territoriales y electorales
            $table->string('municipio')->nullable();
            $table->string('parroquia')->nullable();
            $table->text('centro_votacion')->nullable();
            $table->text('direccion_centro')->nullable();
            
            // Llave foránea para auditoría: saber qué carga de archivo insertó al trabajador
            $table->foreignId('upload_id')->nullable()->constrained('uploads')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votos_personal');
    }
};