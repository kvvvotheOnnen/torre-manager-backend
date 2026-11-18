<?php

namespace Features\Marketplace\Domain\UseCases;

use Features\Marketplace\Domain\Entities\PublicacionVisible;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;
use Features\Marketplace\Domain\Services\VisibilidadMarketplaceService;

final class ListarFavoritosMarketplaceUseCase
{
    public function __construct(
        private readonly MarketplaceRepositoryInterface $repository,
        private readonly VisibilidadMarketplaceService $visibilidad,
    ) {}

    /**
     * @return PublicacionVisible[]
     */
    public function execute(string $usuarioId, string $proyectoId): array
    {
        return array_map(
            fn ($publicacion) => $this->visibilidad->para($publicacion, $usuarioId, $proyectoId, true),
            $this->repository->listarFavoritas($usuarioId, $proyectoId),
        );
    }
}
