<?php

namespace Features\Marketplace\Domain\Exceptions;

use RuntimeException;

final class MotivoRechazoRequeridoException extends RuntimeException
{
    public static function becauseMissing(): self
    {
        return new self('Debes indicar el motivo del rechazo.');
    }
}
