<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Proyecto;
use App\Models\UnidadPrincipal;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResidenteMiInformacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_residente_con_unidad_recibe_sus_datos(): void
    {
        $proyecto = Proyecto::factory()->create();
        $agrupacion = Agrupacion::factory()->for($proyecto)->create([
            'nombre' => 'Edificio',
            'tipo' => 'edificio',
        ]);
        $residente = User::factory()->create([
            'rol' => Rol::Residente->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);
        UnidadPrincipal::factory()->for($agrupacion)->create([
            'identificador' => 'Edificio-801',
            'residente_id' => $residente->getKey(),
        ]);

        Sanctum::actingAs($residente);

        $response = $this->getJson('/api/residente/mi-informacion');

        $response->assertOk()
            ->assertJsonPath('unidad', 'Edificio-801')
            ->assertJsonPath('agrupacion', 'Edificio');
    }

    public function test_residente_sin_unidad_recibe_404(): void
    {
        $residente = User::factory()->create([
            'rol' => Rol::Residente->value,
        ]);

        Sanctum::actingAs($residente);

        $response = $this->getJson('/api/residente/mi-informacion');

        $response->assertNotFound();
    }

    public function test_admin_no_puede_acceder(): void
    {
        $admin = User::factory()->administrador()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/residente/mi-informacion');

        $response->assertForbidden();
    }

    public function test_superadmin_no_puede_acceder(): void
    {
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $response = $this->getJson('/api/residente/mi-informacion');

        $response->assertForbidden();
    }
}
