<?php

namespace Features\SolicitudesResidente\Domain\Exceptions;

use RuntimeException;

final class SolicitudNoEncontradaException extends RuntimeException
{
    public static function porqueNoExiste(): self
    {
        return new self('Solicitud de residente no encontrada.');
    }
}
