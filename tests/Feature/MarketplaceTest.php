<?php

namespace Tests\Feature;

use App\Models\FavoritoMarketplace;
use App\Models\Proyecto;
use App\Models\PublicacionMarketplace;
use App\Models\User;
use Features\Marketplace\Domain\Entities\CategoriaProducto;
use Features\Marketplace\Domain\Entities\CondicionProducto;
use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Features\Marketplace\Domain\Events\PublicacionEliminadaPorModeracion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_residente_crea_publicacion_con_precio_cero_y_solo_la_ve_en_sus_publicaciones(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        Sanctum::actingAs($residente);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $response = $this->postJson('/api/marketplace/publicaciones', [
            'titulo' => 'Silla de escritorio',
            'descripcion' => 'Silla giratoria en buen estado, se regala por cambio de muebles.',
            'precio' => 0,
            'categoria' => 'muebles',
            'condicion' => 'usado',
        ], $headers);

        $response->assertCreated()
            ->assertJsonPath('publicacion.titulo', 'Silla de escritorio')
            ->assertJsonPath('publicacion.precio', 0)
            ->assertJsonPath('publicacion.estado', 'pendiente')
            ->assertJsonPath('publicacion.es_mia', true)
            ->assertJsonPath('publicacion.contacto_disponible', false);

        $this->getJson('/api/marketplace/publicaciones', $headers)
            ->assertOk()
            ->assertJsonCount(0, 'publicaciones');

        $this->getJson('/api/marketplace/mis-publicaciones', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.titulo', 'Silla de escritorio')
            ->assertJsonPath('publicaciones.0.es_mia', true);
    }

    public function test_la_descripcion_es_obligatoria(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        Sanctum::actingAs($residente);

        $this->postJson('/api/marketplace/publicaciones', [
            'titulo' => 'Sin descripción',
            'precio' => 1000,
            'categoria' => 'otros',
            'condicion' => 'nuevo',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])
            ->assertStatus(422)
            ->assertJsonValidationErrors('descripcion');
    }

    public function test_el_catalogo_solo_muestra_disponibles_y_respeta_el_proyecto(): void
    {
        $proyecto = Proyecto::factory()->create();
        $otroProyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $vendedor = $this->usuarioConRol('residente', $proyecto);

        $this->crearPublicacion($proyecto, $vendedor, ['titulo' => 'Disponible']);
        $this->crearPublicacion($proyecto, $vendedor, [
            'titulo' => 'Vendida',
            'estado' => EstadoPublicacion::Vendida->value,
            'vendido_en' => now(),
        ]);
        $this->crearPublicacion($otroProyecto, $vendedor, ['titulo' => 'Otro proyecto']);

        Sanctum::actingAs($residente);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->getJson('/api/marketplace/publicaciones', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.titulo', 'Disponible');

        $admin = $this->usuarioConRol('administrador', $proyecto);
        Sanctum::actingAs($admin);

        $this->getJson('/api/marketplace/publicaciones?estado=todas', $headers)
            ->assertOk()
            ->assertJsonCount(2, 'publicaciones');

        Sanctum::actingAs($residente);

        $this->getJson('/api/marketplace/publicaciones?estado=todas', $headers)
            ->assertForbidden();
    }

    public function test_el_dueno_edita_retira_y_vende_sus_publicaciones(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $publicacion = $this->crearPublicacion($proyecto, $residente);
        Sanctum::actingAs($residente);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->putJson('/api/marketplace/publicaciones/'.$publicacion->getKey(), [
            'titulo' => 'Título actualizado',
            'descripcion' => 'Descripción actualizada de la publicación.',
            'precio' => 12000,
            'categoria' => 'electronica',
            'condicion' => 'semi_nuevo',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('publicacion.titulo', 'Título actualizado')
            ->assertJsonPath('publicacion.precio', 12000);

        $this->postJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/retirar', [], $headers)
            ->assertOk()
            ->assertJsonPath('publicacion.estado', 'retirada');

        $this->postJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/vender', [], $headers)
            ->assertStatus(409);

        $segunda = $this->crearPublicacion($proyecto, $residente, ['titulo' => 'Segunda']);

        $this->postJson('/api/marketplace/publicaciones/'.$segunda->getKey().'/vender', [], $headers)
            ->assertOk()
            ->assertJsonPath('publicacion.estado', 'vendida')
            ->assertJsonPath('publicacion.contacto_disponible', false)
            ->assertJsonPath('publicacion.puede_editar', false);

        $this->getJson('/api/marketplace/historial', $headers)
            ->assertOk()
            ->assertJsonCount(2, 'publicaciones');
    }

    public function test_no_puede_editar_ni_eliminar_la_publicacion_de_otro_residente(): void
    {
        $proyecto = Proyecto::factory()->create();
        $dueno = $this->usuarioConRol('residente', $proyecto);
        $otro = $this->usuarioConRol('residente', $proyecto);
        $publicacion = $this->crearPublicacion($proyecto, $dueno);
        Sanctum::actingAs($otro);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->putJson('/api/marketplace/publicaciones/'.$publicacion->getKey(), [
            'titulo' => 'Intento ajeno',
            'descripcion' => 'Intento de modificar una publicación ajena.',
            'precio' => 1000,
            'categoria' => 'otros',
            'condicion' => 'usado',
        ], $headers)->assertForbidden();

        $this->deleteJson('/api/marketplace/publicaciones/'.$publicacion->getKey(), [
            'motivo' => 'Motivo de prueba',
        ], $headers)->assertForbidden();
    }

    public function test_los_favoritos_se_agregan_y_quitan_pero_no_en_publicaciones_propias(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $vendedor = $this->usuarioConRol('residente', $proyecto);
        $publicacion = $this->crearPublicacion($proyecto, $vendedor);
        $propia = $this->crearPublicacion($proyecto, $residente, ['titulo' => 'Propia']);

        Sanctum::actingAs($residente);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->putJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/favorito', [
            'favorito' => true,
        ], $headers)
            ->assertOk()
            ->assertJsonPath('es_favorita', true);

        $this->getJson('/api/marketplace/favoritos', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.es_favorita', true);

        $this->putJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/favorito', [
            'favorito' => false,
        ], $headers)
            ->assertOk()
            ->assertJsonPath('es_favorita', false);

        $this->getJson('/api/marketplace/favoritos', $headers)
            ->assertOk()
            ->assertJsonCount(0, 'publicaciones');

        $this->putJson('/api/marketplace/publicaciones/'.$propia->getKey().'/favorito', [
            'favorito' => true,
        ], $headers)->assertForbidden();
    }

    public function test_los_favoritos_muestran_una_publicacion_vendida_solo_durante_tres_dias(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        $vendedor = $this->usuarioConRol('residente', $proyecto);

        $reciente = $this->crearPublicacion($proyecto, $vendedor, [
            'titulo' => 'Vendida reciente',
            'estado' => EstadoPublicacion::Vendida->value,
            'vendido_en' => now()->subDays(2),
        ]);
        $antigua = $this->crearPublicacion($proyecto, $vendedor, [
            'titulo' => 'Vendida antigua',
            'estado' => EstadoPublicacion::Vendida->value,
            'vendido_en' => now()->subDays(4),
        ]);

        foreach ([$reciente, $antigua] as $publicacion) {
            FavoritoMarketplace::query()->create([
                'publicacion_id' => $publicacion->getKey(),
                'usuario_id' => $residente->getKey(),
            ]);
        }

        Sanctum::actingAs($residente);

        $this->getJson('/api/marketplace/favoritos', [
            'X-Proyecto-Id' => (string) $proyecto->getKey(),
        ])
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.titulo', 'Vendida reciente')
            ->assertJsonPath('publicaciones.0.estado', 'vendida')
            ->assertJsonPath('publicaciones.0.contacto_disponible', false);
    }

    public function test_el_conserje_elimina_con_motivo_y_el_dueno_lo_ve_en_su_historial(): void
    {
        Event::fake();
        $proyecto = Proyecto::factory()->create();
        $dueno = $this->usuarioConRol('residente', $proyecto);
        $conserje = $this->usuarioConRol('conserje', $proyecto);
        $publicacion = $this->crearPublicacion($proyecto, $dueno);
        Sanctum::actingAs($conserje);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->deleteJson('/api/marketplace/publicaciones/'.$publicacion->getKey(), [
            'motivo' => 'Publicación duplicada en el catálogo.',
        ], $headers)->assertNoContent();

        $this->assertDatabaseHas('publicaciones_marketplace', [
            'id' => $publicacion->getKey(),
            'estado' => 'eliminada',
            'motivo_eliminacion' => 'Publicación duplicada en el catálogo.',
            'eliminado_por' => $conserje->getKey(),
            'eliminado_por_rol' => 'conserje',
        ]);

        Event::assertDispatched(
            PublicacionEliminadaPorModeracion::class,
            fn (PublicacionEliminadaPorModeracion $evento): bool => $evento->publicacionId === (string) $publicacion->getKey()
                && $evento->rolModerador === 'conserje'
                && $evento->motivo === 'Publicación duplicada en el catálogo.',
        );

        Sanctum::actingAs($dueno);

        $this->getJson('/api/marketplace/historial', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.estado', 'eliminada')
            ->assertJsonPath('publicaciones.0.motivo_eliminacion', 'Publicación duplicada en el catálogo.')
            ->assertJsonPath('publicaciones.0.eliminado_por_rol', 'conserje');
    }

    public function test_la_moderacion_exige_motivo(): void
    {
        $proyecto = Proyecto::factory()->create();
        $dueno = $this->usuarioConRol('residente', $proyecto);
        $conserje = $this->usuarioConRol('conserje', $proyecto);
        $publicacion = $this->crearPublicacion($proyecto, $dueno);
        Sanctum::actingAs($conserje);

        $this->deleteJson(
            '/api/marketplace/publicaciones/'.$publicacion->getKey(),
            [],
            ['X-Proyecto-Id' => (string) $proyecto->getKey()],
        )->assertStatus(422)->assertJsonValidationErrors('motivo');
    }

    public function test_el_administrador_ve_todas_y_puede_moderar_publicaciones_ajenas(): void
    {
        $proyecto = Proyecto::factory()->create();
        $dueno = $this->usuarioConRol('residente', $proyecto);
        $admin = $this->usuarioConRol('administrador', $proyecto);
        $disponible = $this->crearPublicacion($proyecto, $dueno, ['titulo' => 'Disponible']);
        $this->crearPublicacion($proyecto, $dueno, [
            'titulo' => 'Vendida',
            'estado' => EstadoPublicacion::Vendida->value,
            'vendido_en' => now(),
        ]);
        $this->crearPublicacion($proyecto, $dueno, [
            'titulo' => 'Eliminada',
            'estado' => EstadoPublicacion::Eliminada->value,
            'motivo_eliminacion' => 'Publicación duplicada en el catálogo.',
            'eliminado_por_rol' => 'conserje',
            'eliminado_en' => now(),
        ]);

        Sanctum::actingAs($admin);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->getJson('/api/marketplace/publicaciones?estado=todas', $headers)
            ->assertOk()
            ->assertJsonCount(2, 'publicaciones')
            ->assertJsonFragment(['titulo' => 'Disponible'])
            ->assertJsonFragment(['titulo' => 'Vendida'])
            ->assertJsonMissing(['titulo' => 'Eliminada']);

        $this->deleteJson('/api/marketplace/publicaciones/'.$disponible->getKey(), [
            'motivo' => 'El artículo no cumple las normas de la comunidad.',
        ], $headers)->assertNoContent();

        $this->assertDatabaseHas('publicaciones_marketplace', [
            'id' => $disponible->getKey(),
            'estado' => 'eliminada',
        ]);
    }

    public function test_el_administrador_lista_solo_las_publicaciones_eliminadas(): void
    {
        $proyecto = Proyecto::factory()->create();
        $dueno = $this->usuarioConRol('residente', $proyecto);
        $conserje = $this->usuarioConRol('conserje', $proyecto);
        $admin = $this->usuarioConRol('administrador', $proyecto);

        $this->crearPublicacion($proyecto, $dueno, ['titulo' => 'Disponible']);
        $this->crearPublicacion($proyecto, $dueno, [
            'titulo' => 'Vendida',
            'estado' => EstadoPublicacion::Vendida->value,
            'vendido_en' => now(),
        ]);
        $this->crearPublicacion($proyecto, $dueno, [
            'titulo' => 'Eliminada',
            'estado' => EstadoPublicacion::Eliminada->value,
            'motivo_eliminacion' => 'Publicación duplicada en el catálogo.',
            'eliminado_por' => $conserje->getKey(),
            'eliminado_por_rol' => 'conserje',
            'eliminado_en' => now(),
        ]);

        Sanctum::actingAs($admin);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->getJson('/api/marketplace/publicaciones?estado=eliminada', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.titulo', 'Eliminada')
            ->assertJsonPath('publicaciones.0.estado', 'eliminada')
            ->assertJsonPath('publicaciones.0.motivo_eliminacion', 'Publicación duplicada en el catálogo.');

        $this->getJson('/api/marketplace/publicaciones?estado=disponible', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.titulo', 'Disponible');

        Sanctum::actingAs($dueno);

        $this->getJson('/api/marketplace/publicaciones?estado=eliminada', $headers)
            ->assertForbidden();
    }

    public function test_el_administrador_solo_ve_sus_publicaciones_en_sus_espacios(): void
    {
        $proyecto = Proyecto::factory()->create();
        $dueno = $this->usuarioConRol('residente', $proyecto);
        $admin = $this->usuarioConRol('administrador', $proyecto);

        $this->crearPublicacion($proyecto, $dueno, ['titulo' => 'Ajena disponible']);
        $this->crearPublicacion($proyecto, $admin, ['titulo' => 'Propia disponible']);
        $this->crearPublicacion($proyecto, $admin, [
            'titulo' => 'Propia vendida',
            'estado' => EstadoPublicacion::Vendida->value,
            'vendido_en' => now(),
        ]);
        $this->crearPublicacion($proyecto, $admin, [
            'titulo' => 'Propia eliminada',
            'estado' => EstadoPublicacion::Eliminada->value,
            'motivo_eliminacion' => 'Publicación duplicada en el catálogo.',
            'eliminado_por_rol' => 'conserje',
            'eliminado_en' => now(),
        ]);

        Sanctum::actingAs($admin);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->getJson('/api/marketplace/publicaciones', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.titulo', 'Ajena disponible');

        $this->getJson('/api/marketplace/publicaciones?estado=todas', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.titulo', 'Ajena disponible');

        $this->getJson('/api/marketplace/publicaciones?estado=disponible', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.titulo', 'Ajena disponible');

        $this->getJson('/api/marketplace/publicaciones?estado=eliminada', $headers)
            ->assertOk()
            ->assertJsonCount(0, 'publicaciones');

        $this->getJson('/api/marketplace/mis-publicaciones', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'publicaciones')
            ->assertJsonPath('publicaciones.0.titulo', 'Propia disponible');

        $this->getJson('/api/marketplace/historial', $headers)
            ->assertOk()
            ->assertJsonCount(2, 'publicaciones');
    }

    public function test_el_administrador_elimina_su_propia_publicacion_sin_motivo(): void
    {
        $proyecto = Proyecto::factory()->create();
        $admin = $this->usuarioConRol('administrador', $proyecto);
        $publicacion = $this->crearPublicacion($proyecto, $admin);
        Sanctum::actingAs($admin);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->postJson('/api/marketplace/publicaciones/'.$publicacion->getKey().'/retirar', [], $headers)
            ->assertOk()
            ->assertJsonPath('publicacion.estado', 'retirada');

        $this->deleteJson('/api/marketplace/publicaciones/'.$publicacion->getKey(), [
            'motivo' => 'Este motivo no debería usarse para una publicación propia.',
        ], $headers)->assertForbidden();
    }

    public function test_el_conserje_no_puede_crear_ni_editar_publicaciones(): void
    {
        $proyecto = Proyecto::factory()->create();
        $dueno = $this->usuarioConRol('residente', $proyecto);
        $conserje = $this->usuarioConRol('conserje', $proyecto);
        $publicacion = $this->crearPublicacion($proyecto, $dueno);
        Sanctum::actingAs($conserje);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->postJson('/api/marketplace/publicaciones', [
            'titulo' => 'Intento de conserje',
            'descripcion' => 'El conserje no debería poder publicar.',
            'precio' => 1000,
            'categoria' => 'otros',
            'condicion' => 'usado',
        ], $headers)->assertForbidden();

        $this->putJson('/api/marketplace/publicaciones/'.$publicacion->getKey(), [
            'titulo' => 'Intento de edición',
            'descripcion' => 'El conserje no debería poder editar.',
            'precio' => 1000,
            'categoria' => 'otros',
            'condicion' => 'usado',
        ], $headers)->assertForbidden();
    }

    public function test_guarda_la_imagen_de_la_publicacion(): void
    {
        Storage::fake('public');
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioConRol('residente', $proyecto);
        Sanctum::actingAs($residente);

        $this->post('/api/marketplace/publicaciones', [
            'titulo' => 'Lámpara de escritorio',
            'descripcion' => 'Lámpara regulable con ampolleta incluida.',
            'precio' => 8000,
            'categoria' => 'muebles',
            'condicion' => 'usado',
            'foto' => UploadedFile::fake()->create('lampara.jpg', 120, 'image/jpeg'),
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])->assertCreated();

        $ruta = PublicacionMarketplace::query()->firstOrFail()->foto_path;

        $this->assertNotNull($ruta);
        Storage::disk('public')->assertExists($ruta);
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

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function crearPublicacion(Proyecto $proyecto, User $dueno, array $cambios = []): PublicacionMarketplace
    {
        return PublicacionMarketplace::query()->create($cambios + [
            'proyecto_id' => $proyecto->getKey(),
            'usuario_id' => $dueno->getKey(),
            'titulo' => 'Publicación de prueba',
            'descripcion' => 'Descripción de una publicación de prueba.',
            'precio' => 10000,
            'categoria' => CategoriaProducto::Muebles->value,
            'condicion' => CondicionProducto::Usado->value,
            'estado' => EstadoPublicacion::Disponible->value,
        ]);
    }
}
