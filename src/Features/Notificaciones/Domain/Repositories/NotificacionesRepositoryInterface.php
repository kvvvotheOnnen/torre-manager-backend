<?php

namespace Features\Notificaciones\Domain\Repositories;

use Features\Notificaciones\Domain\Entities\Notificacion;

interface NotificacionesRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function crear(array $datos): Notificacion;

    /**
     * @return list<Notificacion>
     */
    public function listar(string $usuarioId, int $limite = 50): array;

    public function contarNoLeidas(string $usuarioId): int;

    public function marcarLeida(string $usuarioId, string $notificacionId): Notificacion;

    public function marcarTodasLeidas(string $usuarioId): int;
}
