<?php

namespace Features\Marketplace\Domain\Events;

/**
 * Se dispara cuando una publicación es eliminada por moderación.
 *
 * El listener de notificaciones avisa al dueño de la publicación.
 */
final readonly class PublicacionEliminadaPorModeracion
{
    public function __construct(
        public string $publicacionId,
        public string $proyectoId,
        public ?string $propietarioId,
        public string $titulo,
        public string $moderadorId,
        public string $rolModerador,
        public string $motivo,
    ) {}
}
