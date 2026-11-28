<?php

use App\Http\Controllers\Api\AsistenteAdminController;
use App\Http\Controllers\Api\AsistenteResidenteController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FuncionesDashboardController;
use App\Http\Controllers\Api\InfraestructuraController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\NotificacionesController;
use App\Http\Controllers\Api\PaqueteriaController;
use App\Http\Controllers\Api\RegistroResidenteController;
use App\Http\Controllers\Api\ResidenteController;
use App\Http\Controllers\Api\ServiciosController;
use App\Http\Controllers\Api\SolicitudResidenteController;
use App\Http\Controllers\Api\SuperadminController;
use App\Http\Controllers\Api\VinculoUnidadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('throttle:30,1')->prefix('registro')->group(function (): void {
    Route::get('proyectos', [RegistroResidenteController::class, 'proyectos']);
    Route::get('proyectos/{proyecto}/unidades', [RegistroResidenteController::class, 'unidades'])->whereNumber('proyecto');
    Route::post('solicitud', [RegistroResidenteController::class, 'store'])->middleware('throttle:10,1');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::get('mis-proyectos', [SuperadminController::class, 'misProyectos']);
    Route::get('mis-solicitudes-residentes', [SolicitudResidenteController::class, 'misSolicitudes']);
    Route::get('mis-vinculos', [VinculoUnidadController::class, 'misVinculos']);
    Route::get('mis-unidades', [VinculoUnidadController::class, 'misUnidades']);
    Route::get('dashboard/funciones', FuncionesDashboardController::class);
});

Route::middleware(['auth:sanctum', 'role.proyecto:administrador,superadmin,propietario,residente,conserje'])->group(function (): void {
    Route::get('dashboard/modulos', [DashboardController::class, 'modulos']);
});

Route::middleware(['auth:sanctum', 'role.proyecto:administrador', 'modulo:asistente_ia_admin'])->prefix('admin/asistente')->group(function (): void {
    Route::get('documento', [AsistenteAdminController::class, 'mostrar']);
    Route::post('documento', [AsistenteAdminController::class, 'subir']);
    Route::delete('documento', [AsistenteAdminController::class, 'eliminar']);
});

Route::middleware(['auth:sanctum', 'role.proyecto:residente,propietario'])->prefix('residente')->group(function (): void {
    Route::get('mi-informacion', [ResidenteController::class, 'miInformacion']);
    Route::post('asistente/consultas', [AsistenteResidenteController::class, 'consultar']);
});

