<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MetricasController extends Controller
{
    public function __construct(
        private MetricsService $metrics,
        private AuditService   $audit,
    ) {}

    // -------------------------------------------------------------------------
    // Vistas de panel por rol — retornan la vista Blade que carga el dashboard
    // Los datos reales llegan vía fetch() al endpoint /api/metricas/dashboard
    // -------------------------------------------------------------------------

    public function adminDashboard(): \Illuminate\View\View
    {
        $this->audit->registrar('ACCESO_DASHBOARD', 'Administrador ingresó al panel de control global.');
        return view('dashboard.admin');
    }

    public function supervisorDashboard(): \Illuminate\View\View
    {
        $this->audit->registrar('ACCESO_DASHBOARD', 'Supervisor ingresó al módulo de reportes.');
        return view('dashboard.supervisor');
    }

    public function operadorDashboard(): \Illuminate\View\View
    {
        $this->audit->registrar('ACCESO_DASHBOARD', 'Operador ingresó al módulo operativo.');

        $uploads = \App\Models\Upload::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('dashboard.operador', [
            'uploads'      => $uploads,
            'totalUploads' => \App\Models\Upload::where('user_id', Auth::id())->count(),
        ]);
    }

    // -------------------------------------------------------------------------
    // API JSON — consumida por fetch() desde las vistas Blade (Req 6.1 – 6.5)
    // Ruta protegida por middleware auth + role definido en routes/web.php
    // -------------------------------------------------------------------------

    /**
     * GET /api/metricas/dashboard
     * Retorna el resumen completo de métricas en un único payload JSON.
     * Sin parámetros de entrada — el rol del usuario autenticado se valida
     * exclusivamente a nivel de middleware, no aquí.
     */
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->metrics->resumenDashboard(),
        ]);
    }

    /**
     * GET /api/metricas/planta
     * Endpoint granular para actualización parcial del widget de plantas.
     */
    public function porPlanta(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->metrics->totalPorPlanta(),
        ]);
    }

    /**
     * GET /api/metricas/estado
     * Endpoint granular para el widget de distribución geográfica.
     */
    public function porEstado(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->metrics->distribucionPorEstado(),
        ]);
    }

    /**
     * GET /api/metricas/votos
     * Endpoint granular para el widget de inscritos vs votantes.
     */
    public function votos(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->metrics->inscritosVsNoInscritos(),
        ]);
    }
}
