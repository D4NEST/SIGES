<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VotoPersonal;
use App\Models\RegistroHorario;
use Illuminate\Support\Facades\DB;

class RegistroHorarioSeeder extends Seeder
{
    public function run(): void
    {
        $intervalos = ['7am-8am', '8am-9am', '9am-10am', '10am-11am', '11am-12pm', '12pm-1pm'];
        
        // Obtenemos 500 cédulas aleatorias del padrón para simular votos
        $cedulas = VotoPersonal::inRandomOrder()->limit(500)->pluck('cedula');

        foreach ($cedulas as $cedula) {
            RegistroHorario::create([
                'cedula' => $cedula,
                'intervalo' => $intervalos[array_rand($intervalos)],
                'fecha_registro' => now()->format('Y-m-d'),
                'upload_id' => 1 // Asumimos ID 1
            ]);
            
            // Actualizamos el voto a SI
            VotoPersonal::where('cedula', $cedula)->update(['estado_voto' => 'SI']);
        }
    }
}