Route::middleware(['auth:sanctum', 'puede:paqueteria.ver'])->group(function (): void {
    Route::get('paqueteria', [PaqueteriaController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'role.proyecto:administrador,superadmin'])->prefix('admin')->group(function (): void {
    Route::get('unidades/{unidad}/vinculos', [VinculoUnidadController::class, 'index'])->whereNumber('unidad');
    Route::post('unidades/{unidad}/vinculos', [VinculoUnidadController::class, 'store'])->whereNumber('unidad');
    Route::delete('vinculos/{vinculo}', [VinculoUnidadController::class, 'destroy'])->whereNumber('vinculo');
});

Route::middleware(['auth:sanctum', 'role.proyecto:administrador,conserje,superadmin', 'modulo:solicitudes_residente'])->prefix('admin')->group(function (): void {
    Route::get('solicitudes-residentes', [SolicitudResidenteController::class, 'index']);
    Route::get('solicitudes-residentes/historial', [SolicitudResidenteController::class, 'historial']);
    Route::get('solicitudes-residentes/{id}', [SolicitudResidenteController::class, 'show'])->whereNumber('id');
    Route::post('solicitudes-residentes/{id}/aprobar', [SolicitudResidenteController::class, 'aprobar'])->whereNumber('id');
    Route::post('solicitudes-residentes/{id}/rechazar', [SolicitudResidenteController::class, 'rechazar'])->whereNumber('id');
});

Route::middleware(['auth:sanctum', 'puede:paqueteria.gestionar'])->group(function (): void {
    Route::get('paqueteria/unidades', [PaqueteriaController::class, 'unidades']);
    Route::post('paqueteria', [PaqueteriaController::class, 'registrar']);
    Route::put('paqueteria/{id}', [PaqueteriaController::class, 'actualizar'])->whereNumber('id');
    Route::patch('paqueteria/{id}/retirar', [PaqueteriaController::class, 'retirar'])->whereNumber('id');
});

Route::middleware(['auth:sanctum', 'puede:servicios.ver'])->group(function (): void {
    Route::get('servicios', [ServiciosController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'puede:servicios.gestionar'])->group(function (): void {
    Route::post('servicios', [ServiciosController::class, 'store']);
    Route::put('servicios/{id}', [ServiciosController::class, 'update'])->whereNumber('id');
    Route::delete('servicios/{id}', [ServiciosController::class, 'destroy'])->whereNumber('id');
});

Route::middleware(['auth:sanctum', 'puede:marketplace.ver'])->prefix('marketplace')->group(function (): void {
    Route::get('publicaciones', [MarketplaceController::class, 'index']);
    Route::get('publicaciones/{id}', [MarketplaceController::class, 'show'])->whereNumber('id');
});

Route::middleware(['auth:sanctum', 'puede:marketplace.publicar'])->prefix('marketplace')->group(function (): void {
    Route::get('mis-publicaciones', [MarketplaceController::class, 'misPublicaciones']);
    Route::get('historial', [MarketplaceController::class, 'historial']);
    Route::post('publicaciones', [MarketplaceController::class, 'store']);
    Route::put('publicaciones/{id}', [MarketplaceController::class, 'update'])->whereNumber('id');
    Route::post('publicaciones/{id}/retirar', [MarketplaceController::class, 'retirar'])->whereNumber('id');
    Route::post('publicaciones/{id}/vender', [MarketplaceController::class, 'vender'])->whereNumber('id');
});

Route::middleware(['auth:sanctum', 'puede:marketplace.favoritos'])->prefix('marketplace')->group(function (): void {
    Route::get('favoritos', [MarketplaceController::class, 'favoritos']);
    Route::put('publicaciones/{id}/favorito', [MarketplaceController::class, 'favorito'])->whereNumber('id');
});

Route::middleware(['auth:sanctum', 'puede:marketplace.moderar'])->prefix('marketplace')->group(function (): void {
    Route::delete('publicaciones/{id}', [MarketplaceController::class, 'destroy'])->whereNumber('id');
});

Route::middleware(['auth:sanctum', 'puede:marketplace.aprobar'])->prefix('marketplace')->group(function (): void {
    Route::get('solicitudes', [MarketplaceController::class, 'solicitudes']);
    Route::post('publicaciones/{id}/aprobar', [MarketplaceController::class, 'aprobar'])->whereNumber('id');
    Route::post('publicaciones/{id}/rechazar', [MarketplaceController::class, 'rechazar'])->whereNumber('id');
});

Route::middleware(['auth:sanctum', 'puede:notificaciones.ver'])->prefix('notificaciones')->group(function (): void {
    Route::get('/', [NotificacionesController::class, 'index']);
    Route::get('no-leidas', [NotificacionesController::class, 'noLeidas']);
    Route::patch('leer-todas', [NotificacionesController::class, 'leerTodas']);
    Route::patch('{id}/leer', [NotificacionesController::class, 'leer'])->whereNumber('id');
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

Route::middleware(['auth:sanctum', 'role:superadmin', 'modulo:infraestructura'])->prefix('infraestructura')->group(function (): void {
    Route::get('proyecto', [InfraestructuraController::class, 'miProyecto']);
    Route::post('proyectos', [InfraestructuraController::class, 'crearProyecto']);
    Route::post('agrupaciones', [InfraestructuraController::class, 'crearAgrupacion']);
    Route::post('unidades-accesorias', [InfraestructuraController::class, 'crearUnidadAccesoria']);
    Route::post('unidades-accesorias/lote', [InfraestructuraController::class, 'crearUnidadesAccesoriasEnLote']);
    Route::patch('unidades-accesorias/{unidadAccesoriaId}/asignacion', [InfraestructuraController::class, 'asignarUnidadAccesoria']);
    Route::get('arbol', [InfraestructuraController::class, 'arbol']);
    Route::post('vista-previa-identificaciones', [InfraestructuraController::class, 'vistaPreviaIdentificaciones']);
});
