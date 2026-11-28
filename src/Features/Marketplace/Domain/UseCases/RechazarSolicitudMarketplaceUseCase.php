<?php

namespace Features\Marketplace\Domain\UseCases;

use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Features\Marketplace\Domain\Entities\PublicacionVisible;
use Features\Marketplace\Domain\Events\PublicacionRechazadaMarketplace;
use Features\Marketplace\Domain\Exceptions\MotivoRechazoRequeridoException;
use Features\Marketplace\Domain\Exceptions\PublicacionNoEditableException;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;
use Features\Marketplace\Domain\Services\VisibilidadMarketplaceService;

final class RechazarSolicitudMarketplaceUseCase
{
    public function __construct(
        private readonly MarketplaceRepositoryInterface $repository,
        private readonly VisibilidadMarketplaceService $visibilidad,
    ) {}

    public function execute(string $id, string $proyectoId, string $usuarioId, string $motivo): PublicacionVisible
    {
        $publicacion = $this->repository->obtener($id, $proyectoId);

        if (! $publicacion->estado()->esPendiente()) {
            throw PublicacionNoEditableException::becauseState();
        }

        $motivo = trim($motivo);

        if ($motivo === '') {
            throw MotivoRechazoRequeridoException::becauseMissing();
        }

        $publicacion = $this->repository->actualizar($id, $proyectoId, [
            'estado' => EstadoPublicacion::Rechazada->value,
            'motivo_rechazo' => $motivo,
            'rechazado_en' => now(),
        ]);

        event(new PublicacionRechazadaMarketplace(
            publicacionId: $publicacion->id(),
            proyectoId: $proyectoId,
            propietarioId: $publicacion->usuarioId(),
            titulo: $publicacion->titulo(),
            motivo: $motivo,
            revisorId: $usuarioId,
        ));

        return $this->visibilidad->para($publicacion, $usuarioId, $proyectoId);
    }
}
