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
        Schema::create('metric_facts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('upload_id')->constrained()->onDelete('cascade');
    $table->date('measurement_date');
    $table->decimal('value', 15, 2);
    $table->string('dimension_1')->nullable(); // Región / Zona
    $table->string('dimension_2')->nullable(); // Categoría / Tipo Servicio
    $table->json('metadata')->nullable();      // Por si mandan columnas extra que no mapeamos
    $table->timestamps();

    // Índices cruciales para que los reportes y gráficos carguen instantáneamente
    $table->index(['measurement_date', 'dimension_1', 'dimension_2']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metric_facts');
    }
};
