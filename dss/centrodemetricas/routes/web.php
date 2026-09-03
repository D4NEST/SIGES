<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MetricasController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\VentasUploadController;
use Illuminate\Support\Facades\Route;

// === RUTA PRINCIPAL ===
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// === DASHBOARD (REDIRECCIONA SEGÚN ROL) ===
Route::get('/dashboard', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    $role = auth()->user()->role;
    return redirect()->route('dashboard.' . $role);
})->name('dashboard');

// === DASHBOARDS POR ROL ===
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/admin', [MetricasController::class, 'adminDashboard'])
        ->middleware('role:admin')->name('dashboard.admin');
    Route::get('/dashboard/supervisor', [MetricasController::class, 'supervisorDashboard'])
        ->middleware('role:supervisor')->name('dashboard.supervisor');
    Route::get('/dashboard/operador', [MetricasController::class, 'operadorDashboard'])
        ->middleware('role:operador')->name('dashboard.operador');
});

// === PERFIL ===
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// === VENTAS UPLOAD (NUEVO SISTEMA) ===
Route::middleware(['auth'])->group(function () {
    Route::get('/ventas/upload', [VentasUploadController::class, 'index'])->name('ventas.upload.index');
    Route::post('/ventas/upload/parse', [VentasUploadController::class, 'parse'])->name('ventas.upload.parse');
    Route::post('/ventas/upload/process', [VentasUploadController::class, 'processMapping'])->name('ventas.upload.process');
    Route::get('/ventas/upload/mapping/{uploadId}', [VentasUploadController::class, 'showMappingWizard'])->name('ventas.upload.mapping');
});

// === UPLOADS (MANTENER COMPATIBILIDAD) ===
Route::middleware(['auth'])->group(function () {
    Route::get('/uploads', [VentasUploadController::class, 'index'])->name('uploads.index');
    Route::post('/uploads/parse', [VentasUploadController::class, 'parse'])->name('uploads.parse');
    Route::post('/uploads/process', [VentasUploadController::class, 'processMapping'])->name('uploads.process');
});

// === ADMIN: USUARIOS Y AUDITORÍA ===
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Usuarios
    Route::get('/users', [UsuarioController::class, 'index'])->name('users.index');
    Route::post('/users', [UsuarioController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/toggle', [UsuarioController::class, 'toggleActivo'])->name('users.toggle');
    Route::delete('/users/{user}', [UsuarioController::class, 'destroy'])->name('users.destroy');
    
    // Auditoría
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
});

// === AUTH ===
require __DIR__.'/auth.php';
