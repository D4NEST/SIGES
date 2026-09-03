<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Registra una huella de auditoría inmutable en la base de datos.
     * Puede llamarse desde cualquier controlador, job o middleware (Req 7.1).
     *
     * @param  string       $evento      Identificador en mayúsculas: 'LOGIN', 'CARGA_ARCHIVO', etc.
     * @param  string       $descripcion Texto legible del evento.
     * @param  array|null   $detalles    Datos estructurados adicionales (se almacenan como JSON).
     */
    public function registrar(string $evento, string $descripcion, ?array $detalles = null): void
    {
        $user = Auth::user();

        AuditLog::create([
            'user_id'        => $user?->id,
            'usuario_nombre' => $user?->name ?? 'Sistema/Invitado',
            'evento'         => strtoupper($evento),
            'descripcion'    => $descripcion,
            'detalles_extra' => $detalles,
            'url_solicitada' => request()->fullUrl(),
            'direccion_ip'   => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }
}
