<?php

namespace App\Providers;

use Features\Acceso\Data\Listeners\NotificarVinculoAsignado;
use Features\Acceso\Data\Listeners\NotificarVinculoCerrado;
use Features\Acceso\Data\Repositories\MembresiaProyectoRepository;
use Features\Acceso\Data\Repositories\VinculoUnidadRepository;
use Features\Acceso\Domain\Events\VinculoAsignado;
use Features\Acceso\Domain\Events\VinculoCerrado;
use Features\Acceso\Domain\Repositories\MembresiaProyectoRepositoryInterface;
use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;
use Features\Archivos\Data\FabricaAlmacenes;
use Features\Archivos\Domain\Contracts\AlmacenArchivos;
use Features\Asistente\Data\Repositories\DocumentoRepository;
use Features\Asistente\Data\Repositories\OllamaRepository;
use Features\Asistente\Data\Services\PdfTextExtractor;
use Features\Asistente\Domain\Repositories\DocumentoRepositoryInterface;
use Features\Asistente\Domain\Repositories\ExtractorPdfInterface;
use Features\Asistente\Domain\Repositories\ModeloIARepositoryInterface;
use Features\Auth\Data\Repositories\AuthRepository;
use Features\Auth\Domain\Repositories\AuthRepositoryInterface;
use Features\Correo\Data\Repositories\CorreoResendRepository;
use Features\Correo\Domain\Repositories\CorreoRepositoryInterface;
use Features\Dashboard\Data\AutorizadorCapacidadesPorRol;
use Features\Dashboard\Data\CatalogoFuncionesEnConfig;
use Features\Dashboard\Data\Repositories\DashboardRepository;
use Features\Dashboard\Data\ResolutorAmbitoUnidad;
use Features\Dashboard\Data\VerificadorModuloProyecto;
use Features\Dashboard\Domain\Contracts\AutorizadorCapacidades;
use Features\Dashboard\Domain\Contracts\ResolutorAmbito;
use Features\Dashboard\Domain\Contracts\VerificadorModulo;
use Features\Dashboard\Domain\Repositories\CatalogoFuncionesRepositoryInterface;
use Features\Dashboard\Domain\Repositories\DashboardRepositoryInterface;
use Features\Infraestructura\Data\Repositories\InfraestructuraRepository;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;
use Features\Marketplace\Data\Listeners\NotificarPublicacionAprobada;
use Features\Marketplace\Data\Listeners\NotificarPublicacionEnRevision;
use Features\Marketplace\Data\Listeners\NotificarPublicacionModerada;
use Features\Marketplace\Data\Listeners\NotificarPublicacionRechazada;
use Features\Marketplace\Data\Repositories\MarketplaceRepository;
use Features\Marketplace\Domain\Events\PublicacionAprobadaMarketplace;
use Features\Marketplace\Domain\Events\PublicacionEliminadaPorModeracion;
use Features\Marketplace\Domain\Events\PublicacionEnviadaARevision;
use Features\Marketplace\Domain\Events\PublicacionRechazadaMarketplace;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;
use Features\Notificaciones\Data\Repositories\NotificacionesRepository;
use Features\Notificaciones\Domain\Repositories\NotificacionesRepositoryInterface;
use Features\Paqueteria\Data\Listeners\NotificarPaqueteRegistrado;
use Features\Paqueteria\Data\Listeners\NotificarPaqueteRetirado;
use Features\Paqueteria\Data\Repositories\PaqueteriaRepository;
use Features\Paqueteria\Domain\Events\PaqueteRegistrado;
use Features\Paqueteria\Domain\Events\PaqueteRetirado;
use Features\Paqueteria\Domain\Repositories\PaqueteriaRepositoryInterface;
use Features\Residente\Data\Repositories\ResidenteRepository;
use Features\Residente\Domain\Repositories\ResidenteRepositoryInterface;
use Features\Servicios\Data\Listeners\NotificarTrabajadorPublicado;
use Features\Servicios\Data\Repositories\ServiciosRepository;
use Features\Servicios\Domain\Events\TrabajadorPublicado;
use Features\Servicios\Domain\Repositories\ServiciosRepositoryInterface;
use Features\SolicitudesResidente\Data\Listeners\NotificarSolicitudAprobada;
use Features\SolicitudesResidente\Data\Listeners\NotificarSolicitudRechazada;
use Features\SolicitudesResidente\Data\Repositories\SolicitudResidenteRepository;
use Features\SolicitudesResidente\Domain\Events\SolicitudAprobada;
use Features\SolicitudesResidente\Domain\Events\SolicitudRechazada;
use Features\SolicitudesResidente\Domain\Repositories\SolicitudResidenteRepositoryInterface;
use Features\Superadmin\Data\Repositories\SuperadminRepository;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MembresiaProyectoRepositoryInterface::class, MembresiaProyectoRepository::class);
        $this->app->bind(SolicitudResidenteRepositoryInterface::class, SolicitudResidenteRepository::class);
        $this->app->bind(VinculoUnidadRepositoryInterface::class, VinculoUnidadRepository::class);
        $this->app->bind(DocumentoRepositoryInterface::class, DocumentoRepository::class);
        $this->app->bind(ExtractorPdfInterface::class, PdfTextExtractor::class);
        $this->app->bind(ModeloIARepositoryInterface::class, OllamaRepository::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(CorreoRepositoryInterface::class, CorreoResendRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(CatalogoFuncionesRepositoryInterface::class, CatalogoFuncionesEnConfig::class);
        $this->app->bind(AutorizadorCapacidades::class, AutorizadorCapacidadesPorRol::class);
        $this->app->bind(ResolutorAmbito::class, ResolutorAmbitoUnidad::class);
        $this->app->bind(VerificadorModulo::class, VerificadorModuloProyecto::class);
        $this->app->bind(AlmacenArchivos::class, fn (): AlmacenArchivos => (new FabricaAlmacenes)->crear());
        $this->app->bind(InfraestructuraRepositoryInterface::class, InfraestructuraRepository::class);
        $this->app->bind(PaqueteriaRepositoryInterface::class, PaqueteriaRepository::class);
        $this->app->bind(ServiciosRepositoryInterface::class, ServiciosRepository::class);
        $this->app->bind(MarketplaceRepositoryInterface::class, MarketplaceRepository::class);
        $this->app->bind(NotificacionesRepositoryInterface::class, NotificacionesRepository::class);
        $this->app->bind(ResidenteRepositoryInterface::class, ResidenteRepository::class);
        $this->app->bind(SuperadminRepositoryInterface::class, SuperadminRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(PaqueteRegistrado::class, NotificarPaqueteRegistrado::class);
        Event::listen(PaqueteRetirado::class, NotificarPaqueteRetirado::class);
        Event::listen(PublicacionEnviadaARevision::class, NotificarPublicacionEnRevision::class);
        Event::listen(PublicacionAprobadaMarketplace::class, NotificarPublicacionAprobada::class);
        Event::listen(PublicacionRechazadaMarketplace::class, NotificarPublicacionRechazada::class);
        Event::listen(PublicacionEliminadaPorModeracion::class, NotificarPublicacionModerada::class);
        Event::listen(TrabajadorPublicado::class, NotificarTrabajadorPublicado::class);
        Event::listen(SolicitudAprobada::class, NotificarSolicitudAprobada::class);
        Event::listen(SolicitudRechazada::class, NotificarSolicitudRechazada::class);
        Event::listen(VinculoAsignado::class, NotificarVinculoAsignado::class);
        Event::listen(VinculoCerrado::class, NotificarVinculoCerrado::class);
    }
}
