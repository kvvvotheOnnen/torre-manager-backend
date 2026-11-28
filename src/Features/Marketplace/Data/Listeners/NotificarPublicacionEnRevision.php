<?php

namespace Features\Marketplace\Data\Listeners;

use Features\Acceso\Domain\Repositories\MembresiaProyectoRepositoryInterface;
use Features\Auth\Domain\Entities\Rol;
use Features\Marketplace\Domain\Events\PublicacionEnviadaARevision;
use Features\Notificaciones\Domain\UseCases\CrearNotificacionUseCase;

final class NotificarPublicacionEnRevision
{
    public function __construct(
        private readonly MembresiaProyectoRepositoryInterface $membresias,
        private readonly CrearNotificacionUseCase $crearNotificacion,
    ) {}

    public function handle(PublicacionEnviadaARevision $event): void
    {
        $destinatarios = $this->membresias->usuariosIdsConRoles(
            $event->proyectoId,
            [Rol::Administrador, Rol::Conserje],
        );

        foreach ($destinatarios as $usuarioId) {
            ($this->crearNotificacion)([
                'usuario_id' => $usuarioId,
                'proyecto_id' => $event->proyectoId,
                'tipo' => 'marketplace',
                'titulo' => 'Publicación por revisar',
                'mensaje' => sprintf(
                    'Hay una publicación pendiente de aprobación: %s.',
                    $event->titulo,
                ),
                'datos' => ['publicacion_id' => $event->publicacionId],
            ]);
        }
    }
}
