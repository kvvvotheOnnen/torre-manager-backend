<?php

namespace Features\Dashboard\Domain\Entities;

/**
 * Alcance sobre el que opera una función del dashboard.
 *
 * El ámbito describe "sobre qué" actúa la función, no "quién" la usa:
 * los permisos se resuelven siempre por capacidad.
 */
enum AmbitoFuncion: string
{
    case Proyecto = 'proyecto';
    case Unidad = 'unidad';
    case Propio = 'propio';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Proyecto => 'Todo el proyecto',
            self::Unidad => 'Unidad',
            self::Propio => 'Propio',
        };
    }
}
