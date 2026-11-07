<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InfraestructuraAutorizacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_no_puede_consultar_la_infraestructura(): void
    {
        $proyecto = Proyecto::factory()->create();
        $admin = User::factory()->administrador()->create([
            'proyecto_id' => $proyecto->getKey(),
        ]);
        $admin->proyectos()->attach($proyecto);
        Sanctum::actingAs($admin);

        $this->getJson('/api/infraestructura/arbol', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ])->assertForbidden();
    }

    public function test_administrador_no_puede_crear_agrupaciones(): void
    {
        $proyecto = Proyecto::factory()->create();
        $admin = User::factory()->administrador()->create([
            'proyecto_id' => $proyecto->getKey(),
        ]);
        $admin->proyectos()->attach($proyecto);
        Sanctum::actingAs($admin);

        $this->postJson('/api/infraestructura/agrupaciones', [
            'proyecto_id' => $proyecto->getKey(),
            'nombre' => 'Torre Bloqueada',
            'tipo' => 'torre',
        ])->assertForbidden();
    }

    public function test_superadmin_si_puede_consultar_la_infraestructura(): void
    {
        $proyecto = Proyecto::factory()->create();
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $this->getJson('/api/infraestructura/arbol', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ])->assertOk();
    }
}
