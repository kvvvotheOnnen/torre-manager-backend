<?php

namespace Features\Paqueteria\Domain\Exceptions;

use RuntimeException;

final class UnidadNoEncontradaException extends RuntimeException
{
    public static function becauseIsMissing(): self
    {
        return new self('Unidad no encontrada.');
    }
}
