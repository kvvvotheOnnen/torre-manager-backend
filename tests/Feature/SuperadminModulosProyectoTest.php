<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperadminModulosProyectoTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_deshabilita_modulos_globales_para_un_proyecto(): void
    {
        $proyecto = Proyecto::factory()->create(['modulos' => ['infraestructura']]);
        $admin = User::factory()->administrador()->create();
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $response = $this->putJson("/api/superadmin/proyectos/{$proyecto->getKey()}", [
            'nombre' => 'Condominio Actualizado',
            'descripcion' => null,
            'modulos' => ['infraestructura'],
            'modulos_deshabilitados' => ['paqueteria', 'marketplace'],
            'instalaciones' => [],
            'admin_ids' => [$admin->getKey()],
        ]);

        $response->assertOk()
            ->assertJsonPath('modulos_deshabilitados', ['paqueteria', 'marketplace']);

        $this->assertSame(
            ['paqueteria', 'marketplace'],
            $proyecto->fresh()->modulos_deshabilitados,
        );
    }

    public function test_ver_proyecto_informa_los_modulos_globales_deshabilitados(): void
    {
        $proyecto = Proyecto::factory()->create([
            'modulos' => ['infraestructura'],
            'modulos_deshabilitados' => ['gastos_comunes'],
        ]);
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $this->getJson("/api/superadmin/proyectos/{$proyecto->getKey()}")
            ->assertOk()
            ->assertJsonPath('modulos_deshabilitados', ['gastos_comunes']);
    }

    public function test_rechaza_modulos_deshabilitados_desconocidos(): void
    {
        $proyecto = Proyecto::factory()->create(['modulos' => ['infraestructura']]);
        $admin = User::factory()->administrador()->create();
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $this->putJson("/api/superadmin/proyectos/{$proyecto->getKey()}", [
            'nombre' => 'Condominio Actualizado',
            'modulos' => ['infraestructura'],
            'modulos_deshabilitados' => ['modulo-inexistente'],
            'instalaciones' => [],
            'admin_ids' => [$admin->getKey()],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('modulos_deshabilitados.0');
    }

    public function test_el_dashboard_refleja_los_modulos_globales_deshabilitados(): void
    {
        $proyecto = Proyecto::factory()->create([
            'modulos' => ['infraestructura'],
            'modulos_deshabilitados' => ['paqueteria'],
        ]);
        $admin = User::factory()->administrador()->create();
        $admin->proyectos()->attach($proyecto, ['rol' => 'administrador', 'estado' => 'activo']);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/dashboard/modulos', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ])->assertOk();

        $modulos = collect($response->json('modulos'));

        $this->assertFalse($modulos->firstWhere('id', 'paqueteria')['habilitado']);
        $this->assertTrue($modulos->firstWhere('id', 'marketplace')['habilitado']);
    }
}
