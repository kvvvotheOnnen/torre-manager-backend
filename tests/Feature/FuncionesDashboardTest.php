<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FuncionesDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['funciones.funciones' => [
            [
                'id' => 'bitacora',
                'titulo' => 'Bitácora de acceso',
                'descripcion' => 'Entradas y salidas',
                'icono' => 'badge',
                'capacidades' => ['control_acceso.bitacora'],
                'ambito' => 'proyecto',
                'ruta' => '/control-acceso',
            ],
            [
                'id' => 'comun',
                'titulo' => 'Avisos',
                'descripcion' => 'Para todos los roles',
                'icono' => 'campaign',
                'capacidades' => [],
                'ambito' => 'proyecto',
                'ruta' => '/avisos',
            ],
            [
                'id' => 'modulo-apagado',
                'titulo' => 'Infraestructura',
                'descripcion' => 'Módulo configurable deshabilitado',
                'icono' => 'domain',
                'capacidades' => [],
                'ambito' => 'proyecto',
                'ruta' => '/infraestructura',
                'requiere_modulo' => 'infraestructura',
            ],
        ]]);
    }

    public function test_devuelve_solo_las_funciones_que_el_rol_puede_ejecutar(): void
    {
        $proyecto = Proyecto::factory()->create(['modulos' => []]);
        $admin = $this->usuarioEn($proyecto, 'administrador');
        $residente = $this->usuarioEn($proyecto, 'residente');

        $this->assertSame(['bitacora', 'comun'], $this->idsPara($admin, $proyecto));
        $this->assertSame(['comun'], $this->idsPara($residente, $proyecto));
    }

    public function test_omite_funciones_de_modulos_configurables_deshabilitados(): void
    {
        $proyecto = Proyecto::factory()->create(['modulos' => []]);
        $admin = $this->usuarioEn($proyecto, 'administrador');

        $this->assertNotContains('modulo-apagado', $this->idsPara($admin, $proyecto));

        $proyecto->update(['modulos' => ['infraestructura']]);

        $this->assertContains('modulo-apagado', $this->idsPara($admin, $proyecto->refresh()));
    }

    public function test_no_expone_funciones_a_quien_no_pertenece_al_proyecto(): void
    {
        $proyecto = Proyecto::factory()->create(['modulos' => []]);
        $ajeno = User::factory()->create(['rol' => 'administrador']);

        Sanctum::actingAs($ajeno);

        $this->getJson('/api/dashboard/funciones', ['X-Proyecto-Id' => (string) $proyecto->getKey()])
            ->assertNotFound();
    }

    public function test_resuelve_las_acciones_de_cada_funcion(): void
    {
        config(['funciones.funciones' => [
            [
                'id' => 'servicios',
                'titulo' => 'Servicios recomendados',
                'descripcion' => 'Trabajadores del edificio',
                'icono' => 'handyman',
                'capacidades' => [],
                'ambito' => 'proyecto',
                'ruta' => '/servicios',
                'acciones' => ['gestionar' => 'paqueteria.gestionar'],
            ],
        ]]);

        $proyecto = Proyecto::factory()->create(['modulos' => []]);
        $admin = $this->usuarioEn($proyecto, 'administrador');
        $residente = $this->usuarioEn($proyecto, 'residente');

        $this->assertTrue($this->accionesPara($admin, $proyecto)['gestionar']);
        $this->assertFalse($this->accionesPara($residente, $proyecto)['gestionar']);
        $this->assertArrayHasKey('gestionar', $this->accionesPara($residente, $proyecto));
    }

    private function usuarioEn(Proyecto $proyecto, string $rol): User
    {
        $usuario = User::factory()->create(['rol' => $rol]);
        $usuario->proyectos()->attach($proyecto, ['rol' => $rol, 'estado' => 'activo']);

        return $usuario;
    }

    /** @return string[] */
    private function idsPara(User $usuario, Proyecto $proyecto): array
    {
        Sanctum::actingAs($usuario);

        $respuesta = $this->getJson('/api/dashboard/funciones', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ])->assertOk();

        return array_column($respuesta->json('funciones'), 'id');
    }

    /** @return array<string, bool> */
    private function accionesPara(User $usuario, Proyecto $proyecto): array
    {
        Sanctum::actingAs($usuario);

        $respuesta = $this->getJson('/api/dashboard/funciones', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ])->assertOk();

        $funcion = collect($respuesta->json('funciones'))->firstWhere('id', 'servicios');

        return $funcion['acciones'] ?? [];
    }
}
