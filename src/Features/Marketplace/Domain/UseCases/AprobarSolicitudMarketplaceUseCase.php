<?php

namespace Features\Marketplace\Domain\UseCases;

use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Features\Marketplace\Domain\Entities\PublicacionVisible;
use Features\Marketplace\Domain\Events\PublicacionAprobadaMarketplace;
use Features\Marketplace\Domain\Exceptions\PublicacionNoEditableException;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;
use Features\Marketplace\Domain\Services\VisibilidadMarketplaceService;

final class AprobarSolicitudMarketplaceUseCase
{
    public function __construct(
        private readonly MarketplaceRepositoryInterface $repository,
        private readonly VisibilidadMarketplaceService $visibilidad,
    ) {}

    public function execute(string $id, string $proyectoId, string $usuarioId): PublicacionVisible
    {
        $publicacion = $this->repository->obtener($id, $proyectoId);

        if (! $publicacion->estado()->esPendiente()) {
            throw PublicacionNoEditableException::becauseState();
        }

        $publicacion = $this->repository->actualizar($id, $proyectoId, [
            'estado' => EstadoPublicacion::Disponible->value,
        ]);

        event(new PublicacionAprobadaMarketplace(
            publicacionId: $publicacion->id(),
            proyectoId: $proyectoId,
            propietarioId: $publicacion->usuarioId(),
            titulo: $publicacion->titulo(),
            aprobadorId: $usuarioId,
        ));

        return $this->visibilidad->para($publicacion, $usuarioId, $proyectoId);
    }
}
