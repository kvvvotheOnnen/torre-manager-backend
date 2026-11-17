<?php

namespace Features\SolicitudesResidente\Domain\Entities;

enum EstadoSolicitud: string
{
    case Pendiente = 'pendiente';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';

    public function estaDecidida(): bool
    {
        return $this !== self::Pendiente;
    }
}
