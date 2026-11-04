<?php

namespace Tests\Unit;

use Features\Infraestructura\Domain\UseCases\GeneradorIdentificacionesUnidades;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GeneradorIdentificacionesUnidadesTest extends TestCase
{
    public function test_genera_unidades_excluyendo_pisos_con_excepcion(): void
    {
        $generador = new GeneradorIdentificacionesUnidades;

        $identificadores = $generador->generar('Edificio', 1, 3, 2, [2]);

        $this->assertSame(
            ['Edificio-101', 'Edificio-102', 'Edificio-301', 'Edificio-302'],
            $identificadores,
        );
    }

    public function test_excluye_todos_los_pisos_si_corresponde(): void
    {
        $generador = new GeneradorIdentificacionesUnidades;

        $identificadores = $generador->generar('Edificio', 1, 2, 3, [1, 2]);

        $this->assertSame([], $identificadores);
    }

    public function test_rechaza_rango_invalido(): void
    {
        $generador = new GeneradorIdentificacionesUnidades;

        $this->expectException(InvalidArgumentException::class);

        $generador->generar('Edificio', 3, 1, 2);
    }
}
