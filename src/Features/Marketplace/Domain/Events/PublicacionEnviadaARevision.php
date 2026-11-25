<?php

namespace Features\Marketplace\Domain\Events;

/**
 * Se dispara cuando una publicación de un residente queda pendiente de aprobación.
 */
final readonly class PublicacionEnviadaARevision
{
    public function __construct(
        public string $publicacionId,
        public string $proyectoId,
        public ?string $propietarioId,
        public string $titulo,
    ) {}
}
