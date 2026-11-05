<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperadminInstalacionesExtraTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_proyecto_con_instalaciones_extra_validas(): void
    {
        $admin = User::factory()->administrador()->create();
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $response = $this->postJson('/api/superadmin/proyectos', [
            'nombre' => 'Proyecto Instalaciones',
            'tipo_condominio' => 'torres',
            'modulos' => ['infraestructura'],
            'agrupaciones' => [
                [
                    'nombre' => 'Torre A',
                    'tipo' => 'torre',
                    'piso_desde' => 1,
                    'piso_hasta' => 1,
                    'unidades_por_piso' => 1,
                ],
            ],
            'instalaciones' => [
                [
                    'nombre' => 'Quincho',
                    'capacidad' => 500,
                    'descripcion' => 'Espacio para reuniones del condominio.',
                ],
            ],
            'admin_ids' => [$admin->getKey()],
            'estacionamientos' => 0,
            'bodegas' => 0,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('instalaciones_extra', [
            'proyecto_id' => $response->json('id'),
            'nombre' => 'Quincho',
            'capacidad' => 500,
            'descripcion' => 'Espacio para reuniones del condominio.',
        ]);
    }

    public function test_actualiza_instalaciones_extra_con_capacidad_y_descripcion(): void
    {
        $proyecto = Proyecto::factory()->create();
        $admin = User::factory()->administrador()->create();
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $response = $this->putJson("/api/superadmin/proyectos/{$proyecto->getKey()}", [
            'nombre' => 'Condominio Actualizado',
            'descripcion' => null,
            'modulos' => ['infraestructura'],
            'instalaciones' => [
                [
                    'nombre' => 'Quincho',
                    'capacidad' => 40,
                    'descripcion' => 'Espacio para reuniones del condominio.',
                ],
                [
                    'nombre' => 'Gimnasio',
                    'capacidad' => 500,
                    'descripcion' => null,
                ],
            ],
            'admin_ids' => [$admin->getKey()],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('instalaciones_extra', [
            'proyecto_id' => $proyecto->getKey(),
            'nombre' => 'Quincho',
            'capacidad' => 40,
            'descripcion' => 'Espacio para reuniones del condominio.',
        ]);
        $this->assertDatabaseHas('instalaciones_extra', [
            'proyecto_id' => $proyecto->getKey(),
            'nombre' => 'Gimnasio',
            'capacidad' => 500,
            'descripcion' => null,
        ]);
        $this->assertDatabaseCount('instalaciones_extra', 2);
    }

    public function test_rechaza_capacidad_fuera_de_rango(): void
    {
        $proyecto = Proyecto::factory()->create();
        $admin = User::factory()->administrador()->create();
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $response = $this->putJson("/api/superadmin/proyectos/{$proyecto->getKey()}", [
            'nombre' => 'Condominio Actualizado',
            'modulos' => ['infraestructura'],
            'instalaciones' => [
                ['nombre' => 'Piscina', 'capacidad' => 0],
            ],
            'admin_ids' => [$admin->getKey()],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('instalaciones.0.capacidad');

        $this->assertDatabaseCount('instalaciones_extra', 0);
    }

    public function test_rechaza_descripcion_mayor_al_maximo(): void
    {
        $proyecto = Proyecto::factory()->create();
        $admin = User::factory()->administrador()->create();
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $response = $this->putJson("/api/superadmin/proyectos/{$proyecto->getKey()}", [
            'nombre' => 'Condominio Actualizado',
            'modulos' => ['infraestructura'],
            'instalaciones' => [
                ['nombre' => 'Piscina', 'capacidad' => 10, 'descripcion' => str_repeat('a', 101)],
            ],
            'admin_ids' => [$admin->getKey()],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('instalaciones.0.descripcion');

        $this->assertDatabaseCount('instalaciones_extra', 0);
    }
}
