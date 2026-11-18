<?php

namespace Features\Marketplace\Domain\Services;

use Features\Dashboard\Domain\Contracts\AutorizadorCapacidades;
use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Features\Marketplace\Domain\Entities\PublicacionMarketplace;
use Features\Marketplace\Domain\Entities\PublicacionVisible;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;

/**
 * Calcula, en un solo lugar, qué puede hacer el usuario con una publicación.
 *
 * El cliente recibe los flags ya resueltos. Así, cambiar una regla de
 * permisos no obliga a tocar la pantalla ni a repetir comparaciones de rol.
 */
final class VisibilidadMarketplaceService
{
    /** @var array<string, array{gestionar: bool, moderar: bool}> */
    private array $cache = [];

    public function __construct(
        private readonly AutorizadorCapacidades $autorizador,
        private readonly MarketplaceRepositoryInterface $repository,
    ) {}

    public function para(
        PublicacionMarketplace $publicacion,
        string $usuarioId,
        string $proyectoId,
        ?bool $esFavorita = null,
    ): PublicacionVisible {
        $capacidades = $this->capacidades($usuarioId, $proyectoId);
        $esMia = $publicacion->esPropiaDe($usuarioId);
        $disponible = $publicacion->estado()->estaDisponible();
        $editableComoGestor = $capacidades['gestionar']
            && in_array($publicacion->estado(), [EstadoPublicacion::Disponible, EstadoPublicacion::Vendida], true);

        return new PublicacionVisible(
            publicacion: $publicacion,
            esMia: $esMia,
            esFavorita: $esFavorita ?? $this->repository->esFavorita($publicacion->id(), $usuarioId),
            puedeEditar: ($esMia && $disponible) || $editableComoGestor,
            puedeEliminar: ($esMia && $disponible)
                || (! $esMia && ($editableComoGestor || ($capacidades['moderar'] && $disponible))),
            puedeRetirar: $esMia && $disponible,
            puedeMarcarVendida: $esMia && $disponible,
        );
    }

    public function puedeGestionar(string $usuarioId, string $proyectoId): bool
    {
        return $this->capacidades($usuarioId, $proyectoId)['gestionar'];
    }

    /**
     * @return array{gestionar: bool, moderar: bool}
     */
    private function capacidades(string $usuarioId, string $proyectoId): array
    {
        $clave = $usuarioId.'|'.$proyectoId;

        return $this->cache[$clave] ??= [
            'gestionar' => $this->autorizador->puede($usuarioId, $proyectoId, 'marketplace.gestionar'),
            'moderar' => $this->autorizador->puede($usuarioId, $proyectoId, 'marketplace.moderar'),
        ];
    }
}
