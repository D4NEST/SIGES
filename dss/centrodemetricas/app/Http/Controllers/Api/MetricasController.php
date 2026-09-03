<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Registro;

class MetricasController extends Controller
{
    public function obtenerMetricas(string $tipo)
    {
        try {
            switch ($tipo) {
                case 'consolidado':
                    $data = $this->consolidadoGeneral();
                    break;
                case 'planta_1':
                    $data = $this->porPlanta(1);
                    break;
                case 'votos_estado':
                    $data = $this->porEstado();
                    break;
                case 'cedulas_horas':
                    $data = $this->porHorario();
                    break;
                default:
                    $data = $this->consolidadoGeneral();
            }

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error', 'mensaje' => $e->getMessage()], 500);
        }
    }

    private function consolidadoGeneral(): array
    {
        return [
            'total_empleados' => Registro::count(),
            'votaron' => Registro::where('estatus', 'votó')->count(),
            'pendientes' => Registro::where('estatus', 'pendiente')->count(),
            'no_inscritos' => Registro::where('estatus', 'no_inscrito')->count(),
            'labels' => ['Votaron', 'Pendientes', 'No Inscritos']
        ];
    }

    private function porPlanta(int $id): array
    {
        $query = Registro::where('planta_id', $id);
        return [
            'total' => $query->count(),
            'votaron' => $query->where('estatus', 'votó')->count(),
            'pendientes' => $query->where('estatus', 'pendiente')->count(),
        ];
    }

    private function porEstado()
    {
        return Registro::select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get();
    }

    private function porHorario()
    {
        return Registro::select(DB::raw('HOUR(created_at) as hora'), DB::raw('count(*) as total'))
            ->groupBy('hora')
            ->orderBy('hora')
            ->get();
    }
}