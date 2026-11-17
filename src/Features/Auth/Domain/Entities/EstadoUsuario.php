<?php

namespace Features\Auth\Domain\Entities;

enum EstadoUsuario: string
{
    case Pendiente = 'pendiente';
    case Activo = 'activo';
    case Rechazado = 'rechazado';
}
