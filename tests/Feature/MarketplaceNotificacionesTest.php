<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\Proyecto;
use App\Models\PublicacionMarketplace;
use App\Models\User;
use Features\Marketplace\Domain\Entities\CategoriaProducto;
use Features\Marketplace\Domain\Entities\CondicionProducto;
use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MarketplaceNotificacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_publicacion_del_residente_avisa_al_administrador_y_al_conserje(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $admin = $this->usuarioConRol('administrador', $proyecto);
        $conserje = $this->usuarioConRol('conserje', $proyecto);
        Sanctum::actingAs($residente);

        $this->postJson('/api/marketplace/publicaciones', [
            'titulo' => 'Bicicleta fija',
            'descripcion' => 'Bicicleta fija con resistencia magnética, poco uso.',
            'precio' => 60000,
            'categoria' => 'otros',
            'condicion' => 'semi_nuevo',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])->assertCreated();

        $this->assertNotificacion($admin, 'Publicación por revisar');
        $this->assertNotificacion($conserje, 'Publicación por revisar');
        $this->assertSame(0, Notificacion::query()
            ->where('usuario_id', $residente->getKey())
            ->count());
    }

    public function test_el_conserje_aprueba_y_avisa_al_dueno(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $conserje = $this->usuarioConRol('conserje', $proyecto);
        $publicacion = $this->crearPublicacion($proyecto, $residente, EstadoPublicacion::Pendiente);
        Sanctum::actingAs($conserje);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->postJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/aprobar', [], $headers)
            ->assertOk();

        $notificacion = $this->assertNotificacion($residente, 'Publicación aprobada');
        $this->assertStringContainsString('Solicitud de prueba', $notificacion->mensaje);
        $this->assertSame(0, Notificacion::query()
            ->where('usuario_id', $conserje->getKey())
            ->where('titulo', 'Publicación aprobada')
            ->count());
    }

    public function test_el_conserje_rechaza_y_avisa_al_dueno_con_el_motivo(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $conserje = $this->usuarioConRol('conserje', $proyecto);
        $publicacion = $this->crearPublicacion($proyecto, $residente, EstadoPublicacion::Pendiente);
        Sanctum::actingAs($conserje);

        $this->postJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/rechazar', [
            'motivo' => 'Fotografía ilegible del artículo.',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])->assertOk();

        $notificacion = $this->assertNotificacion($residente, 'Publicación rechazada');
        $this->assertStringContainsString('Fotografía ilegible del artículo.', $notificacion->mensaje);
    }

    public function test_el_conserje_elimina_por_moderacion_y_avisa_al_dueno(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $conserje = $this->usuarioConRol('conserje', $proyecto);
        $publicacion = $this->crearPublicacion($proyecto, $residente, EstadoPublicacion::Disponible);
        Sanctum::actingAs($conserje);

        $this->deleteJson('/api/marketplace/publicaciones/'.$publicacion->getKey(), [
            'motivo' => 'Producto prohibido por el reglamento.',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])->assertNoContent();

        $notificacion = $this->assertNotificacion($residente, 'Publicación eliminada');
        $this->assertStringContainsString('Producto prohibido por el reglamento.', $notificacion->mensaje);
    }

    private function assertNotificacion(User $usuario, string $titulo): Notificacion
    {
        $notificacion = Notificacion::query()
            ->where('usuario_id', $usuario->getKey())
            ->where('titulo', $titulo)
            ->first();

        $this->assertNotNull(
            $notificacion,
            "No se encontró la notificación \"{$titulo}\" para el usuario {$usuario->getKey()}.",
        );

        return $notificacion;
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

    private function crearPublicacion(
        Proyecto $proyecto,
        User $dueno,
        EstadoPublicacion $estado,
    ): PublicacionMarketplace {
        return PublicacionMarketplace::query()->create([
            'proyecto_id' => $proyecto->getKey(),
            'usuario_id' => $dueno->getKey(),
            'titulo' => 'Solicitud de prueba',
            'descripcion' => 'Descripción de una solicitud de prueba.',
            'precio' => 10000,
            'categoria' => CategoriaProducto::Muebles->value,
            'condicion' => CondicionProducto::Usado->value,
            'estado' => $estado->value,
        ]);
    }
}
