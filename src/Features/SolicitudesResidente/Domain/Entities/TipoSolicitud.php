<?php

namespace Features\SolicitudesResidente\Domain\Entities;

enum TipoSolicitud: string
{
    case Propietario = 'propietario';
    case Arrendatario = 'arrendatario';
    case Residente = 'residente';
}
