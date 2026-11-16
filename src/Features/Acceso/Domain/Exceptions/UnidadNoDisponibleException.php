<?php

namespace Features\Acceso\Domain\Exceptions;

use RuntimeException;

final class UnidadNoDisponibleException extends RuntimeException
{
    public static function becauseUnitIsMissing(): self
    {
        return new self('La unidad no existe o no pertenece al proyecto activo.');
    }
}
