<?php

namespace Features\Superadmin\Domain\Entities;

enum ModuloSistema: string
{
    case Infraestructura = 'infraestructura';
    case Paqueteria = 'paqueteria';
    case Quincho = 'quincho';
    case GastosComunes = 'gastos_comunes';
    case ControlAcceso = 'control_acceso';
}
