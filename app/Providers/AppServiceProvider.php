<?php

namespace App\Providers;

use Features\Auth\Data\Repositories\AuthRepository;
use Features\Auth\Domain\Repositories\AuthRepositoryInterface;
use Features\Infraestructura\Data\Repositories\InfraestructuraRepository;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
