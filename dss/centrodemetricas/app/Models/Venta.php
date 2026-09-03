<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $primaryKey = 'id_venta';

    protected $fillable = [
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
        'total_venta',
        'costo',
        'margen',
        'estado_venta',
        'fecha_venta',
        'hora_venta',
        'cliente_id',
        'vendedor',
        'upload_id',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'total_venta' => 'decimal:2',
        'costo' => 'decimal:2',
        'margen' => 'decimal:2',
        'fecha_venta' => 'date',
        'hora_venta' => 'datetime:H:i:s',
    ];

    // --- Scopes para consultas del dashboard ---

    /** Ventas completadas */
    public function scopeCompletadas(Builder $query): Builder
    {
        return $query->where('estado_venta', 'completada');
    }

    /** Ventas pendientes */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado_venta', 'pendiente');
    }

    /** Ventas canceladas */
    public function scopeCanceladas(Builder $query): Builder
    {
        return $query->where('estado_venta', 'cancelada');
    }

    /** Filtrar por región */
    public function scopePorRegion(Builder $query, string $region): Builder
    {
        return $query->where('region', $region);
    }

    /** Filtrar por categoría */
    public function scopePorCategoria(Builder $query, string $categoria): Builder
    {
        return $query->where('categoria', $categoria);
    }

    /** Filtrar por canal de venta */
    public function scopePorCanal(Builder $query, string $canal): Builder
    {
        return $query->where('canal_venta', $canal);
    }

    /** Ventas en un rango de fechas */
    public function scopeEntreFechas(Builder $query, $fechaInicio, $fechaFin): Builder
    {
        return $query->whereBetween('fecha_venta', [$fechaInicio, $fechaFin]);
    }

    /** Ventas de hoy */
    public function scopeHoy(Builder $query): Builder
    {
        return $query->whereDate('fecha_venta', today());
    }

    /** Ventas de este mes */
    public function scopeEsteMes(Builder $query): Builder
    {
        return $query->whereMonth('fecha_venta', now()->month)
                     ->whereYear('fecha_venta', now()->year);
    }

    // --- Relaciones ---

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id');
    }
}
