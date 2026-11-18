<?php

namespace Features\Marketplace\Domain\UseCases;

use Features\Acceso\Domain\Services\AccesoProyectoService;
use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Features\Marketplace\Domain\Events\PublicacionEliminadaPorModeracion;
use Features\Marketplace\Domain\Exceptions\AccionMarketplaceNoPermitidaException;
use Features\Marketplace\Domain\Exceptions\MotivoModeracionRequeridoException;
use Features\Marketplace\Domain\Exceptions\PublicacionNoEditableException;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;
use Features\Marketplace\Domain\Services\VisibilidadMarketplaceService;
use Illuminate\Support\Facades\DB;

final class EliminarPublicacionMarketplaceUseCase
{
    public function __construct(
        private readonly MarketplaceRepositoryInterface $repository,
        private readonly VisibilidadMarketplaceService $visibilidad,
        private readonly AccesoProyectoService $acceso,
    ) {}

    public function execute(string $id, string $proyectoId, string $moderadorId, string $motivo): void
    {
        $publicacion = $this->repository->obtener($id, $proyectoId);

        if ($publicacion->esPropiaDe($moderadorId)) {
            throw AccionMarketplaceNoPermitidaException::becauseUseOwnerAction();
        }

        if ($publicacion->estado() === EstadoPublicacion::Eliminada) {
            throw PublicacionNoEditableException::becauseState();
        }

        $puedeGestionar = $this->visibilidad->puedeGestionar($moderadorId, $proyectoId);

        if (! $puedeGestionar && ! $publicacion->estado()->estaDisponible()) {
            throw PublicacionNoEditableException::becauseState();
        }

        $motivo = trim($motivo);

        if ($motivo === '') {
            throw MotivoModeracionRequeridoException::becauseMissing();
        }

        $rol = $this->acceso->rolEn($moderadorId, $proyectoId)?->value ?? 'desconocido';

        DB::transaction(function () use ($id, $proyectoId, $moderadorId, $motivo, $rol): void {
            $this->repository->actualizar($id, $proyectoId, [
                'estado' => EstadoPublicacion::Eliminada->value,
                'motivo_eliminacion' => $motivo,
                'eliminado_por' => $moderadorId,
                'eliminado_por_rol' => $rol,
                'eliminado_en' => now(),
            ]);
        });

        event(new PublicacionEliminadaPorModeracion(
            publicacionId: $id,
            proyectoId: $proyectoId,
            propietarioId: $publicacion->usuarioId(),
            moderadorId: $moderadorId,
            rolModerador: $rol,
            motivo: $motivo,
        ));
    }
}
