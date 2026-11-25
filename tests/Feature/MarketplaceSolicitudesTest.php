<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\PublicacionMarketplace;
use App\Models\User;
use Features\Marketplace\Domain\Entities\CategoriaProducto;
use Features\Marketplace\Domain\Entities\CondicionProducto;
use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MarketplaceSolicitudesTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_publicacion_del_residente_nace_pendiente_y_el_conserje_la_ve_en_solicitudes(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $conserje = $this->usuarioConRol('conserje', $proyecto);
        Sanctum::actingAs($residente);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->postJson('/api/marketplace/publicaciones', [
            'titulo' => 'Bicicleta fija',
            'descripcion' => 'Bicicleta fija con resistencia magnética, poco uso.',
            'precio' => 60000,
            'categoria' => 'otros',
            'condicion' => 'semi_nuevo',
        ], $headers)->assertCreated()
            ->assertJsonPath('publicacion.estado', 'pendiente');

        Sanctum::actingAs($conserje);

        $this->getJson('/api/marketplace/solicitudes', $headers)
            ->assertOk()
            ->assertJsonPath('pendientes', 1)
            ->assertJsonCount(1, 'solicitudes')
            ->assertJsonPath('solicitudes.0.titulo', 'Bicicleta fija')
            ->assertJsonPath('solicitudes.0.estado', 'pendiente');

        Sanctum::actingAs($residente);

        $this->getJson('/api/marketplace/mis-publicaciones', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.estado', 'pendiente');
    }

    public function test_el_administrador_publica_directo_sin_pasar_por_solicitudes(): void
    {
        $proyecto = Proyecto::factory()->create();
        $admin = $this->usuarioConRol('administrador', $proyecto);
        Sanctum::actingAs($admin);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->postJson('/api/marketplace/publicaciones', [
            'titulo' => 'Aviso de administración',
            'descripcion' => 'Artículo publicado directamente por la administración.',
            'precio' => 0,
            'categoria' => 'otros',
            'condicion' => 'nuevo',
        ], $headers)->assertCreated()
            ->assertJsonPath('publicacion.estado', 'disponible');

        $this->getJson('/api/marketplace/solicitudes', $headers)
            ->assertOk()
            ->assertJsonPath('pendientes', 0)
            ->assertJsonCount(0, 'solicitudes');
    }

    public function test_el_conserje_aprueba_y_la_publicacion_queda_disponible_para_todos(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $conserje = $this->usuarioConRol('conserje', $proyecto);
        $otro = $this->usuarioConRol('residente', $proyecto);
        $publicacion = $this->crearPendiente($proyecto, $residente);
        Sanctum::actingAs($conserje);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->postJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/aprobar', [], $headers)
            ->assertOk()
            ->assertJsonPath('publicacion.estado', 'disponible');

        $this->getJson('/api/marketplace/solicitudes', $headers)
            ->assertOk()
            ->assertJsonPath('pendientes', 0)
            ->assertJsonCount(0, 'solicitudes');

        Sanctum::actingAs($otro);

        $this->getJson('/api/marketplace/publicaciones', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.titulo', 'Solicitud de prueba');
    }

    public function test_el_conserje_rechaza_con_motivo_y_el_dueno_lo_ve_en_sus_publicaciones(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $conserje = $this->usuarioConRol('conserje', $proyecto);
        $publicacion = $this->crearPendiente($proyecto, $residente);
        Sanctum::actingAs($conserje);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->postJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/rechazar', [
            'motivo' => 'Fotografía ilegible del artículo.',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('publicacion.estado', 'rechazada')
            ->assertJsonPath('publicacion.motivo_rechazo', 'Fotografía ilegible del artículo.');

        $this->getJson('/api/marketplace/publicaciones', $headers)
            ->assertOk()
            ->assertJsonCount(0, 'publicaciones');

        Sanctum::actingAs($residente);

        $this->getJson('/api/marketplace/mis-publicaciones', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.estado', 'rechazada')
            ->assertJsonPath('publicaciones.0.motivo_rechazo', 'Fotografía ilegible del artículo.');
    }

    public function test_el_rechazo_exige_un_motivo(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $conserje = $this->usuarioConRol('conserje', $proyecto);
        $publicacion = $this->crearPendiente($proyecto, $residente);
        Sanctum::actingAs($conserje);

        $this->postJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/rechazar', [], [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ])->assertStatus(422)->assertJsonValidationErrors('motivo');
    }

    public function test_el_residente_no_puede_aprobar_ni_rechazar_solicitudes(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $publicacion = $this->crearPendiente($proyecto, $residente);
        Sanctum::actingAs($residente);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->getJson('/api/marketplace/solicitudes', $headers)->assertForbidden();

        $this->postJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/aprobar', [], $headers)
            ->assertForbidden();

        $this->postJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/rechazar', [
            'motivo' => 'Motivo que no debería aplicarse.',
        ], $headers)->assertForbidden();
    }

    public function test_no_se_puede_aprobar_una_publicacion_que_ya_fue_revisada(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $conserje = $this->usuarioConRol('conserje', $proyecto);
        $publicacion = $this->crearPendiente($proyecto, $residente);
        Sanctum::actingAs($conserje);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->postJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/rechazar', [
            'motivo' => 'No corresponde al reglamento del edificio.',
        ], $headers)->assertOk();

        $this->postJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/aprobar', [], $headers)
            ->assertStatus(409);
    }

    private function usuarioConRol(string $rol, Proyecto $proyecto): User
    {
        $usuario = User::factory()->create([
            'rol' => $rol,
            'proyecto_id' => $proyecto->getKey(),
        ]);
        $usuario->proyectos()->attach($proyecto, ['rol' => $rol, 'estado' => 'activo']);

        return $usuario;
    }

    private function crearPendiente(Proyecto $proyecto, User $dueno): PublicacionMarketplace
    {
        return PublicacionMarketplace::query()->create([
            'proyecto_id' => $proyecto->getKey(),
            'usuario_id' => $dueno->getKey(),
            'titulo' => 'Solicitud de prueba',
            'descripcion' => 'Descripción de una solicitud de prueba.',
            'precio' => 10000,
            'categoria' => CategoriaProducto::Muebles->value,
            'condicion' => CondicionProducto::Usado->value,
            'estado' => EstadoPublicacion::Pendiente->value,
        ]);
    }
}
