<?php

namespace Features\Marketplace\Domain\Events;

/**
 * Se dispara cuando una solicitud de publicación es aprobada.
 */
final readonly class PublicacionAprobadaMarketplace
{
    public function __construct(
        public string $publicacionId,
        public string $proyectoId,
        public ?string $propietarioId,
        public string $titulo,
        public string $aprobadorId,
    ) {}
}
