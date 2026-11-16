<?php

namespace Features\Acceso\Domain\Exceptions;

use RuntimeException;

final class VinculoNoEncontradoException extends RuntimeException
{
    public static function becauseVinculoIsMissing(): self
    {
        return new self('El vínculo de unidad no existe.');
    }
}
