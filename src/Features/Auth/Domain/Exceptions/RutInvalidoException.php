<?php

namespace Features\Auth\Domain\Exceptions;

use InvalidArgumentException;

final class RutInvalidoException extends InvalidArgumentException
{
    public static function porFormato(): self
    {
        return new self('El RUT ingresado no tiene un formato válido.');
    }

    public static function porDigitoVerificador(): self
    {
        return new self('El dígito verificador del RUT no es válido.');
    }
}
