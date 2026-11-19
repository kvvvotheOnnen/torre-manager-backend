<?php

namespace Features\Paqueteria\Domain\Exceptions;

use RuntimeException;

final class UnidadNoPerteneceAlProyectoException extends RuntimeException
{
    public static function becauseMismatch(): self
    {
        return new self('La unidad no pertenece al proyecto indicado.');
    }
}
