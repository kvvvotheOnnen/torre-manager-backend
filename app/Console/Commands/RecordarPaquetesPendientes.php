<?php

namespace App\Console\Commands;

use App\Models\Notificacion;
use App\Models\Paquete;
use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;
use Features\Notificaciones\Domain\UseCases\CrearNotificacionUseCase;
use Illuminate\Console\Command;

final class RecordarPaquetesPendientes extends Command
{
    protected $signature = 'paquetes:recordar';

    protected $description = 'Notifica los paquetes pendientes con más de 7 días en conserjería.';

    public function handle(
        VinculoUnidadRepositoryInterface $vinculos,
        CrearNotificacionUseCase $crearNotificacion,
    ): int {
        $paquetes = Paquete::query()
            ->with('unidadPrincipal')
            ->where('estado', 'pendiente')
            ->where('fecha_recepcion', '<=', now()->subDays(7))
            ->orderBy('fecha_recepcion')
            ->get();

        $recordados = Notificacion::query()
            ->where('tipo', 'paqueteria_recordatorio')
            ->where('created_at', '>=', now()->subDays(7))
            ->get()
            ->map(fn (Notificacion $notificacion) => (string) ($notificacion->datos['paquete_id'] ?? ''))
            ->filter()
            ->unique()
            ->all();

        $notificados = 0;

        foreach ($paquetes as $paquete) {
            $paqueteId = (string) $paquete->getKey();

            if (in_array($paqueteId, $recordados, true)) {
                continue;
            }

            $destinatarios = $this->destinatarios($vinculos, $paquete);

            if ($destinatarios === []) {
                continue;
            }

            $unidad = $paquete->unidadPrincipal?->identificador ?? 'sin identificar';
            $mensaje = "El paquete de la unidad {$unidad} ({$paquete->descripcion}) lleva más de 7 días sin retirar. Por favor retíralo en conserjería.";

            foreach ($destinatarios as $usuarioId) {
                ($crearNotificacion)([
                    'usuario_id' => $usuarioId,
                    'proyecto_id' => (string) $paquete->proyecto_id,
                    'tipo' => 'paqueteria_recordatorio',
                    'titulo' => 'Paquete sin retirar',
                    'mensaje' => $mensaje,
                    'datos' => ['paquete_id' => $paqueteId],
                ]);
            }

            $notificados++;
        }

        $this->info("Paquetes notificados: {$notificados}.");

        return self::SUCCESS;
    }

    /** @return string[] */
    private function destinatarios(VinculoUnidadRepositoryInterface $vinculos, Paquete $paquete): array
    {
        $usuarios = $paquete->unidad_principal_id !== null
            ? $vinculos->usuariosActualesDeUnidad((string) $paquete->unidad_principal_id)
            : [];

        if ($usuarios === [] && $paquete->residente_id !== null && $paquete->residente_id !== '') {
            $usuarios = [(string) $paquete->residente_id];
        }

        return array_values(array_unique($usuarios));
    }
}
