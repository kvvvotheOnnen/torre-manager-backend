<?php

namespace Features\Asistente\Domain\Exceptions;

use RuntimeException;

final class DocumentoMuyGrandeException extends RuntimeException
{
    public static function becauseExceedsTokenBudget(int $tokens, int $limite): self
    {
        return new self(
            "El documento es demasiado grande para el asistente ({$tokens} tokens estimados, máximo {$limite})."
        );
    }

    public static function becauseHasNoText(): self
    {
        return new self('No se pudo extraer texto del PDF. Verifica que el documento no sea una imagen escaneada.');
    }
}
