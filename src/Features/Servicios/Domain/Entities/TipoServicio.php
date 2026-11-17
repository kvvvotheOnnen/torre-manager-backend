<?php

namespace Features\Servicios\Domain\Entities;

/**
 * Tipos de servicio ofrecidos por un trabajador recomendado.
 *
 * La lista es cerrada para poder filtrar por tipo en el cliente. Si se
 * necesita un tipo nuevo, se agrega un caso aquí y su etiqueta; nada más.
 */
enum TipoServicio: string
{
    case Electricista = 'electricista';
    case Gasfiter = 'gasfiter';
    case Maestro = 'maestro';
    case Cerrajero = 'cerrajero';
    case TecnicoInformatico = 'tecnico_informatico';
    case Otro = 'otro';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Electricista => 'Electricista',
            self::Gasfiter => 'Gasfiter',
            self::Maestro => 'Maestro',
            self::Cerrajero => 'Cerrajero',
            self::TecnicoInformatico => 'Técnico informático',
            self::Otro => 'Otro',
        };
    }
}
