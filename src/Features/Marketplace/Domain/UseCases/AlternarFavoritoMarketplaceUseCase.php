<?php

namespace Features\Marketplace\Domain\UseCases;

use Features\Marketplace\Domain\Exceptions\AccionMarketplaceNoPermitidaException;
use Features\Marketplace\Domain\Exceptions\PublicacionNoEditableException;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;

final class AlternarFavoritoMarketplaceUseCase
{
    public function __construct(
        private readonly MarketplaceRepositoryInterface $repository,
    ) {}

    public function execute(string $publicacionId, string $proyectoId, string $usuarioId, bool $favorito): bool
    {
        $publicacion = $this->repository->obtener($publicacionId, $proyectoId);

        if ($publicacion->esPropiaDe($usuarioId)) {
            throw AccionMarketplaceNoPermitidaException::becauseFavoriteOwn();
        }

        $actual = $this->repository->esFavorita($publicacionId, $usuarioId);

        if (! $favorito) {
            if ($actual) {
                $this->repository->quitarFavorito($publicacionId, $usuarioId);
            }

            return false;
        }

        if (! $publicacion->estado()->estaDisponible()) {
            throw PublicacionNoEditableException::becauseState();
        }

        if (! $actual) {
            $this->repository->agregarFavorito($publicacionId, $usuarioId);
        }

        return true;
    }
}
