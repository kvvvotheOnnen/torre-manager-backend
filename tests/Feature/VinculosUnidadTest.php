<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Proyecto;
use App\Models\UnidadPrincipal;
use App\Models\UnidadUsuario;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VinculosUnidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_asigna_propietario_y_crea_membresia(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->adminDe($proyecto);
        $propietario = User::factory()->create(['rol' => Rol::Propietario->value]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/unidades/'.$unidad->getKey().'/vinculos', [
            'usuario_id' => $propietario->getKey(),
            'tipo_vinculo' => 'propietario',
            'fecha_inicio' => '2026-01-01',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])
            ->assertCreated()
            ->assertJsonPath('tipo_vinculo', 'propietario')
            ->assertJsonPath('usuario_id', (string) $propietario->getKey());

        $this->assertDatabaseHas('unidad_usuario', [
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $propietario->getKey(),
            'tipo_vinculo' => 'propietario',
            'es_actual' => true,
        ]);

        $this->assertDatabaseHas('proyecto_usuario', [
            'proyecto_id' => $proyecto->getKey(),
            'usuario_id' => $propietario->getKey(),
            'rol' => 'propietario',
        ]);
    }

    public function test_propietario_puede_tener_unidades_en_varios_proyectos_y_convive_con_arrendatario(): void
    {
        [$proyectoA, $unidadA] = $this->estructura('Proyecto A');
        [$proyectoB, $unidadB] = $this->estructura('Proyecto B');
        $superadmin = User::factory()->superadmin()->create();
        $propietario = User::factory()->create(['rol' => Rol::Propietario->value]);
        $arrendatario = User::factory()->create(['rol' => Rol::Residente->value]);

        Sanctum::actingAs($superadmin);

        foreach ([[$proyectoA, $unidadA], [$proyectoB, $unidadB]] as [$proyecto, $unidad]) {
            $this->postJson('/api/admin/unidades/'.$unidad->getKey().'/vinculos', [
                'usuario_id' => $propietario->getKey(),
                'tipo_vinculo' => 'propietario',
                'fecha_inicio' => '2026-01-01',
            ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])->assertCreated();
        }

        $this->postJson('/api/admin/unidades/'.$unidadA->getKey().'/vinculos', [
            'usuario_id' => $arrendatario->getKey(),
            'tipo_vinculo' => 'arrendatario',
            'fecha_inicio' => '2026-02-01',
        ], ['X-Proyecto-Id' => (string) $proyectoA->getKey()])->assertCreated();

        Sanctum::actingAs($propietario);

        $this->getJson('/api/mis-unidades', ['X-Proyecto-Id' => (string) $proyectoA->getKey()])
            ->assertOk()
            ->assertJsonCount(1, 'unidades');

        $this->getJson('/api/mis-unidades')
            ->assertOk()
            ->assertJsonCount(2, 'unidades');

        $this->assertDatabaseHas('unidad_usuario', [
            'unidad_principal_id' => $unidadA->getKey(),
            'usuario_id' => $propietario->getKey(),
            'tipo_vinculo' => 'propietario',
            'es_actual' => true,
        ]);
        $this->assertDatabaseHas('unidad_usuario', [
            'unidad_principal_id' => $unidadA->getKey(),
            'usuario_id' => $arrendatario->getKey(),
            'tipo_vinculo' => 'arrendatario',
            'es_actual' => true,
        ]);
        $this->assertDatabaseHas('unidades_principales', [
            'id' => $unidadA->getKey(),
            'residente_id' => $arrendatario->getKey(),
        ]);
    }

    public function test_cerrar_vinculo_conserva_historial_y_libera_ocupante_legacy(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->adminDe($proyecto);
        $residente = User::factory()->create(['rol' => Rol::Residente->value]);

        Sanctum::actingAs($admin);

        $creado = $this->postJson('/api/admin/unidades/'.$unidad->getKey().'/vinculos', [
            'usuario_id' => $residente->getKey(),
            'tipo_vinculo' => 'residente',
            'fecha_inicio' => '2026-01-01',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])->assertCreated()->json();

        $this->deleteJson('/api/admin/vinculos/'.$creado['id'], [
            'fecha_fin' => '2026-06-30',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])
            ->assertOk()
            ->assertJsonPath('es_actual', false)
            ->assertJsonPath('fecha_fin', '2026-06-30');

        $this->assertDatabaseHas('unidad_usuario', [
            'id' => $creado['id'],
            'tipo_vinculo' => 'residente',
            'es_actual' => false,
            'fecha_fin' => '2026-06-30 00:00:00',
        ]);
        $this->assertDatabaseHas('unidades_principales', [
            'id' => $unidad->getKey(),
            'residente_id' => null,
        ]);
    }

    public function test_admin_no_puede_asignar_en_proyecto_ajeno(): void
    {
        [$proyectoA] = $this->estructura('Proyecto A');
        [, $unidadB] = $this->estructura('Proyecto B');
        $admin = $this->adminDe($proyectoA);
        $usuario = User::factory()->create(['rol' => Rol::Residente->value]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/unidades/'.$unidadB->getKey().'/vinculos', [
            'usuario_id' => $usuario->getKey(),
            'tipo_vinculo' => 'residente',
            'fecha_inicio' => '2026-01-01',
        ], ['X-Proyecto-Id' => (string) $proyectoA->getKey()])
            ->assertNotFound();
    }

    public function test_reasignar_propietario_cierra_el_anterior(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->adminDe($proyecto);
        $primero = User::factory()->create(['rol' => Rol::Propietario->value]);
        $segundo = User::factory()->create(['rol' => Rol::Propietario->value]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/unidades/'.$unidad->getKey().'/vinculos', [
            'usuario_id' => $primero->getKey(),
            'tipo_vinculo' => 'propietario',
            'fecha_inicio' => '2026-01-01',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])->assertCreated();

        $this->postJson('/api/admin/unidades/'.$unidad->getKey().'/vinculos', [
            'usuario_id' => $segundo->getKey(),
            'tipo_vinculo' => 'propietario',
            'fecha_inicio' => '2026-03-01',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])->assertCreated();

        $this->assertDatabaseHas('unidad_usuario', [
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $primero->getKey(),
            'tipo_vinculo' => 'propietario',
            'es_actual' => false,
        ]);
        $this->assertSame(1, UnidadUsuario::query()
            ->where('unidad_principal_id', $unidad->getKey())
            ->where('tipo_vinculo', 'propietario')
            ->where('es_actual', true)
            ->count());
    }

    /** @return array{Proyecto, UnidadPrincipal} */
    private function estructura(string $nombre = 'Proyecto'): array
    {
        $proyecto = Proyecto::factory()->create(['nombre' => $nombre]);
        $agrupacion = Agrupacion::factory()->for($proyecto)->create(['nombre' => 'Edificio '.$nombre]);
        $unidad = UnidadPrincipal::factory()->for($agrupacion)->create([
            'identificador' => $nombre.'-101',
        ]);

        return [$proyecto, $unidad];
    }

    private function adminDe(Proyecto $proyecto): User
    {
        $admin = User::factory()->administrador()->create();
        $admin->proyectos()->attach($proyecto, ['rol' => 'administrador', 'estado' => 'activo']);

        return $admin;
    }
}
