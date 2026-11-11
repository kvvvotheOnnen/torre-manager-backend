<?php

namespace Features\Asistente\Domain\Exceptions;

use RuntimeException;
use Throwable;

final class ModeloNoDisponibleException extends RuntimeException
{
    public static function becauseRequestFailed(Throwable $previous): self
    {
        return new self(
            'El modelo de IA no está disponible en este momento. Intenta nuevamente en unos segundos.',
            previous: $previous,
        );
    }

    public static function becauseResponseIsEmpty(): self
    {
        return new self('El modelo de IA no devolvió una respuesta válida.');
    }
}
