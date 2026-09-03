<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
   public function run(): void
    {
        // 1. Creamos tu usuario administrador con el rol correspondiente
        User::factory()->create([
            'name' => 'Nestor Patino',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin', // <-- Le asignamos el rol real aquí
        ]);

        // 2. Creamos los 10 usuarios aleatorios (estos nacerán como operadores por defecto)
        User::factory(10)->create();
    }
}
