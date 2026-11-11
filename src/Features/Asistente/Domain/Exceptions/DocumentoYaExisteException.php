<?php

namespace Features\Asistente\Domain\Exceptions;

use RuntimeException;

final class DocumentoYaExisteException extends RuntimeException
{
    public static function becauseProjectAlreadyHasDocument(): self
    {
        return new self('Este proyecto ya tiene un documento cargado. Elimínalo antes de subir otro.');
    }
}
