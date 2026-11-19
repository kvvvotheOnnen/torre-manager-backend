<?php

namespace Features\Paqueteria\Domain\Exceptions;

use RuntimeException;

final class ResidenteNoCorrespondeException extends RuntimeException
{
    public static function becauseMismatch(): self
    {
        return new self('El residente no corresponde a la unidad indicada.');
    }
}
