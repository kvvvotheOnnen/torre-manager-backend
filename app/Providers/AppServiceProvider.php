<?php

namespace App\Providers;

use Features\Auth\Data\Repositories\AuthRepository;
use Features\Auth\Domain\Repositories\AuthRepositoryInterface;
use Features\Correo\Data\Repositories\CorreoResendRepository;
use Features\Correo\Domain\Repositories\CorreoRepositoryInterface;
use Features\Dashboard\Data\Repositories\DashboardRepository;
use Features\Dashboard\Domain\Repositories\DashboardRepositoryInterface;
use Features\Infraestructura\Data\Repositories\InfraestructuraRepository;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;
use Features\Residente\Data\Repositories\ResidenteRepository;
use Features\Residente\Domain\Repositories\ResidenteRepositoryInterface;
use Features\Superadmin\Data\Repositories\SuperadminRepository;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(CorreoRepositoryInterface::class, CorreoResendRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(InfraestructuraRepositoryInterface::class, InfraestructuraRepository::class);
        $this->app->bind(ResidenteRepositoryInterface::class, ResidenteRepository::class);
        $this->app->bind(SuperadminRepositoryInterface::class, SuperadminRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
