<?php

namespace Features\Marketplace\Domain\UseCases;

use Features\Marketplace\Domain\Entities\CategoriaProducto;
use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Features\Marketplace\Domain\Entities\PublicacionVisible;
use Features\Marketplace\Domain\Exceptions\AccionMarketplaceNoPermitidaException;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;
use Features\Marketplace\Domain\Services\VisibilidadMarketplaceService;

final class ListarPublicacionesMarketplaceUseCase
{
    public function __construct(
        private readonly MarketplaceRepositoryInterface $repository,
        private readonly VisibilidadMarketplaceService $visibilidad,
    ) {}

    /**
     * @return PublicacionVisible[]
     */
    public function execute(
        string $proyectoId,
        string $usuarioId,
        ?CategoriaProducto $categoria = null,
        ?string $buscar = null,
        ?EstadoPublicacion $estado = null,
        bool $todas = false,
    ): array {
        $puedeGestionar = $this->visibilidad->puedeGestionar($usuarioId, $proyectoId);

        if ($todas && ! $puedeGestionar) {
            throw AccionMarketplaceNoPermitidaException::becauseAdminOnly();
        }

        if ($estado !== null && ! $estado->estaDisponible() && ! $puedeGestionar) {
            throw AccionMarketplaceNoPermitidaException::becauseAdminOnly();
        }

        $favoritas = array_flip($this->repository->idsFavoritas($usuarioId, $proyectoId));

        return array_map(
            fn ($publicacion) => $this->visibilidad->para(
                $publicacion,
                $usuarioId,
                $proyectoId,
                isset($favoritas[$publicacion->id()]),
            ),
            $this->repository->listar(
                proyectoId: $proyectoId,
                categoria: $categoria,
                buscar: $buscar,
                estado: $estado,
                todas: $todas,
                excluirUsuarioId: $usuarioId,
            ),
        );
    }
}
