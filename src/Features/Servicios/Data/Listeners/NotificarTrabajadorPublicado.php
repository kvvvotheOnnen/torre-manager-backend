<?php

namespace Features\Servicios\Data\Listeners;

use Features\Acceso\Domain\Repositories\MembresiaProyectoRepositoryInterface;
use Features\Auth\Domain\Entities\Rol;
use Features\Notificaciones\Domain\UseCases\CrearNotificacionUseCase;
use Features\Servicios\Domain\Events\TrabajadorPublicado;

final class NotificarTrabajadorPublicado
{
    public function __construct(
        private readonly MembresiaProyectoRepositoryInterface $membresias,
        private readonly CrearNotificacionUseCase $crearNotificacion,
    ) {}

    public function handle(TrabajadorPublicado $event): void
    {
        $destinatarios = $this->membresias->usuariosIdsConRoles(
            $event->proyectoId,
            [Rol::Residente, Rol::Propietario, Rol::Conserje],
        );

        foreach ($destinatarios as $usuarioId) {
            if ($usuarioId === $event->creadoPorId) {
                continue;
            }

            ($this->crearNotificacion)([
                'usuario_id' => $usuarioId,
                'proyecto_id' => $event->proyectoId,
                'tipo' => 'servicios',
                'titulo' => 'Nuevo servicio recomendado',
                'mensaje' => sprintf(
                    'Se publicó un nuevo servicio recomendado: %s (%s).',
                    $event->nombre,
                    $event->tipoServicio,
                ),
                'datos' => ['trabajador_id' => $event->trabajadorId],
            ]);
        }
    }
}
