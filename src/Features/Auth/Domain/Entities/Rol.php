<?php

namespace Features\Auth\Domain\Entities;

enum Rol: string
{
    case Administrador = 'administrador';
    case Residente = 'residente';
    case Conserje = 'conserje';
}
