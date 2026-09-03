<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        $users = User::orderBy('created_at', 'desc')->paginate(10);

        return view('admin.users.index', [
            'users'          => $users,
            'totalAdmins'    => User::where('role', 'admin')->count(),
            'totalSupervisors' => User::where('role', 'supervisor')->count(),
            'totalOperators' => User::where('role', 'operador')->where('active', true)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role'  => 'required|in:admin,supervisor,operador',
        ]);

        $password = $this->generarPassword();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'role'     => $data['role'],
            'password' => Hash::make($password),
            'active'   => true,
        ]);

        $this->audit->registrar('CREACION_USUARIO', "Usuario {$user->email} creado con rol {$user->role}.", [
            'user_id' => $user->id,
            'rol'     => $user->role,
        ]);

        return back()->with('success', "Usuario creado. Contraseña temporal: {$password}");
    }

    public function toggleActivo(User $user): RedirectResponse
    {
        // No permitir que un admin se desactive a sí mismo
        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->active = !$user->active;
        $user->save();

        // Si se desactiva, invalidar sesiones activas
        if (!$user->active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $estado = $user->active ? 'activado' : 'desactivado';
        $evento = $user->active ? 'USUARIO_ACTIVADO' : 'USUARIO_DESACTIVADO';

        $this->audit->registrar($evento, "Usuario {$user->email} fue {$estado}.", ['user_id' => $user->id]);

        return back()->with('success', "Usuario {$estado} correctamente.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $email = $user->email;
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->delete();

        $this->audit->registrar('ELIMINACION_USUARIO', "Usuario {$email} eliminado del sistema.");

        return back()->with('success', "Usuario {$email} eliminado.");
    }

    private function generarPassword(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#$';
        return substr(str_shuffle($chars), 0, 10);
    }
}
