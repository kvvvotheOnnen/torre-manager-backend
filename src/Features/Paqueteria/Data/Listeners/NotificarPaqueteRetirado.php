<?php

namespace Features\Paqueteria\Data\Listeners;

use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;
use Features\Notificaciones\Domain\UseCases\CrearNotificacionUseCase;
use Features\Paqueteria\Domain\Events\PaqueteRetirado;

final class NotificarPaqueteRetirado
{
    public function __construct(
        private readonly VinculoUnidadRepositoryInterface $vinculos,
        private readonly CrearNotificacionUseCase $crearNotificacion,
    ) {}

    public function handle(PaqueteRetirado $event): void
    {
        $destinatarios = $this->destinatarios($event->unidadPrincipalId, $event->residenteId);

        if ($destinatarios === []) {
            return;
        }

        $unidad = $event->unidadIdentificador ?? 'sin identificar';
        $mensaje = "El paquete de la unidad {$unidad} ({$event->descripcion}) fue retirado por {$event->retiradoPorNombre}.";

        foreach ($destinatarios as $usuarioId) {
            ($this->crearNotificacion)([
                'usuario_id' => $usuarioId,
                'proyecto_id' => $event->proyectoId,
                'tipo' => 'paqueteria',
                'titulo' => 'Paquete retirado',
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
