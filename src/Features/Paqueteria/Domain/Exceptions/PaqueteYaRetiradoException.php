<?php

namespace Features\Paqueteria\Domain\Exceptions;

use RuntimeException;

final class PaqueteYaRetiradoException extends RuntimeException
{
    public static function becausePackageIsAlreadyPickedUp(): self
    {
        return new self('No se puede modificar un paquete ya retirado.');
    }
}
