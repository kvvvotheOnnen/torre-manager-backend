<?php

namespace Features\Superadmin\Domain\Entities;

enum ModuloSistema: string
{
    case Infraestructura = 'infraestructura';
    case Paqueteria = 'paqueteria';
    case Quincho = 'quincho';
    case GastosComunes = 'gastos_comunes';
    case ControlAcceso = 'control_acceso';

    public function nombre(): string
    {
        return match ($this) {
            self::Infraestructura => 'Infraestructura',
            self::Paqueteria => 'Paquetería',
            self::Quincho => 'Quincho',
            self::GastosComunes => 'Gastos comunes',
            self::ControlAcceso => 'Control de acceso',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Infraestructura => 'Proyecto, agrupaciones, unidades principales y accesorias.',
            self::Paqueteria => 'Trazabilidad de encomiendas del recinto.',
            self::Quincho => 'Reserva y administración de áreas comunes.',
            self::GastosComunes => 'Pago y gestión de gastos comunes en línea.',
            self::ControlAcceso => 'Registro y control de accesos al recinto.',
        };
    }
}
