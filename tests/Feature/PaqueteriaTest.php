<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Paquete;
use App\Models\Proyecto;
use App\Models\UnidadPrincipal;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaqueteriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_conserje_registra_paquete(): void
    {
        $proyecto = Proyecto::factory()->create();
        $agrupacion = Agrupacion::factory()->for($proyecto)->create();
        $unidad = UnidadPrincipal::factory()->for($agrupacion)->create();
        $conserje = User::factory()->create([
            'rol' => Rol::Conserje->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);

        Sanctum::actingAs($conserje);

        $response = $this->postJson('/api/paqueteria', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'descripcion' => 'Paquete de prueba',
            'remitente' => 'Amazon',
        ]);

        $response->assertCreated()
            ->assertJsonPath('descripcion', 'Paquete de prueba')
            ->assertJsonPath('estado', 'pendiente');

        $this->assertDatabaseCount('paquetes', 1);
    }

    public function test_conserje_registra_paquete_con_foto(): void
    {
        Storage::fake('public');
        $proyecto = Proyecto::factory()->create();
        $agrupacion = Agrupacion::factory()->for($proyecto)->create();
        $unidad = UnidadPrincipal::factory()->for($agrupacion)->create();
        $conserje = User::factory()->create([
            'rol' => Rol::Conserje->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);

        Sanctum::actingAs($conserje);

        $response = $this->post('/api/paqueteria', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'descripcion' => 'Paquete con foto',
            'remitente' => 'Amazon',
            'foto' => UploadedFile::fake()->create('paquete.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertCreated();
        $this->assertNotNull($response->json('foto_url'));
    }

    public function test_residente_ve_solo_sus_paquetes(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = User::factory()->create([
            'rol' => Rol::Residente->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);
        $otroResidente = User::factory()->create([
            'rol' => Rol::Residente->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);

        Paquete::factory()->create([
            'proyecto_id' => $proyecto->getKey(),
            'residente_id' => $residente->getKey(),
            'descripcion' => 'Mi paquete',
        ]);
        Paquete::factory()->create([
            'proyecto_id' => $proyecto->getKey(),
            'residente_id' => $otroResidente->getKey(),
            'descripcion' => 'Paquete de otro',
        ]);

        Sanctum::actingAs($residente);

        $response = $this->getJson('/api/paqueteria');

        $response->assertOk()
            ->assertJsonCount(1, 'paquetes')
            ->assertJsonPath('paquetes.0.descripcion', 'Mi paquete');
    }

    public function test_residente_no_puede_registrar(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = User::factory()->create([
            'rol' => Rol::Residente->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);

        Sanctum::actingAs($residente);

        $response = $this->postJson('/api/paqueteria', [
            'proyecto_id' => $proyecto->getKey(),
            'descripcion' => 'Intento',
        ]);

        $response->assertForbidden();
    }

    public function test_marcar_paquete_como_retirado(): void
    {
        $proyecto = Proyecto::factory()->create();
        $conserje = User::factory()->create([
            'rol' => Rol::Conserje->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);
        $paquete = Paquete::factory()->create([
            'proyecto_id' => $proyecto->getKey(),
            'estado' => 'pendiente',
        ]);

        Sanctum::actingAs($conserje);

        $response = $this->patchJson("/api/paqueteria/{$paquete->getKey()}/retirar", [
            'retirado_por_nombre' => 'Nombre Apellido',
        ]);

        $response->assertOk()
            ->assertJsonPath('estado', 'retirado')
            ->assertJsonPath('retirado_por_nombre', 'Nombre Apellido');

        $this->assertDatabaseHas('paquetes', [
            'id' => $paquete->getKey(),
            'estado' => 'retirado',
            'retirado_por_nombre' => 'Nombre Apellido',
        ]);
    }

    public function test_retirar_paquete_requiere_nombre_de_quien_retira(): void
    {
        $proyecto = Proyecto::factory()->create();
        $conserje = User::factory()->create([
            'rol' => Rol::Conserje->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);
        $paquete = Paquete::factory()->create([
            'proyecto_id' => $proyecto->getKey(),
            'estado' => 'pendiente',
        ]);

        Sanctum::actingAs($conserje);

        $this->patchJson("/api/paqueteria/{$paquete->getKey()}/retirar")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('retirado_por_nombre');

        $this->assertDatabaseHas('paquetes', [
            'id' => $paquete->getKey(),
            'estado' => 'pendiente',
        ]);
    }

    public function test_residente_ve_nombre_de_quien_retiro_su_paquete(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = User::factory()->create([
            'rol' => Rol::Residente->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);
        Paquete::factory()->retirado()->create([
            'proyecto_id' => $proyecto->getKey(),
            'residente_id' => $residente->getKey(),
            'retirado_por_nombre' => 'Nombre Apellido',
        ]);

        Sanctum::actingAs($residente);

        $this->getJson('/api/paqueteria')
            ->assertOk()
            ->assertJsonPath('paquetes.0.retirado_por_nombre', 'Nombre Apellido');
    }

    public function test_marcar_paquete_inexistente_retorna_404(): void
    {
        $conserje = User::factory()->create([
            'rol' => Rol::Conserje->value,
        ]);

        Sanctum::actingAs($conserje);

        $response = $this->patchJson('/api/paqueteria/9999/retirar', [
            'retirado_por_nombre' => 'Nombre Apellido',
        ]);

        $response->assertNotFound();
    }

    public function test_admin_ve_paquetes_de_su_proyecto(): void
    {
        $proyecto = Proyecto::factory()->create();
        $admin = User::factory()->administrador()->create([
            'proyecto_id' => $proyecto->getKey(),
        ]);
        $admin->proyectos()->sync([$proyecto->getKey()]);

        Paquete::factory()->count(3)->create([
            'proyecto_id' => $proyecto->getKey(),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/paqueteria');

        $response->assertOk()
            ->assertJsonCount(3, 'paquetes');
    }

    public function test_usuario_sin_proyectos_no_ve_paquetes_de_otros(): void
    {
        $proyecto = Proyecto::factory()->create();
        Paquete::factory()->create(['proyecto_id' => $proyecto->getKey()]);
        $admin = User::factory()->administrador()->create(['proyecto_id' => null]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/paqueteria')
            ->assertOk()
            ->assertJsonCount(0, 'paquetes');
    }

    public function test_admin_no_puede_listar_proyecto_no_asignado(): void
    {
        $proyectoAutorizado = Proyecto::factory()->create();
        $proyectoAjeno = Proyecto::factory()->create();
        $admin = User::factory()->administrador()->create([
            'proyecto_id' => $proyectoAutorizado->getKey(),
        ]);
        $admin->proyectos()->sync([$proyectoAutorizado->getKey()]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/paqueteria?proyecto_id='.$proyectoAjeno->getKey())
            ->assertNotFound();
    }

    public function test_registro_rechaza_proyecto_distinto_al_de_la_unidad(): void
    {
        $proyectoSolicitado = Proyecto::factory()->create();
        $proyectoUnidad = Proyecto::factory()->create();
        $agrupacion = Agrupacion::factory()->for($proyectoUnidad)->create();
        $unidad = UnidadPrincipal::factory()->for($agrupacion)->create();
        $conserje = User::factory()->create([
            'rol' => Rol::Conserje->value,
            'proyecto_id' => $proyectoSolicitado->getKey(),
        ]);
        $conserje->proyectos()->sync([
            $proyectoSolicitado->getKey(),
            $proyectoUnidad->getKey(),
        ]);

        Sanctum::actingAs($conserje);

        $this->postJson('/api/paqueteria', [
            'proyecto_id' => $proyectoSolicitado->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'descripcion' => 'Paquete de prueba',
            'remitente' => 'Remitente de prueba',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('paquetes', 0);
    }

    public function test_registro_no_permite_asignar_un_residente_ajeno_a_la_unidad(): void
    {
        $proyecto = Proyecto::factory()->create();
        $agrupacion = Agrupacion::factory()->for($proyecto)->create();
        $residenteUnidad = User::factory()->create(['rol' => Rol::Residente->value]);
        $otroResidente = User::factory()->create(['rol' => Rol::Residente->value]);
        $unidad = UnidadPrincipal::factory()->for($agrupacion)->create([
            'residente_id' => $residenteUnidad->getKey(),
        ]);
        $conserje = User::factory()->create([
            'rol' => Rol::Conserje->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);

        Sanctum::actingAs($conserje);

        $this->postJson('/api/paqueteria', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'residente_id' => $otroResidente->getKey(),
            'descripcion' => 'Paquete de prueba',
            'remitente' => 'Remitente de prueba',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('paquetes', 0);
    }

    public function test_edicion_no_permite_mover_paquete_a_unidad_de_otro_proyecto(): void
    {
        $proyectoPaquete = Proyecto::factory()->create();
        $proyectoUnidadAjena = Proyecto::factory()->create();
        $unidadPaquete = UnidadPrincipal::factory()
            ->for(Agrupacion::factory()->for($proyectoPaquete))
            ->create();
        $unidadAjena = UnidadPrincipal::factory()
            ->for(Agrupacion::factory()->for($proyectoUnidadAjena))
            ->create();
        $conserje = User::factory()->create([
            'rol' => Rol::Conserje->value,
            'proyecto_id' => $proyectoPaquete->getKey(),
        ]);
        $paquete = Paquete::factory()->create([
            'proyecto_id' => $proyectoPaquete->getKey(),
            'unidad_principal_id' => $unidadPaquete->getKey(),
        ]);

        Sanctum::actingAs($conserje);

        $this->putJson('/api/paqueteria/'.$paquete->getKey(), [
            'descripcion' => 'Descripción manipulada',
            'remitente' => 'Remitente de prueba',
            'unidad_principal_id' => $unidadAjena->getKey(),
        ])->assertUnprocessable();

        $this->assertDatabaseHas('paquetes', [
            'id' => $paquete->getKey(),
            'unidad_principal_id' => $unidadPaquete->getKey(),
            'descripcion' => $paquete->descripcion,
        ]);
    }

    public function test_unidades_incluyen_proyecto_y_respetan_el_ambito_del_usuario(): void
    {
        $proyectoAutorizado = Proyecto::factory()->create();
        $proyectoAjeno = Proyecto::factory()->create();
        $unidadAutorizada = UnidadPrincipal::factory()
            ->for(Agrupacion::factory()->for($proyectoAutorizado))
            ->create();
        UnidadPrincipal::factory()
            ->for(Agrupacion::factory()->for($proyectoAjeno))
            ->create();
        $conserje = User::factory()->create([
            'rol' => Rol::Conserje->value,
            'proyecto_id' => $proyectoAutorizado->getKey(),
        ]);

        Sanctum::actingAs($conserje);

        $this->getJson('/api/paqueteria/unidades')
            ->assertOk()
            ->assertJsonCount(1, 'unidades')
            ->assertJsonPath('unidades.0.id', $unidadAutorizada->getKey())
            ->assertJsonPath('unidades.0.proyecto_id', $proyectoAutorizado->getKey());
    }

    public function test_conserje_no_puede_retirar_paquete_de_otro_proyecto(): void
    {
        $proyectoAutorizado = Proyecto::factory()->create();
        $proyectoAjeno = Proyecto::factory()->create();
        $conserje = User::factory()->create([
            'rol' => Rol::Conserje->value,
            'proyecto_id' => $proyectoAutorizado->getKey(),
        ]);
        $paquete = Paquete::factory()->create([
            'proyecto_id' => $proyectoAjeno->getKey(),
        ]);

        Sanctum::actingAs($conserje);

        $this->patchJson('/api/paqueteria/'.$paquete->getKey().'/retirar', [
            'retirado_por_nombre' => 'Persona de prueba',
        ])->assertNotFound();

        $this->assertDatabaseHas('paquetes', [
            'id' => $paquete->getKey(),
            'estado' => 'pendiente',
        ]);
    }

    public function test_conserje_no_puede_actualizar_paquete_de_otro_proyecto(): void
    {
        $proyectoAutorizado = Proyecto::factory()->create();
        $proyectoAjeno = Proyecto::factory()->create();
        $agrupacion = Agrupacion::factory()->for($proyectoAjeno)->create();
        $unidad = UnidadPrincipal::factory()->for($agrupacion)->create();
        $conserje = User::factory()->create([
            'rol' => Rol::Conserje->value,
            'proyecto_id' => $proyectoAutorizado->getKey(),
        ]);
        $paquete = Paquete::factory()->create([
            'proyecto_id' => $proyectoAjeno->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
        ]);

        Sanctum::actingAs($conserje);

        $this->putJson('/api/paqueteria/'.$paquete->getKey(), [
            'descripcion' => 'Descripción actualizada',
            'remitente' => 'Remitente de prueba',
            'unidad_principal_id' => $unidad->getKey(),
        ])->assertNotFound();

        $this->assertDatabaseHas('paquetes', [
            'id' => $paquete->getKey(),
            'descripcion' => $paquete->descripcion,
        ]);
    }
}
