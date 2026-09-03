<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Importacion;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class AuditoriaProgresoController extends Controller
{
    /**
     * Muestra el dashboard de progreso de carga
     * Para admin: muestra todos los supervisores
     * Para supervisor: muestra sus operadores asignados
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->input('fecha_fin', now()->endOfDay()->format('Y-m-d'));

        if ($user->role === 'admin') {
            $data = $this->getProgresoAdmin($fechaInicio, $fechaFin);
        } else {
            $data = $this->getProgresoSupervisor($user->id, $fechaInicio, $fechaFin);
        }

        return view('admin.auditoria-progreso.index', array_merge($data, [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
        ]));
    }

    /**
     * Obtiene datos de progreso para administradores
     */
    private function getProgresoAdmin(string $fechaInicio, string $fechaFin): array
    {
        // Obtener todos los supervisores
        $supervisores = User::where('role', 'supervisor')
            ->where('active', true)
            ->get();

        $progreso = $supervisores->map(function ($supervisor) use ($fechaInicio, $fechaFin) {
            $cargas = Importacion::where('user_id', $supervisor->id)
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->get();

            $uploads = Upload::where('user_id', $supervisor->id)
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->get();

            $totalCargas = $cargas->count() + $uploads->count();
            $exitosas = $cargas->where('estado', 'completado')->count() + 
                       $uploads->where('status', 'completed')->count();
            $conErrores = $cargas->whereIn('estado', ['error', 'fallido'])->count() + 
                         $uploads->where('status', 'failed')->count();

            $totalFilas = $cargas->sum('total_filas') + $uploads->sum('total_rows');
            $filasProcesadas = $cargas->sum('filas_insertadas') + 
                              $cargas->sum('filas_actualizadas') + 
                              $uploads->sum('processed_rows');

            return [
                'id' => $supervisor->id,
                'nombre' => $supervisor->name,
                'email' => $supervisor->email,
                'rol' => 'supervisor',
                'total_cargas' => $totalCargas,
                'exitosas' => $exitosas,
                'con_errores' => $conErrores,
                'pendientes' => $totalCargas - $exitosas - $conErrores,
                'total_filas' => $totalFilas,
                'filas_procesadas' => $filasProcesadas,
                'porcentaje' => $totalFilas > 0 ? round(($filasProcesadas / $totalFilas) * 100, 1) : 0,
                'estado' => $this->determinarEstado($totalCargas, $exitosas, $conErrores),
                'ultima_actividad' => $cargas->max('created_at') ?? $uploads->max('created_at'),
            ];
        });

        // KPIs generales
        $kpis = [
            'total_usuarios' => $supervisores->count(),
            'completados' => $progreso->where('estado', 'completado')->count(),
            'en_progreso' => $progreso->where('estado', 'en_progreso')->count(),
            'sin_iniciar' => $progreso->where('estado', 'sin_iniciar')->count(),
            'con_errores' => $progreso->where('estado', 'con_errores')->count(),
        ];

        return [
            'usuarios' => $progreso->sortBy('porcentaje')->values(),
            'kpis' => $kpis,
            'tipo_vista' => 'admin',
        ];
    }

    /**
     * Obtiene datos de progreso para supervisores
     */
    private function getProgresoSupervisor(int $supervisorId, string $fechaInicio, string $fechaFin): array
    {
        // Obtener operadores asignados (asumiendo relación por campo supervisor_id o similar)
        $operadores = User::where('role', 'operador')
            ->where('active', true)
            ->get();

        $progreso = $operadores->map(function ($operador) use ($fechaInicio, $fechaFin) {
            $cargas = Importacion::where('user_id', $operador->id)
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->get();

            $uploads = Upload::where('user_id', $operador->id)
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->get();

            $totalCargas = $cargas->count() + $uploads->count();
            $exitosas = $cargas->where('estado', 'completado')->count() + 
                       $uploads->where('status', 'completed')->count();
            $conErrores = $cargas->whereIn('estado', ['error', 'fallido'])->count() + 
                         $uploads->where('status', 'failed')->count();

            $totalFilas = $cargas->sum('total_filas') + $uploads->sum('total_rows');
            $filasProcesadas = $cargas->sum('filas_insertadas') + 
                              $cargas->sum('filas_actualizadas') + 
                              $uploads->sum('processed_rows');

            return [
                'id' => $operador->id,
                'nombre' => $operador->name,
                'email' => $operador->email,
                'rol' => 'operador',
                'total_cargas' => $totalCargas,
                'exitosas' => $exitosas,
                'con_errores' => $conErrores,
                'pendientes' => $totalCargas - $exitosas - $conErrores,
                'total_filas' => $totalFilas,
                'filas_procesadas' => $filasProcesadas,
                'porcentaje' => $totalFilas > 0 ? round(($filasProcesadas / $totalFilas) * 100, 1) : 0,
                'estado' => $this->determinarEstado($totalCargas, $exitosas, $conErrores),
                'ultima_actividad' => $cargas->max('created_at') ?? $uploads->max('created_at'),
            ];
        });

        $kpis = [
            'total_usuarios' => $operadores->count(),
            'completados' => $progreso->where('estado', 'completado')->count(),
            'en_progreso' => $progreso->where('estado', 'en_progreso')->count(),
            'sin_iniciar' => $progreso->where('estado', 'sin_iniciar')->count(),
            'con_errores' => $progreso->where('estado', 'con_errores')->count(),
        ];

        return [
            'usuarios' => $progreso->sortBy('porcentaje')->values(),
            'kpis' => $kpis,
            'tipo_vista' => 'supervisor',
        ];
    }

    /**
     * Determina el estado basado en las cargas
     */
    private function determinarEstado(int $total, int $exitosas, int $errores): string
    {
        if ($total === 0) {
            return 'sin_iniciar';
        }
        if ($errores > $exitosas) {
            return 'con_errores';
        }
        if ($exitosas === $total) {
            return 'completado';
        }
        return 'en_progreso';
    }

    /**
     * Obtiene detalles de cargas de un usuario específico
     */
    public function detalles(User $usuario, Request $request): JsonResponse
    {
        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->input('fecha_fin', now()->endOfDay()->format('Y-m-d'));

        $importaciones = Importacion::where('user_id', $usuario->id)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($imp) {
                return [
                    'id' => $imp->id,
                    'tipo' => 'importacion',
                    'archivo' => $imp->nombre_archivo,
                    'estado' => $imp->estado,
                    'total_filas' => $imp->total_filas,
                    'insertadas' => $imp->filas_insertadas,
                    'actualizadas' => $imp->filas_actualizadas,
                    'rechazadas' => $imp->filas_rechazadas,
                    'errores' => $imp->log_errores,
                    'fecha' => $imp->created_at->format('d/m/Y H:i'),
                ];
            });

        $uploads = Upload::where('user_id', $usuario->id)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($up) {
                return [
                    'id' => $up->id,
                    'tipo' => 'upload',
                    'archivo' => $up->original_name ?? $up->filename,
                    'estado' => $up->status,
                    'total_filas' => $up->total_rows,
                    'procesadas' => $up->processed_rows,
                    'fecha' => $up->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'usuario' => [
                'nombre' => $usuario->name,
                'email' => $usuario->email,
            ],
            'cargas' => $importaciones->concat($uploads)->sortByDesc('fecha')->values(),
        ]);
    }
}
