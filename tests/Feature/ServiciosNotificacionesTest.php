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

class ServiciosNotificacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_trabajador_publicado_avisa_a_residentes_propietarios_y_conserjes(): void
    {
        [$proyecto] = $this->estructura();

        $admin = $this->usuarioConRol($proyecto, Rol::Administrador);
        $residente = $this->usuarioConRol($proyecto, Rol::Residente);
        $propietario = $this->usuarioConRol($proyecto, Rol::Propietario);
        $conserje = $this->usuarioConRol($proyecto, Rol::Conserje);

        Sanctum::actingAs($admin);

        $this->postJson('/api/servicios', [
            'nombre' => 'Carlos Soto',
            'nombre_empresa' => 'Soto Cerrajería',
            'telefono' => '+56911112222',
            'tipo_servicio' => 'cerrajero',
            'descripcion' => 'Apertura de puertas y cambio de chapas.',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])->assertCreated();

        foreach ([$residente, $propietario, $conserje] as $usuario) {
            $notificacion = Notificacion::query()
                ->where('usuario_id', $usuario->getKey())
                ->where('titulo', 'Nuevo servicio recomendado')
                ->first();

            $this->assertNotNull($notificacion);
            $this->assertStringContainsString('Carlos Soto', $notificacion->mensaje);
            $this->assertFalse($notificacion->leida);
        }

        $this->assertSame(
            0,
            Notificacion::query()
                ->where('usuario_id', $admin->getKey())
                ->where('tipo', 'servicios')
                ->count(),
        );
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
