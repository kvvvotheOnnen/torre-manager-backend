<?php

namespace Features\Correo\Domain\Exceptions;

use RuntimeException;
use Throwable;

final class EnvioCorreoException extends RuntimeException
{
    public static function porApiKeyNoConfigurada(): self
    {
        return new self('La API key de Resend no está configurada.');
    }

    public static function porFalloDelProveedor(Throwable $previous): self
    {
        return new self('No fue posible enviar el correo a través de Resend.', 0, $previous);
    }
}
