<?php

namespace Features\Marketplace\Domain\Events;

/**
 * Se dispara cuando una publicación es eliminada por moderación.
 *
 * Todavía no hay listeners: el evento deja preparado el aviso al dueño y la
 * alerta a los administradores cuando quien modera es un conserje.
 */
final readonly class PublicacionEliminadaPorModeracion
{
    public function __construct(
        public string $publicacionId,
        public string $proyectoId,
        public ?string $propietarioId,
        public string $moderadorId,
        public string $rolModerador,
        public string $motivo,
    ) {}
}
