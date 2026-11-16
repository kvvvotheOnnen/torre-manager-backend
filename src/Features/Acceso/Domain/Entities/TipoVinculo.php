<?php

namespace Features\Acceso\Domain\Entities;

enum TipoVinculo: string
{
    case Propietario = 'propietario';
    case Arrendatario = 'arrendatario';
    case Residente = 'residente';
    case Familiar = 'familiar';

    public function esPosesion(): bool
    {
        return $this === self::Propietario;
    }

    public function esOcupacion(): bool
    {
        return $this === self::Arrendatario
            || $this === self::Residente
            || $this === self::Familiar;
    }
}
