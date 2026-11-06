<?php

namespace Features\Dashboard\Domain\Exceptions;

use RuntimeException;

final class ProyectoNoDisponibleException extends RuntimeException
{
    public static function becauseProjectIsMissing(): self
    {
        return new self('No tienes un proyecto disponible para el dashboard.');
    }

    public static function becauseUserHasNoAccess(): self
    {
        return new self('No tienes acceso al proyecto solicitado.');
    }

    public static function becauseProjectDoesNotExist(): self
    {
        return new self('El proyecto solicitado no existe.');
    }
}
