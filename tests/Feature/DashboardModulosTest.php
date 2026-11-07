<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardModulosTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_recibe_modulos_globales_y_configurables_con_su_estado(): void
    {
        $proyecto = Proyecto::factory()->create([
            'modulos' => ['infraestructura'],
        ]);
        $admin = User::factory()->administrador()->create([
            'proyecto_id' => $proyecto->getKey(),
        ]);
        $admin->proyectos()->attach($proyecto);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/dashboard/modulos', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ]);

        $response->assertOk();

        $modulos = collect($response->json('modulos'));

        $this->assertCount(10, $modulos);
        $this->assertNull($modulos->firstWhere('id', 'infraestructura'));

        foreach ([
            'paqueteria',
            'instalaciones_extra',
            'gastos_comunes',
            'control_acceso',
            'registro_conserjes',
            'cambio_unidad_residente',
            'solicitudes_residente',
            'registro_empleados',
            'personas_externas_oficios',
            'alertas_noticias',
        ] as $id) {
            $modulo = $modulos->firstWhere('id', $id);
            $this->assertNotNull($modulo, "Falta el módulo global {$id}.");
            $this->assertTrue($modulo['habilitado']);
            $this->assertSame('general', $modulo['tipo']);
        }
    }

    public function test_los_modulos_globales_no_dependen_del_proyecto_activo(): void
    {
        $proyectoA = Proyecto::factory()->create(['nombre' => 'Proyecto A']);
        $proyectoB = Proyecto::factory()->create(['nombre' => 'Proyecto B']);
        $admin = User::factory()->administrador()->create();
        $admin->proyectos()->attach([$proyectoA->getKey(), $proyectoB->getKey()]);
        Sanctum::actingAs($admin);

        foreach ([$proyectoA, $proyectoB] as $proyecto) {
            $response = $this->getJson('/api/dashboard/modulos', [
                'X-Proyecto-Id' => (string) $proyecto->getKey(),
            ]);

            $response->assertOk();
            $modulos = collect($response->json('modulos'));

            $this->assertCount(10, $modulos);
            $this->assertNull($modulos->firstWhere('id', 'infraestructura'));

            foreach ([
                'paqueteria',
                'instalaciones_extra',
                'gastos_comunes',
                'control_acceso',
            ] as $id) {
                $modulo = $modulos->firstWhere('id', $id);
                $this->assertNotNull($modulo, "Falta el módulo global {$id}.");
                $this->assertTrue($modulo['habilitado']);
                $this->assertSame('general', $modulo['tipo']);
            }
        }
    }

    public function test_superadmin_si_recibe_infraestructura_en_el_catalogo(): void
    {
        $proyecto = Proyecto::factory()->create([
            'modulos' => ['infraestructura'],
        ]);
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $response = $this->getJson('/api/dashboard/modulos', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ]);

        $response->assertOk();

        $modulos = collect($response->json('modulos'));

        $this->assertCount(11, $modulos);
        $infraestructura = $modulos->firstWhere('id', 'infraestructura');
        $this->assertTrue($infraestructura['habilitado']);
        $this->assertSame('proyecto', $infraestructura['tipo']);
    }

    public function test_no_permite_consultar_modulos_de_un_proyecto_sin_acceso(): void
    {
        $proyectoPropio = Proyecto::factory()->create();
        $proyectoAjeno = Proyecto::factory()->create();
        $admin = User::factory()->administrador()->create();
        $admin->proyectos()->attach($proyectoPropio);
        Sanctum::actingAs($admin);

        $this->getJson('/api/dashboard/modulos', [
            'X-Proyecto-Id' => (string) $proyectoAjeno->getKey(),
        ])->assertNotFound();
    }

    public function test_mis_proyectos_incluye_proyectos_por_pivote_y_por_campo_legado(): void
    {
        $proyectoPivote = Proyecto::factory()->create([
            'nombre' => 'Proyecto Pivote',
        ]);
        $proyectoLegado = Proyecto::factory()->create([
            'nombre' => 'Proyecto Legado',
        ]);
        $admin = User::factory()->administrador()->create([
            'proyecto_id' => $proyectoLegado->getKey(),
        ]);
        $admin->proyectos()->attach($proyectoPivote);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/mis-proyectos');

        $response->assertOk()
            ->assertJsonCount(2, 'proyectos')
            ->assertJsonPath('proyectos.0.nombre', 'Proyecto Legado')
            ->assertJsonPath('proyectos.1.nombre', 'Proyecto Pivote');
    }
}
