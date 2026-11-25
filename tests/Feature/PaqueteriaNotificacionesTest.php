<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Notificacion;
use App\Models\Paquete;
use App\Models\Proyecto;
use App\Models\UnidadPrincipal;
use App\Models\UnidadUsuario;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Features\Paqueteria\Domain\Events\PaqueteRegistrado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaqueteriaNotificacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_registro_notifica_a_todos_los_vinculados_actuales_de_la_unidad(): void
    {
        [$proyecto, $unidad] = $this->estructura();

        $propietario = $this->usuarioConVinculo($proyecto, $unidad, 'propietario');
        $arrendatario = $this->usuarioConVinculo($proyecto, $unidad, 'arrendatario');
        $residente = $this->usuarioConVinculo($proyecto, $unidad, 'residente');

        Sanctum::actingAs($this->conserje($proyecto));

        $this->postJson('/api/paqueteria', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'descripcion' => 'Caja mediana',
            'remitente' => 'Tienda Online',
        ])->assertCreated();

        foreach ([$propietario, $arrendatario, $residente] as $usuario) {
            $this->assertDatabaseHas('notificaciones', [
                'usuario_id' => $usuario->getKey(),
                'tipo' => 'paqueteria',
                'titulo' => 'Paquete recibido',
                'leida' => false,
            ]);
        }

        $this->assertSame(3, Notificacion::query()->count());
    }

    public function test_el_retiro_notifica_a_todos_los_vinculados_actuales_de_la_unidad(): void
    {
        [$proyecto, $unidad] = $this->estructura();

        $propietario = $this->usuarioConVinculo($proyecto, $unidad, 'propietario');
        $residente = $this->usuarioConVinculo($proyecto, $unidad, 'residente');

        $paquete = Paquete::factory()->create([
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'descripcion' => 'Sobre',
        ]);

        Sanctum::actingAs($this->conserje($proyecto));

        $this->patchJson(
            "/api/paqueteria/{$paquete->getKey()}/retirar",
            ['retirado_por_nombre' => 'Ana Conserje'],
            ['X-Proyecto-Id' => (string) $proyecto->getKey()],
        )->assertOk();

        foreach ([$propietario, $residente] as $usuario) {
            $this->assertDatabaseHas('notificaciones', [
                'usuario_id' => $usuario->getKey(),
                'tipo' => 'paqueteria',
                'titulo' => 'Paquete retirado',
            ]);
        }
    }

    public function test_sin_vinculos_notifica_al_residente_legacy_del_paquete(): void
    {
        [$proyecto] = $this->estructura();

        $residente = User::factory()->create([
            'rol' => Rol::Residente->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);

        event(new PaqueteRegistrado(
            paqueteId: '999',
            proyectoId: (string) $proyecto->getKey(),
            unidadPrincipalId: null,
            unidadIdentificador: '1204',
            descripcion: 'Caja pequeña',
            remitente: null,
            residenteId: (string) $residente->getKey(),
        ));

        $this->assertDatabaseHas('notificaciones', [
            'usuario_id' => $residente->getKey(),
            'tipo' => 'paqueteria',
            'titulo' => 'Paquete recibido',
        ]);
    }

    public function test_el_recordatorio_notifica_paquetes_de_mas_de_siete_dias_y_no_duplica(): void
    {
        [$proyecto, $unidad] = $this->estructura();

        $residente = $this->usuarioConVinculo($proyecto, $unidad, 'residente');

        $pendiente = Paquete::factory()->create([
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'fecha_recepcion' => now()->subDays(8),
        ]);

        $this->artisan('paquetes:recordar')->assertSuccessful();

        $this->assertDatabaseHas('notificaciones', [
            'usuario_id' => $residente->getKey(),
            'tipo' => 'paqueteria_recordatorio',
            'titulo' => 'Paquete sin retirar',
        ]);

        $total = Notificacion::query()->count();

        $this->artisan('paquetes:recordar')->assertSuccessful();

        $this->assertSame($total, Notificacion::query()->count());

        $retirado = Paquete::factory()->create([
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'fecha_recepcion' => now()->subDays(10),
            'estado' => 'retirado',
            'fecha_retiro' => now()->subDay(),
            'retirado_por_nombre' => 'Ana Conserje',
        ]);

        $this->artisan('paquetes:recordar')->assertSuccessful();

        $recordatoriosDelRetirado = Notificacion::query()
            ->where('tipo', 'paqueteria_recordatorio')
            ->get()
            ->pluck('datos')
            ->filter(fn ($datos) => is_array($datos)
                && (string) ($datos['paquete_id'] ?? '') === (string) $retirado->getKey());

        $this->assertCount(0, $recordatoriosDelRetirado);
    }

    /**
     * @return array{0: Proyecto, 1: UnidadPrincipal}
     */
    private function estructura(): array
    {
        $proyecto = Proyecto::factory()->create();
        $agrupacion = Agrupacion::factory()->for($proyecto)->create();
        $unidad = UnidadPrincipal::factory()->for($agrupacion)->create();

        return [$proyecto, $unidad];
    }

    private function conserje(Proyecto $proyecto): User
    {
        $conserje = User::factory()->create([
            'rol' => Rol::Conserje->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);

        $conserje->proyectos()->attach($proyecto, [
            'rol' => Rol::Conserje->value,
            'estado' => 'activo',
        ]);

        return $conserje;
    }

    private function usuarioConVinculo(Proyecto $proyecto, UnidadPrincipal $unidad, string $tipo): User
    {
        $usuario = User::factory()->create([
            'rol' => Rol::Residente->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);

        UnidadUsuario::query()->create([
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $usuario->getKey(),
            'tipo_vinculo' => $tipo,
            'fecha_inicio' => now()->toDateString(),
            'es_actual' => true,
        ]);

        return $usuario;
    }
}
