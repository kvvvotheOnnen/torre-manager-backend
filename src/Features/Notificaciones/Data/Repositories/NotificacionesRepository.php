<?php

namespace Features\Notificaciones\Data\Repositories;

use App\Models\Notificacion as NotificacionModel;
use Features\Notificaciones\Data\Models\NotificacionDto;
use Features\Notificaciones\Domain\Entities\Notificacion;
use Features\Notificaciones\Domain\Exceptions\NotificacionNoEncontradaException;
use Features\Notificaciones\Domain\Repositories\NotificacionesRepositoryInterface;

final class NotificacionesRepository implements NotificacionesRepositoryInterface
{
    public function crear(array $datos): Notificacion
    {
        $modelo = NotificacionModel::query()->create([
            'usuario_id' => $datos['usuario_id'],
            'proyecto_id' => $datos['proyecto_id'] ?? null,
            'tipo' => $datos['tipo'],
            'titulo' => $datos['titulo'],
            'mensaje' => $datos['mensaje'],
            'datos' => $datos['datos'] ?? null,
            'leida' => false,
        ]);

        return NotificacionDto::fromModel($modelo)->toEntity();
    }

    public function listar(string $usuarioId, int $limite = 50): array
    {
        return NotificacionModel::query()
            ->where('usuario_id', $usuarioId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limite)
            ->get()
            ->map(fn (NotificacionModel $modelo): Notificacion => NotificacionDto::fromModel($modelo)->toEntity())
            ->all();
    }

    public function contarNoLeidas(string $usuarioId): int
    {
        return NotificacionModel::query()
            ->where('usuario_id', $usuarioId)
            ->where('leida', false)
            ->count();
    }

    public function marcarLeida(string $usuarioId, string $notificacionId): Notificacion
    {
        $modelo = NotificacionModel::query()
            ->where('usuario_id', $usuarioId)
            ->whereKey($notificacionId)
            ->first();

        if ($modelo === null) {
            throw NotificacionNoEncontradaException::becauseNotificationIsMissing();
        }

        if (! $modelo->leida) {
            $modelo->leida = true;
            $modelo->leida_at = now();
            $modelo->save();
        }

        return NotificacionDto::fromModel($modelo)->toEntity();
    }

    public function marcarTodasLeidas(string $usuarioId): int
    {
        return NotificacionModel::query()
            ->where('usuario_id', $usuarioId)
            ->where('leida', false)
            ->update([
                'leida' => true,
                'leida_at' => now(),
            ]);
    }
}
