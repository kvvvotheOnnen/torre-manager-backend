<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClasificacionUnidadesTest extends TestCase
{
    use RefreshDatabase;

    private const ESTRUCTURA = [
        [
            'nombre' => 'Torre A',
            'tipo' => 'torre',
            'piso_desde' => 1,
            'piso_hasta' => 2,
            'unidades_por_piso' => 2,
        ],
    ];

    private function borradorConEstructura(): Proyecto
    {
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $id = $this->postJson('/api/superadmin/proyectos/borrador', [
            'nombre' => 'Condominio Clasificado',
        ])->assertCreated()->json('id');

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'tipo_condominio' => 'torres',
        ])->assertOk();

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'agrupaciones' => self::ESTRUCTURA,
        ])->assertOk();

        return Proyecto::query()->findOrFail($id);
    }

    public function test_crea_clasificaciones_y_asigna_unidades(): void
    {
        $proyecto = $this->borradorConEstructura();

        $response = $this->patchJson("/api/superadmin/proyectos/{$proyecto->getKey()}/borrador", [
            'agrupaciones' => self::ESTRUCTURA,
            'clasificaciones' => [
                [
                    'nombre' => 'Grande',
                    'unidades' => [
                        ['agrupacion' => 'Torre A', 'identificador' => 'Torre A-101'],
                        ['agrupacion' => 'Torre A', 'identificador' => 'Torre A-102'],
                    ],
                ],
                [
                    'nombre' => 'Muy grande',
                    'unidades' => [
                        ['agrupacion' => 'Torre A', 'identificador' => 'Torre A-201'],
                    ],
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('clasificaciones_unidad', [
            'proyecto_id' => $proyecto->getKey(),
            'nombre' => 'Grande',
        ]);

        $grande = $proyecto->clasificaciones()->where('nombre', 'Grande')->firstOrFail();
        $muyGrande = $proyecto->clasificaciones()->where('nombre', 'Muy grande')->firstOrFail();

        $this->assertDatabaseHas('unidades_principales', [
            'identificador' => 'Torre A-101',
            'clasificacion_id' => $grande->getKey(),
        ]);
        $this->assertDatabaseHas('unidades_principales', [
            'identificador' => 'Torre A-201',
            'clasificacion_id' => $muyGrande->getKey(),
        ]);
        $this->assertDatabaseHas('unidades_principales', [
            'identificador' => 'Torre A-202',
            'clasificacion_id' => null,
        ]);
    }

    public function test_rechaza_nombres_de_clasificacion_duplicados(): void
    {
        $proyecto = $this->borradorConEstructura();

        $this->patchJson("/api/superadmin/proyectos/{$proyecto->getKey()}/borrador", [
            'agrupaciones' => self::ESTRUCTURA,
            'clasificaciones' => [
                ['nombre' => 'Grande', 'unidades' => []],
                ['nombre' => 'grande', 'unidades' => []],
            ],
        ])->assertUnprocessable();
    }

    public function test_rechaza_una_unidad_en_dos_clasificaciones(): void
    {
        $proyecto = $this->borradorConEstructura();

        $this->patchJson("/api/superadmin/proyectos/{$proyecto->getKey()}/borrador", [
            'agrupaciones' => self::ESTRUCTURA,
            'clasificaciones' => [
                [
                    'nombre' => 'Grande',
                    'unidades' => [
                        ['agrupacion' => 'Torre A', 'identificador' => 'Torre A-101'],
                    ],
                ],
                [
                    'nombre' => 'Muy grande',
                    'unidades' => [
                        ['agrupacion' => 'Torre A', 'identificador' => 'Torre A-101'],
                    ],
                ],
            ],
        ])->assertUnprocessable();
    }

    public function test_rechaza_unidades_que_no_existen_en_la_estructura(): void
    {
        $proyecto = $this->borradorConEstructura();

        $this->patchJson("/api/superadmin/proyectos/{$proyecto->getKey()}/borrador", [
            'agrupaciones' => self::ESTRUCTURA,
            'clasificaciones' => [
                [
                    'nombre' => 'Grande',
                    'unidades' => [
                        ['agrupacion' => 'Torre A', 'identificador' => 'Torre A-999'],
                    ],
                ],
            ],
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('clasificaciones_unidad', [
            'proyecto_id' => $proyecto->getKey(),
            'nombre' => 'Grande',
        ]);
    }

    public function test_guardar_estructura_sin_clasificaciones_reinicia_a_estandar(): void
    {
        $proyecto = $this->borradorConEstructura();

        $this->patchJson("/api/superadmin/proyectos/{$proyecto->getKey()}/borrador", [
            'agrupaciones' => self::ESTRUCTURA,
            'clasificaciones' => [
                [
                    'nombre' => 'Grande',
                    'unidades' => [
                        ['agrupacion' => 'Torre A', 'identificador' => 'Torre A-101'],
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseCount('clasificaciones_unidad', 1);

        $this->patchJson("/api/superadmin/proyectos/{$proyecto->getKey()}/borrador", [
            'agrupaciones' => self::ESTRUCTURA,
        ])->assertOk();

        $this->assertDatabaseCount('clasificaciones_unidad', 0);
        $this->assertSame(
            0,
            $proyecto->fresh()->unidadesPrincipales()->whereNotNull('clasificacion_id')->count(),
        );
    }
}
