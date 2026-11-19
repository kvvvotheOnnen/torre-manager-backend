<?php

namespace Features\Acceso\Domain\UseCases;

use Features\Acceso\Domain\Entities\UnidadContexto;
use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;

final class ListarContextosUnidadesUseCase
{
    public function __construct(
        private readonly VinculoUnidadRepositoryInterface $vinculos,
    ) {}

    /**
     * @param  int[]|null  $proyectoIds  null devuelve todas las unidades (superadmin).
     * @return UnidadContexto[]
     */
    public function execute(?array $proyectoIds = null): array
    {
        return $this->vinculos->contextosDeUnidades($proyectoIds);
    }
}
