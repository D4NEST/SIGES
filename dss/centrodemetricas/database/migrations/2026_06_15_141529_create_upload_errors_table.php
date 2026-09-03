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
        Schema::create('upload_errors', function (Blueprint $table) {
    $table->id();
    $table->foreignId('upload_id')->constrained()->onDelete('cascade');
    $table->integer('row_number');
    $table->text('error_message');
    $table->json('row_data')->nullable(); // Guarda la fila problemática para que el usuario sepa qué falló
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_errors');
    }
};
