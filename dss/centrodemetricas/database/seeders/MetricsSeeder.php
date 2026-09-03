<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MetricsSeeder extends Seeder
{
    public function run()
    {
        // Agregamos el campo 'original_name' que te está pidiendo ahora
        $uploadId = DB::table('uploads')->insertGetId([
            'user_id' => 1,
            'filename' => 'archivo_prueba.csv',
            'original_name' => 'archivo_original.csv', // <--- Agregado
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($i = 0; $i < 50; $i++) {
            DB::table('metric_facts')->insert([
                'upload_id' => $uploadId,
                'measurement_date' => now(),
                'value' => rand(100, 1000),
                'dimension_1' => 'Prueba',
                'dimension_2' => 'Tipo A',
                'metadata' => json_encode(['info' => 'dato de prueba']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
