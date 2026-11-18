<?php

namespace Features\Marketplace\Domain\UseCases;

use Features\Marketplace\Domain\Entities\PublicacionVisible;
use Features\Marketplace\Domain\Exceptions\PublicacionNoEncontradaException;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;
use Features\Marketplace\Domain\Services\VisibilidadMarketplaceService;

final class ObtenerPublicacionMarketplaceUseCase
{
    public function __construct(
        private readonly MarketplaceRepositoryInterface $repository,
        private readonly VisibilidadMarketplaceService $visibilidad,
    ) {}

    public function execute(string $id, string $proyectoId, string $usuarioId): PublicacionVisible
    {
        $publicacion = $this->repository->obtener($id, $proyectoId);
        $visible = $this->visibilidad->para($publicacion, $usuarioId, $proyectoId);

        $oculta = ! $publicacion->estado()->estaDisponible();

        if ($oculta
            && ! $visible->esMia()
            && ! $visible->esFavorita()
            && ! $this->visibilidad->puedeGestionar($usuarioId, $proyectoId)) {
            throw PublicacionNoEncontradaException::becauseDoesNotExist();
        }

        return $visible;
    }
}
