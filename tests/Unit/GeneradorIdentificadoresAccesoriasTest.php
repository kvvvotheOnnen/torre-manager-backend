<?php

namespace Tests\Unit;

use Features\Infraestructura\Domain\Entities\TipoUnidadAccesoria;
use Features\Infraestructura\Domain\UseCases\GeneradorIdentificadoresAccesorias;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GeneradorIdentificadoresAccesoriasTest extends TestCase
{
    public function test_genera_estacionamientos_secuenciales_desde_uno(): void
    {
        $generador = new GeneradorIdentificadoresAccesorias;

        $this->assertSame(
            ['E-001', 'E-002', 'E-003'],
            $generador->generar(TipoUnidadAccesoria::Estacionamiento, 3),
        );
    }

    public function test_continua_desde_el_mayor_existente(): void
    {
        $generador = new GeneradorIdentificadoresAccesorias;

        $this->assertSame(
            ['E-008', 'E-009', 'E-010'],
            $generador->generar(
                TipoUnidadAccesoria::Estacionamiento,
                3,
                ['E-002', 'E-005', 'E-007', 'OTRO'],
            ),
        );
    }

    public function test_genera_bodegas_con_prefijo_b(): void
    {
        $generador = new GeneradorIdentificadoresAccesorias;

        $this->assertSame(
            ['B-001', 'B-002'],
            $generador->generar(TipoUnidadAccesoria::Bodega, 2),
        );
    }

    public function test_rechaza_cantidades_fuera_de_rango(): void
    {
        $generador = new GeneradorIdentificadoresAccesorias;

        $this->expectException(InvalidArgumentException::class);

        $generador->generar(TipoUnidadAccesoria::Estacionamiento, 1000);
    }
}
