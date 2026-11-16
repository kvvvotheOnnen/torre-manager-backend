<?php

namespace Features\Acceso\Domain\UseCases;

use Features\Acceso\Domain\Entities\VinculoUnidad;
use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;

final class ListarVinculosUnidadUseCase
{
    public function __construct(
        private readonly VinculoUnidadRepositoryInterface $vinculos,
    ) {}

    /** @return VinculoUnidad[] */
    public function execute(string $unidadPrincipalId): array
    {
        return $this->vinculos->listarPorUnidad($unidadPrincipalId);
    }
}
