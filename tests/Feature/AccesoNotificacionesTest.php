<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Notificacion;
use App\Models\Proyecto;
use App\Models\UnidadPrincipal;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccesoNotificacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_vinculo_asignado_avisa_al_usuario_afectado(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->usuarioConRol($proyecto, Rol::Administrador);
        $propietario = User::factory()->create(['rol' => Rol::Propietario->value]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/unidades/'.$unidad->getKey().'/vinculos', [
            'usuario_id' => $propietario->getKey(),
            'tipo_vinculo' => 'propietario',
            'fecha_inicio' => '2026-01-01',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])->assertCreated();

        $notificacion = Notificacion::query()
            ->where('usuario_id', $propietario->getKey())
            ->where('titulo', 'Nuevo acceso asignado')
            ->first();

        $this->assertNotNull($notificacion);
        $this->assertStringContainsString('propietario', $notificacion->mensaje);
    }

    public function test_el_vinculo_cerrado_avisa_al_usuario_afectado(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->usuarioConRol($proyecto, Rol::Administrador);
        $propietario = User::factory()->create(['rol' => Rol::Propietario->value]);

        Sanctum::actingAs($admin);

        $respuesta = $this->postJson('/api/admin/unidades/'.$unidad->getKey().'/vinculos', [
            'usuario_id' => $propietario->getKey(),
            'tipo_vinculo' => 'propietario',
            'fecha_inicio' => '2026-01-01',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])->assertCreated();

        $vinculoId = $respuesta->json('id');

        $this->deleteJson(
            '/api/admin/vinculos/'.$vinculoId,
            [],
            ['X-Proyecto-Id' => (string) $proyecto->getKey()],
        )->assertOk();

        $notificacion = Notificacion::query()
            ->where('usuario_id', $propietario->getKey())
            ->where('titulo', 'Acceso cerrado')
            ->first();

        $this->assertNotNull($notificacion);
        $this->assertStringContainsString('propietario', $notificacion->mensaje);
    }

    private function estructura(): array
    {
        $proyecto = Proyecto::factory()->create();
        $agrupacion = Agrupacion::factory()->for($proyecto)->create();
        $unidad = UnidadPrincipal::factory()->for($agrupacion)->create();

        return [$proyecto, $unidad];
    }

    private function usuarioConRol(Proyecto $proyecto, Rol $rol): User
    {
        $usuario = User::factory()->create([
            'rol' => $rol->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);

        $usuario->proyectos()->attach($proyecto, [
            'rol' => $rol->value,
            'estado' => 'activo',
        ]);

        return $usuario;
    }
}
