<?php

namespace Tests\Unit;

use Features\Infraestructura\Domain\UseCases\GeneradorIdentificacionesUnidades;
use Features\Superadmin\Domain\Entities\TipoCondominio;
use Features\Superadmin\Domain\Services\NormalizadorEstructuraProyecto;
use PHPUnit\Framework\TestCase;

class NormalizadorEstructuraProyectoTest extends TestCase
{
    private function normalizador(): NormalizadorEstructuraProyecto
    {
        return new NormalizadorEstructuraProyecto(
            new GeneradorIdentificacionesUnidades,
        );
    }

    public function test_edificio_usa_nomenclatura_unidad(): void
    {
        $agrupaciones = $this->normalizador()->normalizar(TipoCondominio::Edificio, [
            [
                'nombre' => 'Edificio',
                'tipo' => 'edificio',
                'piso_desde' => 1,
                'piso_hasta' => 1,
                'unidades_por_piso' => 2,
            ],
        ]);

        $this->assertSame(
            ['Unidad-101', 'Unidad-102'],
            $agrupaciones[0]['identificadores'],
        );
    }

    public function test_torre_conserva_el_nombre_de_la_agrupacion(): void
    {
        $agrupaciones = $this->normalizador()->normalizar(TipoCondominio::Torres, [
            [
                'nombre' => 'Torre A',
                'tipo' => 'torre',
                'piso_desde' => 1,
                'piso_hasta' => 1,
                'unidades_por_piso' => 2,
            ],
        ]);

        $this->assertSame(
            ['Torre A-101', 'Torre A-102'],
            $agrupaciones[0]['identificadores'],
        );
    }

    public function test_edificio_respeta_un_prefijo_personalizado(): void
    {
        $agrupaciones = $this->normalizador()->normalizar(TipoCondominio::Edificio, [
            [
                'nombre' => 'Edificio',
                'tipo' => 'edificio',
                'prefijo' => 'Ala Norte',
                'piso_desde' => 1,
                'piso_hasta' => 1,
                'unidades_por_piso' => 1,
            ],
        ]);

        $this->assertSame(['Ala Norte-101'], $agrupaciones[0]['identificadores']);
    }

    public function test_piso_con_excepcion_sin_unidades_queda_vacio(): void
    {
        $agrupaciones = $this->normalizador()->normalizar(TipoCondominio::Edificio, [
            [
                'nombre' => 'Edificio',
                'tipo' => 'edificio',
                'piso_desde' => 1,
                'piso_hasta' => 3,
                'unidades_por_piso' => 2,
                'pisos_excepcion' => [
                    ['piso' => 2, 'unidades' => []],
                ],
            ],
        ]);

        $this->assertSame(
            ['Unidad-101', 'Unidad-102', 'Unidad-301', 'Unidad-302'],
            $agrupaciones[0]['identificadores'],
        );
    }

    public function test_unidades_simples_de_excepcion_usan_la_base_del_edificio(): void
    {
        $agrupaciones = $this->normalizador()->normalizar(TipoCondominio::Edificio, [
            [
                'nombre' => 'Edificio',
                'tipo' => 'edificio',
                'piso_desde' => 1,
                'piso_hasta' => 2,
                'unidades_por_piso' => 2,
                'pisos_excepcion' => [
                    [
                        'piso' => 2,
                        'unidades' => [
                            ['tipo' => 'simple'],
                            ['identificador' => 'Quincho', 'tipo' => 'instalacion'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            ['Unidad-101', 'Unidad-102', 'Unidad-201'],
            $agrupaciones[0]['identificadores'],
        );
    }
}
