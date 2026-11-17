<?php

namespace Features\Auth\Domain\Repositories;

use Features\Auth\Domain\Entities\EstadoUsuario;
use Features\Auth\Domain\Entities\Rol;
use Features\Auth\Domain\Entities\Usuario;

interface UsuarioRepositoryInterface
{
    public function buscarPorEmail(string $email): ?Usuario;

    public function buscarPorRut(string $rut): ?Usuario;

    public function crearPendiente(
        string $proyectoId,
        string $nombre,
        string $email,
        string $rut,
        string $telefono,
        string $telefonoEmergencia,
        string $nombreEmergencia,
        Rol $rol,
    ): Usuario;

    public function actualizarDatos(
        string $usuarioId,
        string $nombre,
        string $rut,
        string $telefono,
        string $telefonoEmergencia,
        string $nombreEmergencia,
        ?Rol $rol = null,
        ?EstadoUsuario $estado = null,
    ): void;

    public function activar(string $usuarioId): void;

    public function marcarRechazadoSiPendiente(string $usuarioId): void;
}
