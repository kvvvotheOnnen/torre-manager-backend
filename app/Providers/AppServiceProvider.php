<?php

namespace App\Providers;

use Features\Auth\Data\Repositories\AuthRepository;
use Features\Auth\Domain\Repositories\AuthRepositoryInterface;
use Features\Infraestructura\Data\Repositories\InfraestructuraRepository;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;
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
        $this->app->bind(InfraestructuraRepositoryInterface::class, InfraestructuraRepository::class);
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
