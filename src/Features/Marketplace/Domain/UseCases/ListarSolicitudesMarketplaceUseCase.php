<?php

namespace Features\Marketplace\Domain\UseCases;

use Features\Marketplace\Domain\Entities\PublicacionVisible;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;
use Features\Marketplace\Domain\Services\VisibilidadMarketplaceService;

final class ListarSolicitudesMarketplaceUseCase
{
    public function __construct(
        private readonly MarketplaceRepositoryInterface $repository,
        private readonly VisibilidadMarketplaceService $visibilidad,
    ) {}

    /**
     * Solicitudes de publicación pendientes de aprobación del proyecto.
     *
     * @return array{pendientes: int, solicitudes: PublicacionVisible[]}
     */
    public function execute(string $proyectoId, string $usuarioId): array
    {
        $solicitudes = array_map(
            fn ($publicacion) => $this->visibilidad->para($publicacion, $usuarioId, $proyectoId),
            $this->repository->listarPendientes($proyectoId),
        );

        return [
            'pendientes' => $this->repository->contarPendientes($proyectoId),
            'solicitudes' => $solicitudes,
        ];
    }
}
