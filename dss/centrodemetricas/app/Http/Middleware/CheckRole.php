<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        // 1. Sin sesión activa
        if (!$user) {
            abort(403, 'Acceso no autorizado.');
        }

        // 2. Usuario desactivado por el administrador
        if (!$user->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta ha sido desactivada.']);
        }

        // 3. Admin bypasea cualquier restricción de rol
        if ($user->role === 'admin') {
            return $next($request);
        }

        // 4. Rol insuficiente — registrar intento en audit_logs
        if ($user->role !== $role) {
            AuditLog::create([
                'user_id'        => $user->id,
                'usuario_nombre' => $user->name,
                'evento'         => 'ACCESO_DENEGADO',
                'descripcion'    => "Intento de acceso no autorizado a ruta restringida para rol '{$role}'.",
                'detalles_extra' => ['rol_usuario' => $user->role, 'rol_requerido' => $role],
                'url_solicitada' => $request->fullUrl(),
                'direccion_ip'   => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);
            abort(403, 'Acceso no autorizado al Centro de Métricas.');
        }

        return $next($request);
    }
}

