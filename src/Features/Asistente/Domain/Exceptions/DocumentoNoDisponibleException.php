<?php

namespace Features\Asistente\Domain\Exceptions;

use RuntimeException;

final class DocumentoNoDisponibleException extends RuntimeException
{
    public static function becauseProjectHasNoDocument(): self
    {
        return new self('La administración aún no ha cargado un documento para este proyecto.');
    }
}
