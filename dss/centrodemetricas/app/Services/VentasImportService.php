<?php

namespace App\Services;

use App\Models\Importacion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class VentasImportService
{
    /**
     * Estructura de columnas esperadas para importación de ventas.
     */
    public const ESTRUCTURA_VENTAS = [
        'producto',
        'categoria',
        'sku',
        'sucursal',
        'region',
        'canal_venta',
        'estado',
        'direccion_sucursal',
        'cantidad',
        'precio_unitario',
        'costo',
        'margen',
        'estado_venta',
        'fecha_venta',
        'hora_venta',
        'cliente_id',
        'vendedor',
    ];

    /**
     * Diccionario: cabecera normalizada del Excel → campo en tabla ventas.
     */
    public const TRADUCTOR_CAMPOS_BD = [
        'producto'           => 'producto',
        'categoria'          => 'categoria',
        'sku'                => 'sku',
        'codigo'             => 'sku',
        'sucursal'           => 'sucursal',
        'tienda'             => 'sucursal',
        'region'             => 'region',
        'zona'               => 'region',
        'canal_venta'        => 'canal_venta',
        'canal'              => 'canal_venta',
        'estado'             => 'estado',
        'direccion'          => 'direccion_sucursal',
        'direccion_sucursal' => 'direccion_sucursal',
        'cantidad'           => 'cantidad',
        'unidades'           => 'cantidad',
        'precio_unitario'    => 'precio_unitario',
        'precio'             => 'precio_unitario',
        'costo'              => 'costo',
        'margen'             => 'margen',
        'estado_venta'       => 'estado_venta',
        'estatus'            => 'estado_venta',
        'fecha_venta'        => 'fecha_venta',
        'fecha'              => 'fecha_venta',
        'hora_venta'         => 'hora_venta',
        'hora'               => 'hora_venta',
        'cliente_id'         => 'cliente_id',
        'cliente'            => 'cliente_id',
        'vendedor'           => 'vendedor',
        'vende'              => 'vendedor',
    ];

    /**
     * Calcula el hash SHA-256 del contenido del archivo para detección de duplicados.
     */
    public function calcularHash(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }

    /**
     * Comprueba si el archivo ya fue importado comparando el hash.
     */
    public function esArchivoDuplicado(string $hash): bool
    {
        return Importacion::hashExiste($hash);
    }

    /**
     * Normaliza un encabezado de Excel al formato slug usado por Laravel Excel.
     */
    public function normalizarCabecera(string $heading): string
    {
        $limpio = trim(mb_strtolower($heading, 'UTF-8'));
        $limpio = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $limpio);
        $limpio = preg_replace('/[^a-z0-9_]/', '_', $limpio);
        return trim(preg_replace('/_+/', '_', $limpio), '_');
    }

    /**
     * Lee los encabezados de la primera fila del Excel.
     *
     * @return array<int, string>
     */
    public function leerEncabezados(UploadedFile $file): array
    {
        $array = \Maatwebsite\Excel\Facades\Excel::toArray([], $file->getRealPath());
        return $array[0][0] ?? [];
    }

    /**
     * Valida que el archivo tenga las columnas mínimas necesarias.
     *
     * @param  array<int, string> $headings  Encabezados crudos leídos del Excel
     * @return array{valido: bool, faltantes: string[], mensaje: string}
     */
    public function validarEstructura(array $headings): array
    {
        $normalizados = array_map(fn($h) => $this->normalizarCabecera($h), $headings);
        
        // Mapear a campos de BD
        $camposDetectados = [];
        foreach ($normalizados as $norm) {
            if (isset(self::TRADUCTOR_CAMPOS_BD[$norm])) {
                $camposDetectados[] = self::TRADUCTOR_CAMPOS_BD[$norm];
            }
        }
        
        $camposDetectados = array_unique($camposDetectados);
        
        // Solo 'producto' es obligatorio
        $requeridos = ['producto'];
        $faltantes = array_diff($requeridos, $camposDetectados);
        
        return [
            'valido'     => empty($faltantes),
            'faltantes'  => array_values($faltantes),
            'mensaje'    => empty($faltantes) 
                ? 'Estructura válida para importación de ventas.' 
                : 'Faltan columnas requeridas: ' . implode(', ', $faltantes),
        ];
    }

    /**
     * Construye automáticamente el mapeo encabezado→campo_BD.
     *
     * @param  array<int, string> $headings  Encabezados originales (sin normalizar)
     * @return array<string, string>  ['Nombre Original Excel' => 'campo_bd']
     */
    public function construirMapeoAutomatico(array $headings): array
    {
        $mapeo = [];
        foreach ($headings as $heading) {
            $normalizado = $this->normalizarCabecera($heading);
            $mapeo[$heading] = self::TRADUCTOR_CAMPOS_BD[$normalizado] ?? $normalizado;
        }
        return $mapeo;
    }

    /**
     * Valida que el campo 'producto' esté presente en el mapeo.
     */
    public function validarMapeo(array $mapeo): bool
    {
        return in_array('producto', array_values($mapeo), true);
    }

    /**
     * Retorna los campos disponibles para mapeo manual.
     */
    public function getCamposDisponibles(): array
    {
        return self::ESTRUCTURA_VENTAS;
    }

    /**
     * Valida una muestra de datos antes de la importación completa.
     *
     * @param  array<int, array> $muestraDatos  Primeras N filas del Excel
     * @param  array<string, string> $mapeo
     * @return array<int, string>  Lista de errores
     */
    public function validarMuestraDatos(array $muestraDatos, array $mapeo): array
    {
        $errores = [];
        $columnaProducto = array_search('producto', $mapeo);

        if ($columnaProducto === false) {
            return ['El campo producto no está mapeado.'];
        }

        foreach ($muestraDatos as $i => $fila) {
            $normalizado = $this->normalizarCabecera($columnaProducto);
            $producto = trim($fila[$normalizado] ?? '');

            if (empty($producto)) {
                $errores[] = "Fila " . ($i + 2) . ": producto vacío.";
            }
        }

        return $errores;
    }
}
