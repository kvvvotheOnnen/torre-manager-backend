<?php

namespace Features\Acceso\Domain\UseCases;

use Features\Acceso\Domain\Entities\UnidadContexto;
use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;

final class ObtenerContextoUnidadUseCase
{
    public function __construct(
        private readonly VinculoUnidadRepositoryInterface $vinculos,
    ) {}

    public function execute(string $unidadPrincipalId): ?UnidadContexto
    {
        return $this->vinculos->contextoUnidad($unidadPrincipalId);
    }
}
