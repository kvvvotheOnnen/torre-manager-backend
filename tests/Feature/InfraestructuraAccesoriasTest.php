<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Proyecto;
use App\Models\UnidadAccesoria;
use App\Models\UnidadPrincipal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InfraestructuraAccesoriasTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_crea_un_lote_de_estacionamientos(): void
    {
        $proyecto = Proyecto::factory()->create();
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $response = $this->postJson('/api/infraestructura/unidades-accesorias/lote', [
            'proyecto_id' => $proyecto->getKey(),
            'tipo' => 'estacionamiento',
            'cantidad' => 3,
        ]);

        $response->assertCreated()
            ->assertJsonPath('unidades.0.identificador', 'E-001')
            ->assertJsonPath('unidades.1.identificador', 'E-002')
            ->assertJsonPath('unidades.2.identificador', 'E-003')
            ->assertJsonPath('unidades.0.asignacion', null)
            ->assertJsonPath('unidades.0.tipo', 'estacionamiento');

        $this->assertDatabaseCount('unidades_accesorias', 3);
    }

    public function test_asigna_una_accesoria_a_un_departamento(): void
    {
        $proyecto = Proyecto::factory()->create();
        $agrupacion = Agrupacion::factory()->for($proyecto)->create([
            'nombre' => 'Torre A',
            'tipo' => 'torre',
        ]);
        $unidad = UnidadPrincipal::factory()->for($agrupacion)->create([
            'identificador' => '101',
        ]);
        $accesoria = UnidadAccesoria::factory()->for($proyecto)->create([
            'identificador' => 'E-001',
            'tipo' => 'estacionamiento',
            'asignacion' => 'otro',
            'unidad_principal_id' => null,
        ]);
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $response = $this->patchJson(
            "/api/infraestructura/unidades-accesorias/{$accesoria->getKey()}/asignacion",
            [
                'asignacion' => 'departamento',
                'unidad_principal_id' => $unidad->getKey(),
            ],
        );

        $response->assertOk()
            ->assertJsonPath('asignacion', 'departamento')
            ->assertJsonPath('unidad_principal_id', (string) $unidad->getKey());

        $this->assertDatabaseHas('unidades_accesorias', [
            'id' => $accesoria->getKey(),
            'asignacion' => 'departamento',
            'unidad_principal_id' => $unidad->getKey(),
        ]);
    }

    public function test_asigna_visita_y_libera_la_unidad_principal(): void
    {
        $proyecto = Proyecto::factory()->create();
        $agrupacion = Agrupacion::factory()->for($proyecto)->create([
            'nombre' => 'Torre A',
            'tipo' => 'torre',
        ]);
        $unidad = UnidadPrincipal::factory()->for($agrupacion)->create();
        $accesoria = UnidadAccesoria::factory()->for($proyecto)->create([
            'asignacion' => 'departamento',
            'unidad_principal_id' => $unidad->getKey(),
        ]);
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $response = $this->patchJson(
            "/api/infraestructura/unidades-accesorias/{$accesoria->getKey()}/asignacion",
            ['asignacion' => 'visita'],
        );

        $response->assertOk()
            ->assertJsonPath('asignacion', 'visita')
            ->assertJsonPath('unidad_principal_id', null);
    }

    public function test_guarda_otro_como_asignacion_explicita(): void
    {
        $proyecto = Proyecto::factory()->create();
        $accesoria = UnidadAccesoria::factory()->for($proyecto)->create([
            'identificador' => 'E-001',
            'asignacion' => null,
        ]);
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $response = $this->patchJson(
            "/api/infraestructura/unidades-accesorias/{$accesoria->getKey()}/asignacion",
            ['asignacion' => 'otro'],
        );

        $response->assertOk()
            ->assertJsonPath('asignacion', 'otro')
            ->assertJsonPath('unidad_principal_id', null);

        $this->assertDatabaseHas('unidades_accesorias', [
            'id' => $accesoria->getKey(),
            'asignacion' => 'otro',
            'unidad_principal_id' => null,
        ]);
    }

    public function test_crea_proyecto_con_estacionamientos_y_bodegas_iniciales(): void
    {
        $admin = User::factory()->administrador()->create();
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $response = $this->postJson('/api/superadmin/proyectos', [
            'nombre' => 'Condominio Nuevo',
            'descripcion' => 'Proyecto de prueba',
            'tipo_condominio' => 'torres',
            'modulos' => ['infraestructura'],
            'agrupaciones' => [
                [
                    'nombre' => 'Torre A',
                    'tipo' => 'torre',
                    'piso_desde' => 1,
                    'piso_hasta' => 1,
                    'unidades_por_piso' => 2,
                ],
            ],
            'instalaciones' => [],
            'admin_ids' => [$admin->getKey()],
            'estacionamientos' => 2,
            'bodegas' => 1,
        ]);

        $response->assertCreated();

        $proyectoId = $response->json('id');

        $this->assertDatabaseHas('unidades_accesorias', [
            'proyecto_id' => $proyectoId,
            'tipo' => 'estacionamiento',
            'identificador' => 'E-001',
            'asignacion' => null,
        ]);
        $this->assertDatabaseHas('unidades_accesorias', [
            'proyecto_id' => $proyectoId,
            'tipo' => 'estacionamiento',
            'identificador' => 'E-002',
        ]);
        $this->assertDatabaseHas('unidades_accesorias', [
            'proyecto_id' => $proyectoId,
            'tipo' => 'bodega',
            'identificador' => 'B-001',
        ]);
    }
}
