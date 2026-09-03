<?php

namespace App\Imports;

use App\Models\Venta;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Str;

class VentasImport implements ToModel, WithHeadingRow, WithChunkReading, WithValidation
{
    protected array $mapping;
    protected int $uploadId;

    public function __construct(array $mapping, int $uploadId)
    {
        $this->mapping = $mapping;
        $this->uploadId = $uploadId;
    }

    public function model(array $row)
    {
        $data = [
            'upload_id' => $this->uploadId,
        ];

        // Procesar cada campo del mapeo
        foreach ($this->mapping as $header => $dbField) {
            $slug = Str::slug($header, '_');
            $valor = $row[$slug] ?? null;

            if (empty($valor) && $valor !== '0') {
                continue;
            }

            // Convertir nombres de campos del Excel a nombres de base de datos
            switch ($dbField) {
                case 'producto':
                    $data['producto'] = trim($valor);
                    break;
                    
                case 'categoria':
                    $data['categoria'] = trim($valor);
                    break;
                    
                case 'sku':
                    $data['sku'] = trim($valor);
                    break;
                    
                case 'sucursal':
                    $data['sucursal'] = trim($valor);
                    break;
                    
                case 'region':
                    $data['region'] = trim($valor);
                    break;
                    
                case 'canal_venta':
                    $data['canal_venta'] = trim($valor);
                    break;
                    
                case 'estado':
                    $data['estado'] = trim($valor);
                    break;
                    
                case 'direccion_sucursal':
                    $data['direccion_sucursal'] = trim($valor);
                    break;
                    
                case 'cantidad':
                    $data['cantidad'] = (int) $valor;
                    break;
                    
                case 'precio_unitario':
                    $data['precio_unitario'] = (float) str_replace(['$', ','], '', $valor);
                    break;
                    
                case 'costo':
                    $data['costo'] = (float) str_replace(['$', ','], '', $valor);
                    break;
                    
                case 'margen':
                    $data['margen'] = (float) str_replace(['%'], '', $valor);
                    break;
                    
                case 'estado_venta':
                    $estado = strtolower(trim($valor));
                    if (in_array($estado, ['completada', 'pendiente', 'cancelada'])) {
                        $data['estado_venta'] = $estado;
                    } else {
                        $data['estado_venta'] = 'completada';
                    }
                    break;
                    
                case 'fecha_venta':
                    // Intentar parsear diferentes formatos de fecha
                    $fecha = $this->parsearFecha($valor);
                    if ($fecha) {
                        $data['fecha_venta'] = $fecha;
                    }
                    break;
                    
                case 'hora_venta':
                    $hora = $this->parsearHora($valor);
                    if ($hora) {
                        $data['hora_venta'] = $hora;
                    }
                    break;
                    
                case 'cliente_id':
                    $data['cliente_id'] = trim($valor);
                    break;
                    
                case 'vendedor':
                    $data['vendedor'] = trim($valor);
                    break;
            }
        }

        // Calcular total_venta si no está explícito
        if (!isset($data['total_venta'])) {
            $cantidad = $data['cantidad'] ?? 0;
            $precio = $data['precio_unitario'] ?? 0;
            $data['total_venta'] = $cantidad * $precio;
        }

        // Calcular margen si no está explícito pero tenemos costo y precio
        if (!isset($data['margen']) && isset($data['costo']) && isset($data['precio_unitario'])) {
            $precio = $data['precio_unitario'];
            $costo = $data['costo'];
            if ($precio > 0) {
                $data['margen'] = round((($precio - $costo) / $precio) * 100, 2);
            }
        }

        // Solo crear si tenemos al menos un producto
        if (!empty($data['producto'])) {
            return Venta::create($data);
        }

        return null;
    }

    /**
     * Parsea diferentes formatos de fecha.
     */
    private function parsearFecha($valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        // Intentar con formatos comunes
        $formatos = [
            'Y-m-d',
            'd/m/Y',
            'm/d/Y',
            'd-m-Y',
            'm-d-Y',
            'Y/m/d',
        ];

        foreach ($formatos as $formato) {
            try {
                $fecha = \DateTime::createFromFormat($formato, trim($valor));
                if ($fecha) {
                    return $fecha->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Intentar con strtotime como fallback
        try {
            $timestamp = strtotime(trim($valor));
            if ($timestamp) {
                return date('Y-m-d', $timestamp);
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Parsea diferentes formatos de hora.
     */
    private function parsearHora($valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        $valor = trim($valor);

        // Formato HH:MM:SS o HH:MM
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $valor, $matches)) {
            $hora = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $minuto = $matches[2];
            $segundo = $matches[3] ?? '00';
            return "{$hora}:{$minuto}:{$segundo}";
        }

        // Formato con AM/PM
        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $valor, $matches)) {
            $hora = (int) $matches[1];
            $minuto = $matches[2];
            $ampm = strtoupper($matches[3]);

            if ($ampm === 'PM' && $hora < 12) {
                $hora += 12;
            } elseif ($ampm === 'AM' && $hora === 12) {
                $hora = 0;
            }

            $hora = str_pad($hora, 2, '0', STR_PAD_LEFT);
            return "{$hora}:{$minuto}:00";
        }

        return null;
    }

    /**
     * Reglas de validación para la importación.
     */
    public function rules(): array
    {
        return [
            // No requerimos campos obligatorios para permitir flexibilidad en el mapeo
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
