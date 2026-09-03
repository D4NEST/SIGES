<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VotosPersonalSeeder extends Seeder
{
    // Datos de referencia venezolanos reales
    private array $plantas = [
        'Planta Pertigalete', 'Planta Mara', 'Planta Monay',
        'Planta Cumarebo', 'Planta Lara', 'Planta San Sebastián',
        'Planta Guayana', 'Sede Central Caracas',
    ];

    private array $filiales = [
        'Vencemos', 'Cemento Andino', 'Corporación Socialista de Cemento (CSC)',
        'Industria Venezolana de Cemento (INVECEM)', 'Cementos Catatumbo',
        'Naviera del Caribe', 'Corporación de Cemento',
    ];

    private array $estados = [
        'Anzoátegui', 'Aragua', 'Bolívar', 'Carabobo', 'Caracas',
        'Lara', 'Miranda', 'Monagas', 'Sucre', 'Táchira',
        'Trujillo', 'Zulia',
    ];

    private array $municipiosPorEstado = [
        'Anzoátegui' => ['Guanta', 'Sotillo', 'Bolívar'],
        'Aragua'     => ['Girardot', 'San Sebastián', 'Mario Briceño'],
        'Bolívar'    => ['Caroní', 'Heres', 'Angostura'],
        'Carabobo'   => ['Valencia', 'Naguanagua', 'Los Guayos'],
        'Caracas'    => ['Libertador', 'Chacao', 'Sucre'],
        'Lara'       => ['Iribarren', 'Jiménez', 'Torres'],
        'Miranda'    => ['Baruta', 'Sucre', 'Los Salias'],
        'Monagas'    => ['Maturín', 'Cedeño', 'Punceres'],
        'Sucre'      => ['Cumaná', 'Bermúdez', 'Montes'],
        'Táchira'    => ['San Cristóbal', 'Cárdenas', 'Torbes'],
        'Trujillo'   => ['Trujillo', 'Valera', 'Boconó'],
        'Zulia'      => ['Maracaibo', 'San Francisco', 'Cabimas'],
    ];

    private array $cargos = [
        'Operador de Planta', 'Supervisor de Turno', 'Técnico de Mantenimiento',
        'Analista de Sistemas', 'Coordinador de Área', 'Gerente de Operaciones',
        'Asistente Administrativo', 'Ingeniero de Proceso', 'Jefe de Sección',
        'Electricista Industrial', 'Mecánico de Planta', 'Despachador',
    ];

    private array $centros = [
        'U.E.N. Faustino Sarmiento', 'Liceo Mario Briceño Iragorry',
        'E.B.N. El Manzanillo', 'U.E.N. Manuel Reyes Zuleta',
        'Escuela Básica Nacional Primaria', 'Centro Comunitario Sector 3',
        'Liceo Nacional Bolivariano', 'U.E. Corporación de Cemento',
        'Escuela Básica Estadal', 'U.E. Simón Bolívar',
    ];

    private array $nombres = [
        'José','María','Luis','Ana','Carlos','Carmen','Juan','Laura',
        'Pedro','Elena','Miguel','Isabel','Jorge','Patricia','Roberto',
        'Luisa','Fernando','Diana','Alejandro','Beatriz',
    ];

    private array $apellidos = [
        'González','Rodríguez','Pérez','García','Martínez','López',
        'Hernández','Díaz','Torres','Ramírez','Flores','Morales',
        'Jiménez','Castillo','Vargas','Reyes','Mendoza','Silva',
    ];

    public function run(): void
    {
        $total  = 5000;  // Cambia este número libremente — 10000, 50000, etc.
        $chunk  = 500;
        $lote   = [];

        $this->command->info("Generando {$total} registros adicionales en votos_personal...");

        // NO borramos registros existentes — acumulamos sobre lo que ya hay
        // Solo evitamos colisiones generando cédulas que no existan aún
        $cedulasExistentes = DB::table('votos_personal')->pluck('cedula')->flip()->toArray();

        $insertados = 0;
        $intentos   = 0;
        $maxIntentos = $total * 3; // Margen para evitar loop infinito

        while ($insertados < $total && $intentos < $maxIntentos) {
            $intentos++;

            $cedula = $this->generarCedulaUnica($cedulasExistentes);
            $cedulasExistentes[$cedula] = true; // Marca como usada para esta sesión

            $estado    = $this->estados[array_rand($this->estados)];
            $municipio = $this->municipiosPorEstado[$estado][
                array_rand($this->municipiosPorEstado[$estado])
            ];

            // Timestamps variados para que el gráfico de carga por hora tenga datos
            $horasAtras = rand(0, 72); // Hasta 3 días atrás
            $timestamp  = now()->subHours($horasAtras)->subMinutes(rand(0, 59));

            $lote[] = [
                'cedula'                  => $cedula,
                'nombre_apellido'         => $this->nombres[array_rand($this->nombres)]
                                           . ' ' . $this->apellidos[array_rand($this->apellidos)]
                                           . ' ' . $this->apellidos[array_rand($this->apellidos)],
                'cargo'                   => $this->cargos[array_rand($this->cargos)],
                'ubicacion_administrativa'=> $this->plantas[array_rand($this->plantas)],
                'planta'                  => $this->plantas[array_rand($this->plantas)],
                'filial'                  => $this->filiales[array_rand($this->filiales)],
                'estado_fisico'           => $estado,
                'telefono'                => '0414-' . rand(1000000, 9999999),
                'estado_voto'             => (rand(0, 100) < 65) ? 'SI' : 'NO',
                'municipio'               => $municipio,
                'parroquia'               => $municipio,
                'centro_votacion'         => $this->centros[array_rand($this->centros)],
                'direccion_centro'        => 'Av. Principal, ' . $municipio,
                'upload_id'               => null,
                'created_at'              => $timestamp,
                'updated_at'              => $timestamp,
            ];

            $insertados++;

            if (count($lote) >= $chunk) {
                DB::table('votos_personal')->insertOrIgnore($lote);
                $lote = [];
                $this->command->info("  Insertados {$insertados}/{$total}...");
            }
        }

        if (!empty($lote)) {
            DB::table('votos_personal')->insertOrIgnore($lote);
        }

        $totalActual = DB::table('votos_personal')->count();
        $this->command->info("✓ Seeder completado. Nuevos: {$insertados} | Total en BD: {$totalActual}");
    }

    private function generarCedulaUnica(array &$usadas): string
    {
        // Rango 30000000-99999999 para no colisionar con cédulas reales del sistema (< 30M)
        do {
            $cedula = (string) rand(30000000, 99999999);
        } while (isset($usadas[$cedula]));
        return $cedula;
    }
}
