<?php

namespace Features\Auth\Domain\Exceptions;

use Features\Auth\Domain\Entities\EstadoUsuario;
use RuntimeException;

final class UsuarioNoAprobadoException extends RuntimeException
{
    public static function porEstado(EstadoUsuario $estado): self
    {
        return new self(match ($estado) {
            EstadoUsuario::Pendiente => 'Tu solicitud está pendiente de aprobación.',
            EstadoUsuario::Rechazado => 'Tu solicitud de registro fue rechazada.',
            EstadoUsuario::Activo => 'La cuenta no está habilitada.',
        });
    }
}
