<?php

namespace Features\SolicitudesResidente\Domain\Exceptions;

use RuntimeException;

final class SolicitudYaDecididaException extends RuntimeException
{
    public static function porqueYaFueDecidida(): self
    {
        return new self('La solicitud ya fue aprobada o rechazada.');
    }
}
