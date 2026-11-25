<?php

namespace Tests\Feature;

use App\Models\Notificacion as NotificacionModel;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class NotificacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_usuario_lista_solo_sus_notificaciones_mas_recientes_primero(): void
    {
        $proyecto = Proyecto::factory()->create();
        $usuario = $this->usuarioConRol('residente', $proyecto);
        $otro = $this->usuarioConRol('residente', $proyecto);

        $this->crearNotificacion($proyecto, $usuario, 'Primera');
        $this->crearNotificacion($proyecto, $usuario, 'Segunda');
        $this->crearNotificacion($proyecto, $otro, 'Ajena');

        Sanctum::actingAs($usuario);

        $this->getJson('/api/notificaciones', $this->headers($proyecto))
            ->assertOk()
            ->assertJsonCount(2, 'notificaciones')
            ->assertJsonPath('notificaciones.0.titulo', 'Segunda')
            ->assertJsonPath('notificaciones.1.titulo', 'Primera')
            ->assertJsonPath('notificaciones.0.leida', false);
    }

    public function test_el_contador_devuelve_solo_las_no_leidas_del_usuario(): void
    {
        $proyecto = Proyecto::factory()->create();
        $usuario = $this->usuarioConRol('residente', $proyecto);
        $otro = $this->usuarioConRol('residente', $proyecto);

        $this->crearNotificacion($proyecto, $usuario, 'Pendiente 1');
        $this->crearNotificacion($proyecto, $usuario, 'Pendiente 2');
        $this->crearNotificacion($proyecto, $usuario, 'Leída', leida: true);
        $this->crearNotificacion($proyecto, $otro, 'Ajena');

        Sanctum::actingAs($usuario);

        $this->getJson('/api/notificaciones/no-leidas', $this->headers($proyecto))
            ->assertOk()
            ->assertJsonPath('no_leidas', 2);
    }

    public function test_marcar_una_notificacion_como_leida_actualiza_el_contador(): void
    {
        $proyecto = Proyecto::factory()->create();
        $usuario = $this->usuarioConRol('conserje', $proyecto);
        $notificacion = $this->crearNotificacion($proyecto, $usuario);

        Sanctum::actingAs($usuario);

        $this->patchJson('/api/notificaciones/'.$notificacion->getKey().'/leer', [], $this->headers($proyecto))
            ->assertOk()
            ->assertJsonPath('notificacion.leida', true);

        $this->getJson('/api/notificaciones/no-leidas', $this->headers($proyecto))
            ->assertOk()
            ->assertJsonPath('no_leidas', 0);
    }

    public function test_marcar_todas_las_notificaciones_como_leidas(): void
    {
        $proyecto = Proyecto::factory()->create();
        $usuario = $this->usuarioConRol('administrador', $proyecto);

        $this->crearNotificacion($proyecto, $usuario, 'Uno');
        $this->crearNotificacion($proyecto, $usuario, 'Dos');

        Sanctum::actingAs($usuario);

        $this->patchJson('/api/notificaciones/leer-todas', [], $this->headers($proyecto))
            ->assertOk()
            ->assertJsonPath('marcadas', 2);

        $this->getJson('/api/notificaciones/no-leidas', $this->headers($proyecto))
            ->assertOk()
            ->assertJsonPath('no_leidas', 0);
    }

    public function test_no_puede_marcar_como_leida_una_notificacion_ajena(): void
    {
        $proyecto = Proyecto::factory()->create();
        $usuario = $this->usuarioConRol('residente', $proyecto);
        $otro = $this->usuarioConRol('residente', $proyecto);
        $notificacion = $this->crearNotificacion($proyecto, $otro);

        Sanctum::actingAs($usuario);

        $this->patchJson('/api/notificaciones/'.$notificacion->getKey().'/leer', [], $this->headers($proyecto))
            ->assertNotFound();

        $this->assertFalse($notificacion->refresh()->leida);
    }

    public function test_sin_proyecto_activo_la_api_responde_404(): void
    {
        $proyecto = Proyecto::factory()->create();
        $usuario = $this->usuarioConRol('residente', $proyecto);

        Sanctum::actingAs($usuario);

        $this->getJson('/api/notificaciones')
            ->assertNotFound()
            ->assertJsonPath('message', 'Proyecto no encontrado.');
    }

    private function usuarioConRol(string $rol, Proyecto $proyecto): User
    {
        $usuario = User::factory()->create([
            'rol' => $rol,
            'proyecto_id' => $proyecto->getKey(),
        ]);

        $usuario->proyectos()->attach($proyecto, [
            'rol' => $rol,
            'estado' => 'activo',
        ]);

        return $usuario;
    }

    /**
     * @return array<string, string>
     */
    private function headers(Proyecto $proyecto): array
    {
        return ['X-Proyecto-Id' => (string) $proyecto->getKey()];
    }

    private function crearNotificacion(
        Proyecto $proyecto,
        User $usuario,
        string $titulo = 'Aviso',
        bool $leida = false,
    ): NotificacionModel {
        return NotificacionModel::query()->create([
            'usuario_id' => $usuario->getKey(),
            'proyecto_id' => $proyecto->getKey(),
            'tipo' => 'prueba',
            'titulo' => $titulo,
            'mensaje' => 'Mensaje de prueba.',
            'leida' => $leida,
        ]);
    }
}
