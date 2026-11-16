<?php

namespace Features\Acceso\Domain\UseCases;

use Features\Acceso\Domain\Entities\VinculoUnidad;
use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;

final class ListarVinculosUsuarioUseCase
{
    public function __construct(
        private readonly VinculoUnidadRepositoryInterface $vinculos,
    ) {}

    /** @return VinculoUnidad[] */
    public function execute(string $usuarioId, ?string $proyectoId = null): array
    {
        return $this->vinculos->listarPorUsuario($usuarioId, $proyectoId);
    }
}
