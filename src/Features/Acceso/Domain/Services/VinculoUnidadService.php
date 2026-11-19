<?php

namespace Features\Acceso\Domain\Services;

use Features\Acceso\Domain\Entities\VinculoUnidad;
use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;

final class VinculoUnidadService
{
    public function __construct(
        private readonly VinculoUnidadRepositoryInterface $vinculos,
    ) {}

    public function esPropietarioActual(string $usuarioId, string $unidadPrincipalId): bool
    {
        return $this->vinculos->esPropietarioActual($unidadPrincipalId, $usuarioId);
    }

    public function esOcupanteActual(string $usuarioId, string $unidadPrincipalId): bool
    {
        return $this->vinculos->esOcupanteActual($unidadPrincipalId, $usuarioId);
    }

    public function propietarioActual(string $unidadPrincipalId): ?VinculoUnidad
    {
        return $this->vinculos->propietarioActual($unidadPrincipalId);
    }

    public function ocupanteActual(string $unidadPrincipalId): ?VinculoUnidad
    {
        return $this->vinculos->ocupanteActual($unidadPrincipalId);
    }
}
