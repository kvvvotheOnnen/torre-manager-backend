<?php

namespace Features\Marketplace\Domain\UseCases;

use Features\Acceso\Domain\Services\AccesoProyectoService;
use Features\Auth\Domain\Entities\Rol;
use Features\Marketplace\Domain\Entities\CategoriaProducto;
use Features\Marketplace\Domain\Entities\CondicionProducto;
use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Features\Marketplace\Domain\Entities\PublicacionVisible;
use Features\Marketplace\Domain\Events\PublicacionEnviadaARevision;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;
use Features\Marketplace\Domain\Services\VisibilidadMarketplaceService;

final class CrearPublicacionMarketplaceUseCase
{
    public function __construct(
        private readonly MarketplaceRepositoryInterface $repository,
        private readonly VisibilidadMarketplaceService $visibilidad,
        private readonly AccesoProyectoService $acceso,
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
        $rol = $this->acceso->rolEn($usuarioId, $proyectoId);
        $publicaDirecto = $rol !== null
            && in_array($rol, [Rol::Administrador, Rol::Superadmin], true);

        $publicacion = $this->repository->crear([
            'proyecto_id' => $proyectoId,
            'usuario_id' => $usuarioId,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'categoria' => $categoria->value,
            'condicion' => $condicion->value,
            'estado' => $publicaDirecto
                ? EstadoPublicacion::Disponible->value
                : EstadoPublicacion::Pendiente->value,
            'foto_path' => $fotoPath,
        ]);

        if (! $publicaDirecto) {
            event(new PublicacionEnviadaARevision(
                publicacionId: $publicacion->id(),
                proyectoId: $proyectoId,
                propietarioId: $usuarioId,
                titulo: $publicacion->titulo(),
            ));
        }

        return $this->visibilidad->para($publicacion, $usuarioId, $proyectoId, false);
    }
}
