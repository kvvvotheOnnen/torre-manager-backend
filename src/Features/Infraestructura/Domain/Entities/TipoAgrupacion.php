<?php

namespace Features\Infraestructura\Domain\Entities;

enum TipoAgrupacion: string
{
    case Edificio = 'edificio';
    case Torre = 'torre';
    case Bloque = 'bloque';
    case Manzana = 'manzana';
}
