<?php

namespace Features\Marketplace\Domain\UseCases;

use Features\Marketplace\Domain\Entities\CategoriaProducto;
use Features\Marketplace\Domain\Entities\CondicionProducto;
use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Features\Marketplace\Domain\Entities\PublicacionVisible;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;
use Features\Marketplace\Domain\Services\VisibilidadMarketplaceService;

final class CrearPublicacionMarketplaceUseCase
{
    public function __construct(
        private readonly MarketplaceRepositoryInterface $repository,
        private readonly VisibilidadMarketplaceService $visibilidad,
    ) {}

    public function execute(
        string $proyectoId,
        string $usuarioId,
        string $titulo,
        string $descripcion,
        int $precio,
        CategoriaProducto $categoria,
        CondicionProducto $condicion,
        ?string $fotoPath,
    ): PublicacionVisible {
        $publicacion = $this->repository->crear([
            'proyecto_id' => $proyectoId,
            'usuario_id' => $usuarioId,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'categoria' => $categoria->value,
            'condicion' => $condicion->value,
            'estado' => EstadoPublicacion::Disponible->value,
            'foto_path' => $fotoPath,
        ]);

        return $this->visibilidad->para($publicacion, $usuarioId, $proyectoId, false);
    }
}
