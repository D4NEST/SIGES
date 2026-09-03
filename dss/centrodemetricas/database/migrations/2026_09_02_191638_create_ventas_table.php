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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id('id_venta');
            
            // Información del producto
            $table->string('producto');
            $table->string('categoria')->nullable();
            $table->string('sku')->nullable();
            
            // Información de ubicación
            $table->string('sucursal')->nullable();
            $table->string('region')->nullable();
            $table->string('canal_venta')->nullable(); // tienda, online, distribuidor
            $table->string('estado')->nullable();
            $table->string('direccion_sucursal')->nullable();
            
            // Información de la venta
            $table->integer('cantidad')->default(0);
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->decimal('total_venta', 12, 2)->default(0);
            $table->decimal('costo', 10, 2)->default(0);
            $table->decimal('margen', 5, 2)->default(0); // Porcentaje de margen
            
            // Estado y fechas
            $table->enum('estado_venta', ['completada', 'pendiente', 'cancelada'])->default('completada');
            $table->date('fecha_venta')->nullable();
            $table->time('hora_venta')->nullable();
            
            // Información del cliente y vendedor
            $table->string('cliente_id')->nullable();
            $table->string('vendedor')->nullable();
            
            // Relación con importación
            $table->foreignId('upload_id')->nullable()->constrained('uploads')->nullOnDelete();
            
            $table->timestamps();
            
            // Índices para mejorar consultas
            $table->index('producto');
            $table->index('categoria');
            $table->index('region');
            $table->index('estado_venta');
            $table->index('fecha_venta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
