<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\InstalacionExtra;
use App\Models\Proyecto;
use App\Models\UnidadAccesoria;
use App\Models\UnidadPrincipal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperadminEliminarProyectoTest extends TestCase
{
    use RefreshDatabase;

    public function test_elimina_un_proyecto_con_su_estructura_y_asociaciones(): void
    {
        $proyecto = Proyecto::factory()->create();
        $agrupacion = Agrupacion::factory()->for($proyecto)->create();
        $unidad = UnidadPrincipal::factory()->for($agrupacion)->create();

        UnidadAccesoria::factory()->for($proyecto)->create([
            'unidad_principal_id' => $unidad->getKey(),
        ]);
        InstalacionExtra::factory()->for($proyecto)->create();

        $admin = User::factory()->administrador()->create();
        $proyecto->administradores()->attach($admin->getKey());

        Sanctum::actingAs(User::factory()->superadmin()->create());

        $this->deleteJson("/api/superadmin/proyectos/{$proyecto->getKey()}")
            ->assertNoContent();

        $this->assertDatabaseMissing('proyectos', ['id' => $proyecto->getKey()]);
        $this->assertDatabaseMissing('agrupaciones', ['id' => $agrupacion->getKey()]);
        $this->assertDatabaseMissing('unidades_principales', ['id' => $unidad->getKey()]);
        $this->assertDatabaseCount('unidades_accesorias', 0);
        $this->assertDatabaseCount('instalaciones_extra', 0);
        $this->assertDatabaseCount('administrador_proyecto', 0);
        $this->assertDatabaseHas('usuarios', ['id' => $admin->getKey()]);
    }

    public function test_devuelve_404_si_el_proyecto_no_existe(): void
    {
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $this->deleteJson('/api/superadmin/proyectos/99999')
            ->assertNotFound();
    }
}
