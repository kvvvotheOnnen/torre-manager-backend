<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperadminProyectoBorradorTest extends TestCase
{
    use RefreshDatabase;

    private function autenticarSuperadmin(): void
    {
        Sanctum::actingAs(User::factory()->superadmin()->create());
    }

    public function test_inicia_borrador_y_no_aparece_en_el_listado(): void
    {
        $this->autenticarSuperadmin();

        $response = $this->postJson('/api/superadmin/proyectos/borrador', [
            'nombre' => 'Proyecto en creación',
            'descripcion' => 'Borrador',
        ]);

        $response->assertCreated()
            ->assertJsonPath('nombre', 'Proyecto en creación')
            ->assertJsonPath('estado', 'borrador');

        $this->assertDatabaseHas('proyectos', [
            'nombre' => 'Proyecto en creación',
            'estado' => 'borrador',
        ]);

        $listado = $this->getJson('/api/superadmin/proyectos');
        $listado->assertOk()->assertJsonCount(0, 'proyectos');
    }

    public function test_guarda_todos_los_pasos_y_finaliza_el_proyecto(): void
    {
        $this->autenticarSuperadmin();

        $admin = User::factory()->administrador()->create();

        $id = $this->postJson('/api/superadmin/proyectos/borrador', [
            'nombre' => 'Condominio Paso a Paso',
        ])->assertCreated()->json('id');

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'descripcion' => 'Descripción final',
        ])->assertOk()->assertJsonPath('descripcion', 'Descripción final');

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'tipo_condominio' => 'torres',
        ])->assertOk()->assertJsonPath('tipo_condominio', 'torres');

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'agrupaciones' => [
                [
                    'nombre' => 'Torre A',
                    'tipo' => 'torre',
                    'piso_desde' => 1,
                    'piso_hasta' => 2,
                    'unidades_por_piso' => 2,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseCount('agrupaciones', 1);
        $this->assertDatabaseCount('unidades_principales', 4);

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'instalaciones' => [
                ['nombre' => 'Quincho', 'capacidad' => 30, 'descripcion' => 'Eventos'],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('instalaciones_extra', [
            'proyecto_id' => $id,
            'nombre' => 'Quincho',
            'capacidad' => 30,
        ]);

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'admin_ids' => [(string) $admin->getKey()],
        ])->assertOk();

        $response = $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'modulos' => ['infraestructura', 'paqueteria'],
            'finalizar' => true,
        ]);

        $response->assertOk()->assertJsonPath('estado', 'completo');

        $this->assertDatabaseHas('proyectos', [
            'id' => $id,
            'estado' => 'completo',
        ]);

        $this->getJson('/api/superadmin/proyectos')
            ->assertOk()
            ->assertJsonCount(1, 'proyectos');
    }

    public function test_no_permite_finalizar_sin_estructura_administradores_y_modulos(): void
    {
        $this->autenticarSuperadmin();

        $id = $this->postJson('/api/superadmin/proyectos/borrador', [
            'nombre' => 'Incompleto',
        ])->assertCreated()->json('id');

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'finalizar' => true,
        ])->assertUnprocessable();

        $this->assertDatabaseHas('proyectos', [
            'id' => $id,
            'estado' => 'borrador',
        ]);
    }

    public function test_cambiar_tipo_condominio_reinicia_la_estructura(): void
    {
        $this->autenticarSuperadmin();

        $id = $this->postJson('/api/superadmin/proyectos/borrador', [
            'nombre' => 'Cambio de tipo',
        ])->assertCreated()->json('id');

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'tipo_condominio' => 'torres',
            'agrupaciones' => [
                [
                    'nombre' => 'Torre A',
                    'tipo' => 'torre',
                    'piso_desde' => 1,
                    'piso_hasta' => 1,
                    'unidades_por_piso' => 2,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseCount('agrupaciones', 1);

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'tipo_condominio' => 'casas',
        ])->assertOk();

        $this->assertDatabaseCount('agrupaciones', 0);
        $this->assertDatabaseCount('unidades_principales', 0);
    }

    public function test_descarta_el_borrador(): void
    {
        $this->autenticarSuperadmin();

        $id = $this->postJson('/api/superadmin/proyectos/borrador', [
            'nombre' => 'Borrador a descartar',
        ])->assertCreated()->json('id');

        $this->deleteJson("/api/superadmin/proyectos/{$id}/borrador")->assertNoContent();

        $this->assertDatabaseMissing('proyectos', ['id' => $id]);
    }

    public function test_no_descarta_un_proyecto_completo(): void
    {
        $this->autenticarSuperadmin();

        $proyecto = Proyecto::factory()->create();

        $this->deleteJson("/api/superadmin/proyectos/{$proyecto->getKey()}/borrador")
            ->assertNotFound();

        $this->assertDatabaseHas('proyectos', ['id' => $proyecto->getKey()]);
    }

    public function test_no_guarda_pasos_sobre_un_proyecto_completo(): void
    {
        $this->autenticarSuperadmin();

        $proyecto = Proyecto::factory()->create();

        $this->patchJson("/api/superadmin/proyectos/{$proyecto->getKey()}/borrador", [
            'nombre' => 'No permitido',
        ])->assertUnprocessable();
    }

    public function test_guarda_edificio_con_pisos_excepcion(): void
    {
        $this->autenticarSuperadmin();

        $id = $this->postJson('/api/superadmin/proyectos/borrador', [
            'nombre' => 'Edificio con excepción',
        ])->assertCreated()->json('id');

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'tipo_condominio' => 'edificio',
        ])->assertOk();

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'agrupaciones' => [
                [
                    'nombre' => 'Edificio',
                    'tipo' => 'edificio',
                    'piso_desde' => 1,
                    'piso_hasta' => 3,
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
            ],
        ])->assertOk();

        $this->assertDatabaseCount('unidades_principales', 5);
        $this->assertDatabaseHas('unidades_principales', ['identificador' => 'Edificio-201']);
        $this->assertDatabaseMissing('unidades_principales', ['identificador' => 'Edificio-202']);
        $this->assertDatabaseCount('instalaciones_extra', 0);

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'instalaciones' => [
                ['nombre' => 'Quincho', 'capacidad' => 80, 'descripcion' => 'Sala de fiestas'],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('instalaciones_extra', [
            'proyecto_id' => $id,
            'nombre' => 'Quincho',
            'capacidad' => 80,
        ]);
    }

    public function test_piso_excepcion_sin_unidades_omite_el_piso_y_salta_su_numeracion(): void
    {
        $this->autenticarSuperadmin();

        $id = $this->postJson('/api/superadmin/proyectos/borrador', [
            'nombre' => 'Edificio con piso vacío',
        ])->assertCreated()->json('id');

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'tipo_condominio' => 'edificio',
            'agrupaciones' => [
                [
                    'nombre' => 'Edificio',
                    'tipo' => 'edificio',
                    'piso_desde' => 1,
                    'piso_hasta' => 3,
                    'unidades_por_piso' => 2,
                    'pisos_excepcion' => [
                        ['piso' => 2, 'sin_unidades' => true],
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseCount('unidades_principales', 4);
        $this->assertDatabaseHas('unidades_principales', ['identificador' => 'Edificio-101']);
        $this->assertDatabaseHas('unidades_principales', ['identificador' => 'Edificio-301']);
        $this->assertDatabaseMissing('unidades_principales', ['identificador' => 'Edificio-201']);
        $this->assertDatabaseMissing('unidades_principales', ['identificador' => 'Edificio-202']);
    }

    public function test_unidades_simples_de_piso_excepcion_respetan_numeracion(): void
    {
        $this->autenticarSuperadmin();

        $id = $this->postJson('/api/superadmin/proyectos/borrador', [
            'nombre' => 'Edificio con unidades manuales',
        ])->assertCreated()->json('id');

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'tipo_condominio' => 'edificio',
            'agrupaciones' => [
                [
                    'nombre' => 'Edificio',
                    'tipo' => 'edificio',
                    'piso_desde' => 1,
                    'piso_hasta' => 3,
                    'unidades_por_piso' => 2,
                    'pisos_excepcion' => [
                        [
                            'piso' => 2,
                            'unidades' => [
                                ['tipo' => 'simple'],
                                ['tipo' => 'simple'],
                                ['identificador' => 'Quincho', 'tipo' => 'instalacion'],
                            ],
                        ],
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseCount('unidades_principales', 6);
        $this->assertDatabaseHas('unidades_principales', ['identificador' => 'Edificio-201']);
        $this->assertDatabaseHas('unidades_principales', ['identificador' => 'Edificio-202']);
        $this->assertDatabaseMissing('unidades_principales', ['identificador' => 'Edificio-203']);
        $this->assertDatabaseCount('instalaciones_extra', 0);
    }

    public function test_rechaza_piso_excepcion_sin_unidades_si_no_esta_marcado(): void
    {
        $this->autenticarSuperadmin();

        $id = $this->postJson('/api/superadmin/proyectos/borrador', [
            'nombre' => 'Piso sin unidades inválido',
        ])->assertCreated()->json('id');

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'tipo_condominio' => 'edificio',
            'agrupaciones' => [
                [
                    'nombre' => 'Edificio',
                    'tipo' => 'edificio',
                    'piso_desde' => 1,
                    'piso_hasta' => 3,
                    'unidades_por_piso' => 2,
                    'pisos_excepcion' => [
                        ['piso' => 2],
                    ],
                ],
            ],
        ])->assertUnprocessable();
    }

    public function test_rechaza_piso_excepcion_fuera_del_rango(): void
    {
        $this->autenticarSuperadmin();

        $id = $this->postJson('/api/superadmin/proyectos/borrador', [
            'nombre' => 'Excepción inválida',
        ])->assertCreated()->json('id');

        $this->patchJson("/api/superadmin/proyectos/{$id}/borrador", [
            'tipo_condominio' => 'edificio',
            'agrupaciones' => [
                [
                    'nombre' => 'Edificio',
                    'tipo' => 'edificio',
                    'piso_desde' => 1,
                    'piso_hasta' => 3,
                    'unidades_por_piso' => 2,
                    'pisos_excepcion' => [
                        [
                            'piso' => 7,
                            'unidades' => [
                                ['tipo' => 'simple'],
                            ],
                        ],
                    ],
                ],
            ],
        ])->assertUnprocessable();
    }
}
