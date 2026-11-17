<?php

namespace Features\SolicitudesResidente\Domain\Exceptions;

use RuntimeException;

final class SolicitudDuplicadaException extends RuntimeException
{
    public static function porqueYaExisteUnaPendiente(): self
    {
        return new self('Ya existe una solicitud pendiente para esta unidad o para alguna de las personas.');
    }
}
