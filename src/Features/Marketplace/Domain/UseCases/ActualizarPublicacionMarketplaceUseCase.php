<?php

namespace Features\Marketplace\Domain\UseCases;

use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Features\Marketplace\Domain\Entities\PublicacionVisible;
use Features\Marketplace\Domain\Exceptions\AccionMarketplaceNoPermitidaException;
use Features\Marketplace\Domain\Exceptions\PublicacionNoEditableException;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;
use Features\Marketplace\Domain\Services\VisibilidadMarketplaceService;

final class ActualizarPublicacionMarketplaceUseCase
{
    public function __construct(
        private readonly MarketplaceRepositoryInterface $repository,
        private readonly VisibilidadMarketplaceService $visibilidad,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     * @return array{publicacion: PublicacionVisible, fotoAnterior: ?string}
     */
    public function execute(string $id, string $proyectoId, string $usuarioId, array $datos): array
    {
        $anterior = $this->repository->obtener($id, $proyectoId);
        $puedeGestionar = $this->visibilidad->puedeGestionar($usuarioId, $proyectoId);

        if (! $anterior->esPropiaDe($usuarioId) && ! $puedeGestionar) {
            throw AccionMarketplaceNoPermitidaException::becauseNotOwner();
        }

        $editable = $anterior->estado()->estaDisponible()
            || ($puedeGestionar && $anterior->estado() === EstadoPublicacion::Vendida);

        if (! $editable) {
            throw PublicacionNoEditableException::becauseState();
        }

        $reemplazaFoto = array_key_exists('foto_path', $datos) && $datos['foto_path'] !== null;
        $publicacion = $this->repository->actualizar($id, $proyectoId, $datos);

        return [
            'publicacion' => $this->visibilidad->para($publicacion, $usuarioId, $proyectoId),
            'fotoAnterior' => $reemplazaFoto ? $anterior->fotoPath() : null,
        ];
    }
}
