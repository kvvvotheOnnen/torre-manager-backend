<?php

namespace Features\SolicitudesResidente\Domain\Exceptions;

use InvalidArgumentException;

final class SolicitudInvalidaException extends InvalidArgumentException
{
    public static function porque(string $mensaje): self
    {
        return new self($mensaje);
    }

    public static function porPersonasInvalidas(): self
    {
        return new self('La solicitud debe incluir entre 1 y 6 personas.');
    }

    public static function porRutDuplicado(): self
    {
        return new self('No se puede repetir el RUT entre las personas de la solicitud.');
    }

    public static function porEmailDuplicado(): self
    {
        return new self('No se puede repetir el correo entre las personas de la solicitud.');
    }

    public static function porRutAsociadoAOtroCorreo(): self
    {
        return new self('El RUT ya está asociado a otro correo registrado.');
    }

    public static function porEmailAsociadoAOtroRut(): self
    {
        return new self('El correo ya está asociado a otro RUT registrado.');
    }
}
