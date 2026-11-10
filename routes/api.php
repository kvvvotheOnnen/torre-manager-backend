<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InfraestructuraController;
use App\Http\Controllers\Api\PaqueteriaController;
use App\Http\Controllers\Api\ResidenteController;
use App\Http\Controllers\Api\SuperadminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'role:administrador,superadmin'])->group(function (): void {
    Route::get('mis-proyectos', [SuperadminController::class, 'misProyectos']);
    Route::get('dashboard/modulos', [DashboardController::class, 'modulos']);
});

Route::middleware(['auth:sanctum', 'role:residente'])->prefix('residente')->group(function (): void {
    Route::get('mi-informacion', [ResidenteController::class, 'miInformacion']);
});

Route::middleware(['auth:sanctum', 'role:residente,conserje,administrador,superadmin'])->group(function (): void {
    Route::get('paqueteria', [PaqueteriaController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'role:conserje,administrador,superadmin'])->group(function (): void {
    Route::get('paqueteria/unidades', [PaqueteriaController::class, 'unidades']);
    Route::post('paqueteria', [PaqueteriaController::class, 'registrar']);
    Route::put('paqueteria/{id}', [PaqueteriaController::class, 'actualizar'])->whereNumber('id');
    Route::patch('paqueteria/{id}/retirar', [PaqueteriaController::class, 'retirar'])->whereNumber('id');
});

Route::middleware(['auth:sanctum', 'role:superadmin'])->prefix('superadmin')->group(function (): void {
    Route::get('administradores', [SuperadminController::class, 'administradores']);
    Route::post('administradores', [SuperadminController::class, 'crearAdministrador']);
    Route::get('proyectos', [SuperadminController::class, 'proyectos']);
    Route::post('proyectos', [SuperadminController::class, 'crearProyecto']);
    Route::post('proyectos/borrador', [SuperadminController::class, 'iniciarProyectoBorrador']);
    Route::patch('proyectos/{proyecto}/borrador', [SuperadminController::class, 'guardarPasoProyecto'])->whereNumber('proyecto');
    Route::delete('proyectos/{proyecto}/borrador', [SuperadminController::class, 'descartarProyectoBorrador'])->whereNumber('proyecto');
    Route::get('proyectos/{proyecto}', [SuperadminController::class, 'verProyecto'])->whereNumber('proyecto');
    Route::put('proyectos/{proyecto}', [SuperadminController::class, 'actualizarProyecto'])->whereNumber('proyecto');
    Route::delete('proyectos/{proyecto}', [SuperadminController::class, 'eliminarProyecto'])->whereNumber('proyecto');
});

Route::middleware(['auth:sanctum', 'role:superadmin'])->prefix('infraestructura')->group(function (): void {
    Route::get('proyecto', [InfraestructuraController::class, 'miProyecto']);
    Route::post('proyectos', [InfraestructuraController::class, 'crearProyecto']);
    Route::post('agrupaciones', [InfraestructuraController::class, 'crearAgrupacion']);
    Route::post('unidades-accesorias', [InfraestructuraController::class, 'crearUnidadAccesoria']);
    Route::post('unidades-accesorias/lote', [InfraestructuraController::class, 'crearUnidadesAccesoriasEnLote']);
    Route::patch('unidades-accesorias/{unidadAccesoriaId}/asignacion', [InfraestructuraController::class, 'asignarUnidadAccesoria']);
    Route::get('arbol', [InfraestructuraController::class, 'arbol']);
    Route::post('vista-previa-identificaciones', [InfraestructuraController::class, 'vistaPreviaIdentificaciones']);
});
