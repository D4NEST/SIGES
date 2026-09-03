<?php

namespace App\Livewire;

use App\Services\AuditService;
use App\Services\MetricsService;
use Livewire\Component;

class DashboardMetricas extends Component
{
    // Propiedades públicas reactivas — Livewire las serializa automáticamente
    public array $resumen = [];
    public array $ticketPromedio = [];
    public array $porRegion = [];
    public array $porCanal = [];
    public array $porCategoria = [];
    public array $porEstado = [];
    public array $topMasVendidos = [];
    public array $topMenosVendidos = [];
    public array $topPorIngresos = [];
    public array $mayorMargen = [];
    public array $porHora = [];
    public array $tendencia = [];
    public array $porVendedor = [];
    public array $comparativaMensual = [];

    public function mount(MetricsService $metrics, AuditService $audit): void
    {
        $audit->registrar('ACCESO_DASHBOARD', 'Usuario cargó el dashboard de ventas.');
        $this->cargarMetricas($metrics);
    }

    /**
     * Livewire llama este método automáticamente cada 30 segundos
     * gracias a wire:poll.30s en la vista.
     */
    public function actualizarMetricas(MetricsService $metrics): void
    {
        $this->cargarMetricas($metrics);
    }

    private function cargarMetricas(MetricsService $metrics): void
    {
        $resumen = $metrics->resumenDashboard();

        // toArray() convierte la Collection a array plano para serialización de Livewire
        $this->resumen = $resumen['resumen'];
        $this->ticketPromedio = $resumen['ticket_promedio'];
        $this->porRegion = $resumen['por_region']->toArray();
        $this->porCanal = $resumen['por_canal']->toArray();
        $this->porCategoria = $resumen['por_categoria']->toArray();
        $this->porEstado = $resumen['por_estado']->toArray();
        $this->topMasVendidos = $resumen['top_mas_vendidos']->toArray();
        $this->topMenosVendidos = $resumen['top_menos_vendidos']->toArray();
        $this->topPorIngresos = $resumen['top_por_ingresos']->toArray();
        $this->mayorMargen = $resumen['mayor_margen']->toArray();
        $this->porHora = $resumen['por_hora']->toArray();
        $this->tendencia = $resumen['tendencia']->toArray();
        $this->porVendedor = $resumen['por_vendedor']->toArray();
        $this->comparativaMensual = $resumen['comparativa_mensual'];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard-metricas');
    }
}
