<?php

namespace Features\Marketplace\Domain\Exceptions;

use RuntimeException;

final class PublicacionNoEncontradaException extends RuntimeException
{
    public static function becauseDoesNotExist(): self
    {
        return new self('Publicación no encontrada.');
    }
}
