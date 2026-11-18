<?php

namespace Features\Servicios\Domain\Exceptions;

use RuntimeException;

final class TrabajadorNoEncontradoException extends RuntimeException
{
    public static function becauseDoesNotExist(): self
    {
        return new self('El trabajador no existe en este proyecto.');
    }
}
