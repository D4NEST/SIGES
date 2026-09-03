<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MetricasController;

Route::get('/metricas/{tipo}', [MetricasController::class, 'obtenerMetricas']);