<?php

namespace Features\Paqueteria\Data\Listeners;

use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;
use Features\Notificaciones\Domain\UseCases\CrearNotificacionUseCase;
use Features\Paqueteria\Domain\Events\PaqueteRegistrado;

final class NotificarPaqueteRegistrado
{
    public function __construct(
        private readonly VinculoUnidadRepositoryInterface $vinculos,
        private readonly CrearNotificacionUseCase $crearNotificacion,
    ) {}

    public function handle(PaqueteRegistrado $event): void
    {
        $destinatarios = $this->destinatarios($event->unidadPrincipalId, $event->residenteId);

        if ($destinatarios === []) {
            return;
        }

        $unidad = $event->unidadIdentificador ?? 'sin identificar';
        $remitente = trim((string) $event->remitente);

        $mensaje = $remitente !== ''
            ? "Llegó un paquete de {$remitente} para la unidad {$unidad}: {$event->descripcion}."
            : "Llegó un paquete para la unidad {$unidad}: {$event->descripcion}.";

        foreach ($destinatarios as $usuarioId) {
            ($this->crearNotificacion)([
                'usuario_id' => $usuarioId,
                'proyecto_id' => $event->proyectoId,
                'tipo' => 'paqueteria',
                'titulo' => 'Paquete recibido',
                'mensaje' => $mensaje,
                'datos' => ['paquete_id' => $event->paqueteId],
            ]);
        }
    }

    /** @return string[] */
    private function destinatarios(?string $unidadPrincipalId, ?string $residenteId): array
    {
        $usuarios = $unidadPrincipalId !== null
            ? $this->vinculos->usuariosActualesDeUnidad($unidadPrincipalId)
            : [];

        if ($usuarios === [] && $residenteId !== null && $residenteId !== '') {
            $usuarios = [$residenteId];
        }

        return array_values(array_unique($usuarios));
    }
}
