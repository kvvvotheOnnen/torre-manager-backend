<?php

namespace Features\Auth\Domain\Entities;

enum Rol: string
{
    case Superadmin = 'superadmin';
    case Administrador = 'administrador';
    case Residente = 'residente';
    case Conserje = 'conserje';
}
