<?php

namespace Features\Acceso\Domain\Repositories;

use Features\Acceso\Domain\Entities\MembresiaProyecto;
use Features\Auth\Domain\Entities\Rol;

interface MembresiaProyectoRepositoryInterface
{
    /** @return MembresiaProyecto[] */
    public function listarPorUsuario(string $usuarioId): array;

    /** @return int[] */
    public function proyectosIds(string $usuarioId): array;

    public function rolEn(string $usuarioId, string $proyectoId): ?Rol;

    public function asegurar(string $proyectoId, string $usuarioId, Rol $rol): MembresiaProyecto;
}
