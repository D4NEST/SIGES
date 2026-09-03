<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificacionCredenciales;
use App\Http\Controllers\MetricasController;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $totalAdmins = User::where('role', 'admin')->count();
        $totalOperators = User::where('role', 'operador')->count();
        $totalSupervisors = User::where('role', 'supervisor')->count(); 
        
        $users = User::latest()->paginate(10);

        return view('admin.users.index', compact('users', 'totalAdmins', 'totalOperators', 'totalSupervisors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|in:admin,operador,supervisor', 
        ]);

        $temporaryPassword = Str::random(10);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($temporaryPassword),
        ]);

        Mail::to($user->email)->send(new NotificacionCredenciales($user, $temporaryPassword));

        $usuarioResponsable = Auth::check() 
            ? Auth::user()->email 
            : 'Sistema/Invitado';

        MetricasController::registrarAuditoria(
            'CREACION_USUARIO',
            'El administrador creó un nuevo usuario desde el panel de control institucional.',
            [
                'usuario_creado' => $user->email, 
                'rol_assigned' => $user->role,
                'creado_por' => $usuarioResponsable
            ]
        );

        return redirect()->back()->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * MÉTODO TUNEADO: Se añadió 'int' a $id para satisfacer al editor.
     */
    public function updateRole(Request $request, int $id) // <--- CAMBIO AQUÍ: 'int'
    {
        $request->validate([
            'role' => 'required|in:admin,operador,supervisor',
        ]);

        $user = User::findOrFail($id);
        $rolAnterior = $user->role;
        $user->role = $request->role;
        $user->save();

        $usuarioResponsable = Auth::check() ? Auth::user()->email : 'Sistema/Invitado';

        MetricasController::registrarAuditoria(
            'MODIFICACION_ROL_PERMISO',
            "Cambio de privilegios: El usuario '{$user->name}' fue modificado.",
            [
                'usuario_afectado' => $user->email,
                'rol_anterior'     => $rolAnterior,
                'rol_nuevo'        => $user->role,
                'modificado_por'   => $usuarioResponsable
            ]
        );

        return redirect()->back()->with('success', "Rol actualizado con éxito.");
    }
}