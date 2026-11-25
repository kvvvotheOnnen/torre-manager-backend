<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Notificacion;
use App\Models\Proyecto;
use App\Models\SolicitudResidente;
use App\Models\UnidadPrincipal;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SolicitudesNotificacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_solicitud_aprobada_avisa_al_solicitante(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->usuarioConRol($proyecto, Rol::Administrador);
        $principal = $this->usuarioConRol($proyecto, Rol::Residente);
        $solicitud = $this->crearSolicitud($proyecto, $unidad, $principal);

        Sanctum::actingAs($admin);

        $this->postJson(
            '/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/aprobar',
            [],
            ['X-Proyecto-Id' => (string) $proyecto->getKey()],
        )->assertOk();

        $notificacion = Notificacion::query()
            ->where('usuario_id', $principal->getKey())
            ->where('titulo', 'Solicitud aprobada')
            ->first();

        $this->assertNotNull($notificacion);
        $this->assertFalse($notificacion->leida);
        $this->assertSame(
            (string) $solicitud->getKey(),
            (string) ($notificacion->datos['solicitud_id'] ?? ''),
        );
    }

    public function test_la_solicitud_rechazada_avisa_al_solicitante_con_el_motivo(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->usuarioConRol($proyecto, Rol::Administrador);
        $principal = $this->usuarioConRol($proyecto, Rol::Residente);
        $solicitud = $this->crearSolicitud($proyecto, $unidad, $principal);

        Sanctum::actingAs($admin);

        $this->postJson(
            '/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/rechazar',
            ['motivo' => 'Los datos del RUT no coinciden con el registro.'],
            ['X-Proyecto-Id' => (string) $proyecto->getKey()],
        )->assertOk();

        $notificacion = Notificacion::query()
            ->where('usuario_id', $principal->getKey())
            ->where('titulo', 'Solicitud rechazada')
            ->first();

        $this->assertNotNull($notificacion);
        $this->assertStringContainsString(
            'Los datos del RUT no coinciden con el registro.',
            $notificacion->mensaje,
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

    private function crearSolicitud(
        Proyecto $proyecto,
        UnidadPrincipal $unidad,
        User $principal,
    ): SolicitudResidente {
        $solicitud = SolicitudResidente::query()->create([
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'tipo_solicitud' => 'propietario',
            'origen' => 'seeder',
            'estado' => 'pendiente',
        ]);

        $solicitud->personas()->create([
            'usuario_id' => $principal->getKey(),
            'nombre' => $principal->nombre,
            'rut' => $principal->rut ?? '16000000-0',
            'email' => $principal->email,
            'telefono' => '+56970000001',
            'telefono_emergencia' => '+56971111111',
            'nombre_emergencia' => 'Contacto principal',
            'tipo_vinculo' => 'propietario',
            'es_principal' => true,
        ]);

        return $solicitud;
    }
}
