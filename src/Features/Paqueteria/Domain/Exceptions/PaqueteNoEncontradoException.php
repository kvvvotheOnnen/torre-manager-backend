<?php

namespace Features\Paqueteria\Domain\Exceptions;

use RuntimeException;

final class PaqueteNoEncontradoException extends RuntimeException
{
    public static function becausePackageIsMissing(): self
    {
        return new self('El paquete no existe.');
    }
}
