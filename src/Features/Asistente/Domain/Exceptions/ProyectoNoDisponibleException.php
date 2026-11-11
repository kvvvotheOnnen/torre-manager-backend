<?php

namespace Features\Asistente\Domain\Exceptions;

use RuntimeException;

final class ProyectoNoDisponibleException extends RuntimeException
{
    public static function becauseUserHasNoAccess(): self
    {
        return new self('No tienes acceso al proyecto solicitado.');
    }

    public static function becauseUserHasNoProject(): self
    {
        return new self('No tienes un proyecto activo asociado.');
    }
}
