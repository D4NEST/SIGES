<?php

namespace Database\Seeders;

use App\Models\Venta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VentasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Productos de ejemplo
        $productos = [
            ['producto' => 'Laptop HP Pavilion', 'categoria' => 'Computadoras', 'sku' => 'LAP-HP-001'],
            ['producto' => 'Mouse Logitech M185', 'categoria' => 'Periféricos', 'sku' => 'MOU-LOG-001'],
            ['producto' => 'Teclado Mecánico RGB', 'categoria' => 'Periféricos', 'sku' => 'TEC-MEC-001'],
            ['producto' => 'Monitor Samsung 24"', 'categoria' => 'Monitores', 'sku' => 'MON-SAM-001'],
            ['producto' => 'Audífonos Sony WH-1000', 'categoria' => 'Audio', 'sku' => 'AUD-SON-001'],
            ['producto' => 'Tablet iPad Air', 'categoria' => 'Tablets', 'sku' => 'TAB-APP-001'],
            ['producto' => 'Smartphone Samsung Galaxy', 'categoria' => 'Smartphones', 'sku' => 'CEL-SAM-001'],
            ['producto' => 'Impresora HP LaserJet', 'categoria' => 'Impresoras', 'sku' => 'IMP-HP-001'],
            ['producto' => 'SSD Kingston 500GB', 'categoria' => 'Almacenamiento', 'sku' => 'SSD-KIN-001'],
            ['producto' => 'Webcam Logitech C920', 'categoria' => 'Periféricos', 'sku' => 'WEB-LOG-001'],
            ['producto' => 'Router TP-Link AC1750', 'categoria' => 'Redes', 'sku' => 'ROU-TPL-001'],
            ['producto' => 'Memoria USB 64GB', 'categoria' => 'Almacenamiento', 'sku' => 'USB-GEN-001'],
            ['producto' => 'Cargador Universal', 'categoria' => 'Accesorios', 'sku' => 'CAR-UNI-001'],
            ['producto' => 'Cable HDMI 2m', 'categoria' => 'Cables', 'sku' => 'CAB-HDM-001'],
            ['producto' => 'Batería Externa 10000mAh', 'categoria' => 'Accesorios', 'sku' => 'BAT-EXT-001'],
        ];

        // Regiones y estados
        $ubicaciones = [
            ['region' => 'Centro', 'estados' => ['Miranda', 'Carabobo', 'Aragua', 'Vargas']],
            ['region' => 'Occidente', 'estados' => ['Zulia', 'Falcón', 'Lara', 'Portuguesa']],
            ['region' => 'Oriente', 'estados' => ['Anzoátegui', 'Monagas', 'Sucre', 'Nueva Esparta']],
            ['region' => 'Sur', 'estados' => ['Bolívar', 'Amazonas', 'Apure']],
            ['region' => 'Los Andes', 'estados' => ['Táchira', 'Mérida', 'Trujillo', 'Barinas']],
        ];

        // Canales de venta
        $canales = ['Tienda Física', 'Online', 'Distribuidor', 'Mayorista', 'Televentas'];

        // Sucursales
        $sucursales = [
            'Centro Comercial Sambil',
            'Centro Comercial Líder',
            'Shopping Center Plaza',
            'Mall El Recreo',
            'Tienda Principal',
            'Sucursal Centro',
            'Sucursal Este',
            'Sucursal Oeste',
        ];

        // Vendedores
        $vendedores = [
            'María González',
            'Carlos Rodríguez',
            'Ana Martínez',
            'José Pérez',
            'Laura Hernández',
            'Pedro Sánchez',
            'Sofía Ramírez',
            'Miguel Torres',
        ];

        // Generar ventas aleatorias
        $ventas = [];
        $fechaBase = now()->subDays(30);

        for ($i = 0; $i < 500; $i++) {
            // Seleccionar producto aleatorio
            $producto = $productos[array_rand($productos)];
            
            // Seleccionar ubicación aleatoria
            $ubicacion = $ubicaciones[array_rand($ubicaciones)];
            $estado = $ubicacion['estados'][array_rand($ubicacion['estados'])];
            
            // Generar cantidad y precios
            $cantidad = rand(1, 10);
            $precioUnitario = rand(100, 5000) / 10; // Entre 10 y 500
            $costo = $precioUnitario * (rand(40, 80) / 100); // Costo entre 40% y 80% del precio
            $margen = round((($precioUnitario - $costo) / $precioUnitario) * 100, 2);
            
            // Determinar estado de venta (80% completadas, 15% pendientes, 5% canceladas)
            $rand = rand(1, 100);
            if ($rand <= 80) {
                $estadoVenta = 'completada';
            } elseif ($rand <= 95) {
                $estadoVenta = 'pendiente';
            } else {
                $estadoVenta = 'cancelada';
            }
            
            // Fecha aleatoria en los últimos 30 días
            $fecha = $fechaBase->copy()->addDays(rand(0, 30))->format('Y-m-d');
            $hora = sprintf('%02d:00:00', rand(7, 21)); // Entre 7am y 9pm
            
            $ventas[] = [
                'producto'           => $producto['producto'],
                'categoria'          => $producto['categoria'],
                'sku'                => $producto['sku'],
                'sucursal'           => $sucursales[array_rand($sucursales)],
                'region'             => $ubicacion['region'],
                'canal_venta'        => $canales[array_rand($canales)],
                'estado'             => $estado,
                'direccion_sucursal' => 'Dirección de sucursal ejemplo',
                'cantidad'           => $cantidad,
                'precio_unitario'    => $precioUnitario,
                'total_venta'        => $cantidad * $precioUnitario,
                'costo'              => $costo,
                'margen'             => $margen,
                'estado_venta'       => $estadoVenta,
                'fecha_venta'        => $fecha,
                'hora_venta'         => $hora,
                'cliente_id'         => 'CLI-' . rand(1000, 9999),
                'vendedor'           => $vendedores[array_rand($vendedores)],
                'created_at'         => now(),
                'updated_at'         => now(),
            ];
        }

        // Insertar en lotes de 100 para mejor rendimiento
        foreach (array_chunk($ventas, 100) as $lote) {
            DB::table('ventas')->insert($lote);
        }

        $this->command->info('Se generaron ' . count($ventas) . ' registros de ventas de ejemplo.');
    }
}
