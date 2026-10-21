<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InfraestructuraController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'role:administrador'])->prefix('infraestructura')->group(function (): void {
    Route::get('proyecto', [InfraestructuraController::class, 'miProyecto']);
    Route::post('proyectos', [InfraestructuraController::class, 'crearProyecto']);
    Route::post('agrupaciones', [InfraestructuraController::class, 'crearAgrupacion']);
    Route::post('unidades-accesorias', [InfraestructuraController::class, 'crearUnidadAccesoria']);
    Route::patch('unidades-accesorias/{unidadAccesoriaId}/asignacion', [InfraestructuraController::class, 'asignarUnidadAccesoria']);
    Route::get('arbol', [InfraestructuraController::class, 'arbol']);
    Route::post('vista-previa-identificaciones', [InfraestructuraController::class, 'vistaPreviaIdentificaciones']);
});
