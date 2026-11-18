<?php

namespace Features\Marketplace\Domain\Entities;

/**
 * Categorías visibles en el catálogo del marketplace.
 *
 * La lista es cerrada para que el cliente pueda dibujar los filtros sin
 * consultar al backend. Agregar una categoría es agregar un caso y su
 * etiqueta; no requiere migración porque se guarda como string.
 */
enum CategoriaProducto: string
{
    case Electrodomesticos = 'electrodomesticos';
    case Muebles = 'muebles';
    case Electronica = 'electronica';
    case Ropa = 'ropa';
    case Accesorios = 'accesorios';
    case Otros = 'otros';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Electrodomesticos => 'Electrodomésticos',
            self::Muebles => 'Muebles',
            self::Electronica => 'Electrónica',
            self::Ropa => 'Ropa',
            self::Accesorios => 'Accesorios',
            self::Otros => 'Otros',
        };
    }
}
