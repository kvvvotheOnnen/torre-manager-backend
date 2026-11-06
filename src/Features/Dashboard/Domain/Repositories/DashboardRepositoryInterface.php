<?php

namespace Features\Dashboard\Domain\Repositories;

use Features\Dashboard\Domain\Entities\ProyectoConModulos;
use Features\Dashboard\Domain\Exceptions\ProyectoNoDisponibleException;

interface DashboardRepositoryInterface
{
    /**
     * Resuelve el proyecto activo del usuario y sus módulos configurables.
     *
     * @throws ProyectoNoDisponibleException
     */
    public function obtenerProyectoDelUsuario(string $usuarioId, ?string $proyectoIdSolicitado = null): ProyectoConModulos;
}
