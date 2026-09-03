<?php

namespace App\Services;

use App\Models\Venta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MetricsService
{
    /**
     * Resumen general de ventas - KPIs principales.
     * Retorna array con totales y porcentajes.
     */
    public function resumenVentas(): array
    {
        $totalVentas = Venta::count();
        $completadas = Venta::completadas()->count();
        $pendientes = Venta::pendientes()->count();
        $canceladas = Venta::canceladas()->count();
        
        $montoTotal = Venta::completadas()->sum('total_venta');
        $montoPendiente = Venta::pendientes()->sum('total_venta');
        
        $tasaConversion = $totalVentas > 0 
            ? round(($completadas / $totalVentas) * 100, 2) 
            : 0.0;

        return [
            'total_ventas' => $totalVentas,
            'completadas' => $completadas,
            'pendientes' => $pendientes,
            'canceladas' => $canceladas,
            'monto_total' => round($montoTotal, 2),
            'monto_pendiente' => round($montoPendiente, 2),
            'tasa_conversion' => $tasaConversion,
        ];
    }

    /**
     * Ventas totales agrupadas por región.
     */
    public function ventasPorRegion(): Collection
    {
        return Venta::selectRaw('region, COUNT(*) as total_ventas, SUM(total_venta) as monto_total')
            ->whereNotNull('region')
            ->groupBy('region')
            ->orderByDesc('total_ventas')
            ->get();
    }

    /**
     * Ventas totales agrupadas por canal de venta.
     */
    public function ventasPorCanal(): Collection
    {
        return Venta::selectRaw('canal_venta, COUNT(*) as total_ventas, SUM(total_venta) as monto_total')
            ->whereNotNull('canal_venta')
            ->groupBy('canal_venta')
            ->orderByDesc('monto_total')
            ->get();
    }

    /**
     * Ventas totales agrupadas por categoría de producto.
     */
    public function ventasPorCategoria(): Collection
    {
        return Venta::selectRaw('categoria, COUNT(*) as total_ventas, SUM(total_venta) as monto_total, SUM(cantidad) as unidades_vendidas')
            ->whereNotNull('categoria')
            ->groupBy('categoria')
            ->orderByDesc('monto_total')
            ->get();
    }

    /**
     * Distribución de ventas por estado geográfico.
     */
    public function distribucionPorEstado(): Collection
    {
        return Venta::selectRaw('estado, COUNT(*) as total_ventas, SUM(total_venta) as monto_total')
            ->whereNotNull('estado')
            ->groupBy('estado')
            ->orderBy('estado')
            ->get();
    }

    /**
     * Top productos MÁS vendidos por cantidad.
     */
    public function topProductosMasVendidos(int $limite = 10): Collection
    {
        return Venta::selectRaw('producto, SUM(cantidad) as total_unidades, COUNT(*) as total_transacciones, SUM(total_venta) as monto_total')
            ->whereNotNull('producto')
            ->groupBy('producto')
            ->orderByDesc('total_unidades')
            ->limit($limite)
            ->get();
    }

    /**
     * Top productos MENOS vendidos por cantidad.
     */
    public function topProductosMenosVendidos(int $limite = 10): Collection
    {
        return Venta::selectRaw('producto, SUM(cantidad) as total_unidades, COUNT(*) as total_transacciones, SUM(total_venta) as monto_total')
            ->whereNotNull('producto')
            ->groupBy('producto')
            ->orderBy('total_unidades')
            ->limit($limite)
            ->get();
    }

    /**
     * Top productos por monto de ventas (ingresos).
     */
    public function topProductosPorIngresos(int $limite = 10): Collection
    {
        return Venta::selectRaw('producto, SUM(total_venta) as monto_total, SUM(cantidad) as total_unidades, AVG(precio_unitario) as precio_promedio')
            ->whereNotNull('producto')
            ->groupBy('producto')
            ->orderByDesc('monto_total')
            ->limit($limite)
            ->get();
    }

    /**
     * Productos con mayor margen de ganancia.
     */
    public function productosMayorMargen(int $limite = 10): Collection
    {
        return Venta::selectRaw('producto, AVG(margen) as margen_promedio, SUM(total_venta) as monto_total, COUNT(*) as ventas')
            ->whereNotNull('producto')
            ->where('margen', '>', 0)
            ->groupBy('producto')
            ->orderByDesc('margen_promedio')
            ->limit($limite)
            ->get();
    }

    /**
     * Ventas por hora del día - Para análisis de horarios pico.
     */
    public function ventasPorHora(): Collection
    {
        return Venta::selectRaw('HOUR(hora_venta) as hora, COUNT(*) as total_ventas, SUM(total_venta) as monto_total')
            ->whereNotNull('hora_venta')
            ->groupBy(DB::raw('HOUR(hora_venta)'))
            ->orderBy('hora')
            ->get()
            ->map(function ($item) {
                $item->hora_formateada = $item->hora . ':00';
                return $item;
            });
    }

    /**
     * Tendencia de ventas por día (últimos 30 días).
     */
    public function tendenciaVentas(int $dias = 30): Collection
    {
        return Venta::selectRaw('fecha_venta, COUNT(*) as total_ventas, SUM(total_venta) as monto_total, SUM(cantidad) as unidades')
            ->whereNotNull('fecha_venta')
            ->where('fecha_venta', '>=', now()->subDays($dias))
            ->groupBy('fecha_venta')
            ->orderBy('fecha_venta')
            ->get();
    }

    /**
     * Ventas por vendedor.
     */
    public function ventasPorVendedor(): Collection
    {
        return Venta::selectRaw('vendedor, COUNT(*) as total_ventas, SUM(total_venta) as monto_total, AVG(total_venta) as ticket_promedio')
            ->whereNotNull('vendedor')
            ->groupBy('vendedor')
            ->orderByDesc('monto_total')
            ->get();
    }

    /**
     * Ventas por sucursal.
     */
    public function ventasPorSucursal(): Collection
    {
        return Venta::selectRaw('sucursal, region, COUNT(*) as total_ventas, SUM(total_venta) as monto_total')
            ->whereNotNull('sucursal')
            ->groupBy('sucursal', 'region')
            ->orderByDesc('monto_total')
            ->get();
    }

    /**
     * Ticket promedio general.
     */
    public function ticketPromedio(): array
    {
        $promedio = Venta::completadas()->avg('total_venta');
        $minimo = Venta::completadas()->min('total_venta');
        $maximo = Venta::completadas()->max('total_venta');
        
        return [
            'promedio' => round($promedio ?? 0, 2),
            'minimo' => round($minimo ?? 0, 2),
            'maximo' => round($maximo ?? 0, 2),
        ];
    }

    /**
     * Comparativa de ventas: este mes vs mes anterior.
     */
    public function comparativaMensual(): array
    {
        $esteMes = Venta::esteMes()->completadas();
        $mesAnterior = Venta::whereMonth('fecha_venta', now()->subMonth()->month)
                           ->whereYear('fecha_venta', now()->subMonth()->year)
                           ->completadas();

        $ventasEsteMes = $esteMes->count();
        $ventasMesAnterior = $mesAnterior->count();
        
        $montoEsteMes = $esteMes->sum('total_venta');
        $montoMesAnterior = $mesAnterior->sum('total_venta');

        $crecimientoVentas = $ventasMesAnterior > 0 
            ? round((($ventasEsteMes - $ventasMesAnterior) / $ventasMesAnterior) * 100, 2) 
            : 0;
        
        $crecimientoMonto = $montoMesAnterior > 0 
            ? round((($montoEsteMes - $montoMesAnterior) / $montoMesAnterior) * 100, 2) 
            : 0;

        return [
            'este_mes' => [
                'ventas' => $ventasEsteMes,
                'monto' => round($montoEsteMes, 2),
            ],
            'mes_anterior' => [
                'ventas' => $ventasMesAnterior,
                'monto' => round($montoMesAnterior, 2),
            ],
            'crecimiento_ventas' => $crecimientoVentas,
            'crecimiento_monto' => $crecimientoMonto,
        ];
    }

    /**
     * Agrega todas las métricas en un único payload JSON para el dashboard.
     */
    public function resumenDashboard(): array
    {
        return [
            'resumen' => $this->resumenVentas(),
            'ticket_promedio' => $this->ticketPromedio(),
            'por_region' => $this->ventasPorRegion(),
            'por_canal' => $this->ventasPorCanal(),
            'por_categoria' => $this->ventasPorCategoria(),
            'por_estado' => $this->distribucionPorEstado(),
            'top_mas_vendidos' => $this->topProductosMasVendidos(),
            'top_menos_vendidos' => $this->topProductosMenosVendidos(),
            'top_por_ingresos' => $this->topProductosPorIngresos(),
            'mayor_margen' => $this->productosMayorMargen(),
            'por_hora' => $this->ventasPorHora(),
            'tendencia' => $this->tendenciaVentas(),
            'por_vendedor' => $this->ventasPorVendedor(),
            'comparativa_mensual' => $this->comparativaMensual(),
        ];
    }
}
