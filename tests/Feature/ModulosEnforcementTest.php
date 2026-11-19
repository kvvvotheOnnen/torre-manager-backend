<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\User;
use Features\Dashboard\Domain\Contracts\VerificadorModulo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModulosEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private function adminEn(Proyecto $proyecto): User
    {
        $admin = User::factory()->administrador()->create([
            'proyecto_id' => $proyecto->getKey(),
        ]);
        $admin->proyectos()->attach($proyecto);

        return $admin;
    }

    public function test_paqueteria_deshabilitada_bloquea_sus_endpoints(): void
    {
        $proyecto = Proyecto::factory()->create([
            'modulos' => ['infraestructura'],
            'modulos_deshabilitados' => ['paqueteria'],
        ]);
        Sanctum::actingAs($this->adminEn($proyecto));

        $this->getJson('/api/paqueteria', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ])->assertForbidden()
            ->assertJsonPath('message', 'El módulo no está habilitado para este proyecto.');

        $this->postJson('/api/paqueteria', [
            'proyecto_id' => $proyecto->getKey(),
            'descripcion' => 'Intento',
        ])->assertForbidden();
    }

    public function test_paqueteria_habilitada_permite_sus_endpoints(): void
    {
        $proyecto = Proyecto::factory()->create([
            'modulos' => ['infraestructura'],
            'modulos_deshabilitados' => ['marketplace'],
        ]);
        Sanctum::actingAs($this->adminEn($proyecto));

        $this->getJson('/api/paqueteria', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ])->assertOk()
            ->assertJsonPath('paquetes', []);
    }

    public function test_marketplace_y_servicios_respetan_su_modulo(): void
    {
        $proyecto = Proyecto::factory()->create([
            'modulos' => ['infraestructura'],
            'modulos_deshabilitados' => ['marketplace', 'servicios_recomendados'],
        ]);
        Sanctum::actingAs($this->adminEn($proyecto));

        $this->getJson('/api/marketplace/publicaciones', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ])->assertForbidden();

        $this->getJson('/api/servicios', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ])->assertForbidden();
    }

    public function test_asistente_administrativo_respeta_su_modulo(): void
    {
        $proyecto = Proyecto::factory()->create([
            'modulos' => ['infraestructura'],
            'modulos_deshabilitados' => ['asistente_ia_admin'],
        ]);
        Sanctum::actingAs($this->adminEn($proyecto));

        $this->post(
            '/api/admin/asistente/documento',
            ['archivo' => UploadedFile::fake()->create('reglamento.pdf', 10, 'application/pdf')],
            [
                'Accept' => 'application/json',
                'X-Proyecto-Id' => (string) $proyecto->getKey(),
            ],
        )->assertForbidden();
    }

    public function test_solicitudes_residentes_respetan_su_modulo(): void
    {
        $proyecto = Proyecto::factory()->create([
            'modulos' => ['infraestructura'],
            'modulos_deshabilitados' => ['solicitudes_residente'],
        ]);
        Sanctum::actingAs($this->adminEn($proyecto));

        $this->getJson('/api/admin/solicitudes-residentes', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ])->assertForbidden();
    }

    public function test_superadmin_esta_exento_del_estado_del_modulo(): void
    {
        $proyecto = Proyecto::factory()->create([
            'modulos' => ['infraestructura'],
            'modulos_deshabilitados' => ['paqueteria'],
        ]);
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $this->getJson('/api/paqueteria', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ])->assertOk();
    }

    public function test_un_modulo_desconocido_se_deniega(): void
    {
        $proyecto = Proyecto::factory()->create(['modulos' => ['infraestructura']]);
        $admin = $this->adminEn($proyecto);

        $habilitado = app(VerificadorModulo::class)->estaHabilitado(
            (string) $admin->getKey(),
            (string) $proyecto->getKey(),
            'modulo_inexistente',
        );

        $this->assertFalse($habilitado);
    }
}
