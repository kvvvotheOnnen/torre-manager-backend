<?php

namespace Features\Auth\Domain\Exceptions;

use RuntimeException;

final class InvalidCredentialsException extends RuntimeException
{
    public static function becauseCredentialsAreWrong(): self
    {
        return new self('Las credenciales proporcionadas son incorrectas.');
    }
}
