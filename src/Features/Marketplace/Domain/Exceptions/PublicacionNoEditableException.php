<?php

namespace Features\Marketplace\Domain\Exceptions;

use RuntimeException;

final class PublicacionNoEditableException extends RuntimeException
{
    public static function becauseState(): self
    {
        return new self('La publicación ya no se puede modificar en su estado actual.');
    }
}
