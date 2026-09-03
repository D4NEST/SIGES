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
        Schema::table('users', function (Blueprint $table) {
            // Creamos el campo 'role' después de la columna 'email'
            $table->enum('role', ['admin', 'supervisor', 'operador'])
                  ->default('operador')
                  ->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Si revertimos la migración, eliminamos la columna creada
            $table->dropColumn('role');
        });
    }
};
