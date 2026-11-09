<?php

namespace Features\Residente\Domain\Exceptions;

use RuntimeException;

final class UnidadNoAsignadaException extends RuntimeException
{
    public static function becauseResidenteHasNoUnit(): self
    {
        return new self('El residente no tiene una unidad asignada.');
    }
}
