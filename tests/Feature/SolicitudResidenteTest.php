<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Proyecto;
use App\Models\ProyectoUsuario;
use App\Models\SolicitudResidente;
use App\Models\UnidadAccesoria;
use App\Models\UnidadPrincipal;
use App\Models\UnidadUsuario;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SolicitudResidenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lista_y_detalle_incluye_instalaciones(): void
    {
        [$proyecto, $unidad] = $this->estructura();

        foreach (['B-900' => 'bodega', 'E-900' => 'estacionamiento'] as $identificador => $tipo) {
            UnidadAccesoria::query()->create([
                'proyecto_id' => $proyecto->getKey(),
                'unidad_principal_id' => $unidad->getKey(),
                'identificador' => $identificador,
                'tipo' => $tipo,
                'asignacion' => 'departamento',
            ]);
        }

        $principal = $this->usuario('Detalle Demo', 'detalle@example.test');
        $solicitud = $this->crearSolicitud($proyecto, $unidad, 'propietario', $principal);
        $admin = $this->staff($proyecto, Rol::Administrador);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/solicitudes-residentes', $this->headers($proyecto))
            ->assertOk()
            ->assertJsonCount(1, 'solicitudes');

        $this->getJson('/api/admin/solicitudes-residentes/'.$solicitud->getKey(), $this->headers($proyecto))
            ->assertOk()
            ->assertJsonCount(2, 'instalaciones')
            ->assertJsonCount(1, 'personas')
            ->assertJsonPath('personas.0.es_principal', true);
    }

    public function test_aprobar_no_crea_usuarios_y_vincula_solo_al_principal(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);
        $principal = $this->usuario('Ana Demo', 'ana.principal@example.test');
        $solicitud = $this->crearSolicitud($proyecto, $unidad, 'propietario', $principal, [
            ['nombre' => 'Luis Demo', 'rut' => '15000002-5', 'email' => 'luis.acompanante@example.test'],
        ]);

        $usuariosAntes = User::query()->count();

        Sanctum::actingAs($admin);

        $this->postJson(
            '/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/aprobar',
            [],
            $this->headers($proyecto),
        )->assertOk()->assertJsonPath('estado', 'aprobada');

        $this->assertSame($usuariosAntes, User::query()->count());

        $this->assertDatabaseHas('proyecto_usuario', [
            'proyecto_id' => $proyecto->getKey(),
            'usuario_id' => $principal->getKey(),
            'rol' => Rol::Propietario->value,
            'estado' => 'activo',
        ]);
        $this->assertDatabaseHas('unidad_usuario', [
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $principal->getKey(),
            'tipo_vinculo' => 'propietario',
            'es_actual' => true,
        ]);
        $this->assertSame(0, UnidadUsuario::query()->where('tipo_vinculo', 'familiar')->count());
        $this->assertSame(1, UnidadUsuario::query()->where('unidad_principal_id', $unidad->getKey())->count());
    }

    public function test_aprobar_arrendatario_sincroniza_ocupante_legacy(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);
        $principal = $this->usuario('Carla Demo', 'carla.principal@example.test');
        $solicitud = $this->crearSolicitud($proyecto, $unidad, 'arrendatario', $principal);

        Sanctum::actingAs($admin);

        $this->postJson(
            '/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/aprobar',
            [],
            $this->headers($proyecto),
        )->assertOk();

        $this->assertDatabaseHas('unidad_usuario', [
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $principal->getKey(),
            'tipo_vinculo' => 'arrendatario',
            'es_actual' => true,
        ]);
        $this->assertDatabaseHas('proyecto_usuario', [
            'proyecto_id' => $proyecto->getKey(),
            'usuario_id' => $principal->getKey(),
            'rol' => Rol::Residente->value,
        ]);
        $this->assertDatabaseHas('unidades_principales', [
            'id' => $unidad->getKey(),
            'residente_id' => $principal->getKey(),
        ]);
    }

    public function test_aprobar_residente_permite_multiples_actuales(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);
        $anterior = $this->usuario('Residente Anterior', 'residente.anterior@example.test');
        UnidadUsuario::query()->create([
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $anterior->getKey(),
            'tipo_vinculo' => 'residente',
            'fecha_inicio' => '2025-01-01',
            'es_actual' => true,
        ]);

        $nuevo = $this->usuario('Residente Nuevo', 'residente.nuevo@example.test');
        $solicitud = $this->crearSolicitud($proyecto, $unidad, 'residente', $nuevo);

        Sanctum::actingAs($admin);

        $this->postJson(
            '/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/aprobar',
            [],
            $this->headers($proyecto),
        )->assertOk();

        $this->assertSame(
            2,
            UnidadUsuario::query()
                ->where('unidad_principal_id', $unidad->getKey())
                ->where('tipo_vinculo', 'residente')
                ->where('es_actual', true)
                ->count(),
        );
    }

    public function test_aprobar_propietario_cierra_el_anterior(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);
        $anterior = $this->usuario('Propietario Anterior', 'propietario.anterior@example.test');
        UnidadUsuario::query()->create([
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $anterior->getKey(),
            'tipo_vinculo' => 'propietario',
            'fecha_inicio' => '2025-01-01',
            'es_actual' => true,
        ]);

        $nuevo = $this->usuario('Propietario Nuevo', 'propietario.nuevo@example.test');
        $solicitud = $this->crearSolicitud($proyecto, $unidad, 'propietario', $nuevo);

        Sanctum::actingAs($admin);

        $this->postJson(
            '/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/aprobar',
            [],
            $this->headers($proyecto),
        )->assertOk();

        $this->assertDatabaseHas('unidad_usuario', [
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $anterior->getKey(),
            'tipo_vinculo' => 'propietario',
            'es_actual' => false,
        ]);
        $this->assertSame(
            1,
            UnidadUsuario::query()
                ->where('unidad_principal_id', $unidad->getKey())
                ->where('tipo_vinculo', 'propietario')
                ->where('es_actual', true)
                ->count(),
        );
    }

    public function test_aprobar_sin_usuario_principal_asociado_falla(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);
        $solicitud = $this->crearSolicitud($proyecto, $unidad, 'propietario', null);

        Sanctum::actingAs($admin);

        $this->postJson(
            '/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/aprobar',
            [],
            $this->headers($proyecto),
        )->assertStatus(422);

        $this->assertDatabaseHas('solicitudes_residente', [
            'id' => $solicitud->getKey(),
            'estado' => 'pendiente',
        ]);
        $this->assertSame(0, UnidadUsuario::query()->count());
    }

    public function test_no_se_puede_decidir_dos_veces(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);
        $principal = $this->usuario('Único Demo', 'unico@example.test');
        $solicitud = $this->crearSolicitud($proyecto, $unidad, 'propietario', $principal);

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/aprobar', [], $this->headers($proyecto))
            ->assertOk();
        $this->postJson('/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/aprobar', [], $this->headers($proyecto))
            ->assertStatus(409);
        $this->postJson('/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/rechazar', ['motivo' => 'Duplicada'], $this->headers($proyecto))
            ->assertStatus(409);
    }

    public function test_rechazar_no_modifica_al_usuario_y_guarda_el_motivo(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);
        $principal = $this->usuario('Rechazo Demo', 'rechazo@example.test');
        $solicitud = $this->crearSolicitud($proyecto, $unidad, 'propietario', $principal);

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/rechazar', [], $this->headers($proyecto))
            ->assertStatus(422);

        $this->postJson('/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/rechazar', [
            'motivo' => 'Los datos no coinciden con la unidad.',
        ], $this->headers($proyecto))
            ->assertOk()
            ->assertJsonPath('estado', 'rechazada')
            ->assertJsonPath('motivo_rechazo', 'Los datos no coinciden con la unidad.');

        $this->assertDatabaseHas('usuarios', ['id' => $principal->getKey(), 'email' => $principal->email]);
        $this->assertSame(0, UnidadUsuario::query()->count());
        $this->assertSame(0, ProyectoUsuario::query()
            ->where('usuario_id', $principal->getKey())
            ->count());

        $this->postJson('/api/login', [
            'email' => $principal->email,
            'password' => 'password',
        ])->assertOk();
    }

    public function test_historial_solo_incluye_solicitudes_decididas(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);

        $aprobada = $this->crearSolicitud($proyecto, $unidad, 'propietario', $this->usuario('Uno', 'uno@example.test'));
        $unidad2 = UnidadPrincipal::factory()->for($unidad->agrupacion)->create(['identificador' => 'U-2']);
        $rechazada = $this->crearSolicitud($proyecto, $unidad2, 'arrendatario', $this->usuario('Dos', 'dos@example.test'));
        $unidad3 = UnidadPrincipal::factory()->for($unidad->agrupacion)->create(['identificador' => 'U-3']);
        $this->crearSolicitud($proyecto, $unidad3, 'propietario', $this->usuario('Tres', 'tres@example.test'));

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/solicitudes-residentes/'.$aprobada->getKey().'/aprobar', [], $this->headers($proyecto))
            ->assertOk();
        $this->postJson('/api/admin/solicitudes-residentes/'.$rechazada->getKey().'/rechazar', ['motivo' => 'Documentación incompleta'], $this->headers($proyecto))
            ->assertOk();

        $this->getJson('/api/admin/solicitudes-residentes/historial', $this->headers($proyecto))
            ->assertOk()
            ->assertJsonCount(2, 'solicitudes');
    }

    public function test_permisos_por_rol_y_proyecto(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        [$otroProyecto, $otraUnidad] = $this->estructura('Otro Proyecto');
        $admin = $this->staff($proyecto, Rol::Administrador);
        $conserje = $this->staff($proyecto, Rol::Conserje);
        $residente = $this->staff($proyecto, Rol::Residente);

        $propia = $this->crearSolicitud($proyecto, $unidad, 'propietario', $this->usuario('Propia', 'propia@example.test'));
        $ajena = $this->crearSolicitud($otroProyecto, $otraUnidad, 'propietario', $this->usuario('Ajena', 'ajena@example.test'));

        Sanctum::actingAs($residente);
        $this->getJson('/api/admin/solicitudes-residentes', $this->headers($proyecto))->assertForbidden();

        Sanctum::actingAs($conserje);
        $this->getJson('/api/admin/solicitudes-residentes', $this->headers($proyecto))->assertOk();

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/solicitudes-residentes/'.$ajena->getKey(), $this->headers($proyecto))->assertNotFound();
        $this->getJson('/api/admin/solicitudes-residentes/'.$propia->getKey(), $this->headers($proyecto))->assertOk();
    }

    public function test_usuario_pendiente_puede_login_pero_no_accede_al_dashboard(): void
    {
        [$proyecto] = $this->estructura();
        $principal = $this->usuario('Pendiente Demo', 'pendiente@example.test');
        $this->crearSolicitud($proyecto, $this->estructuraUnidad($proyecto, 'P-1'), 'propietario', $principal);

        $this->postJson('/api/login', [
            'email' => $principal->email,
            'password' => 'password',
        ])->assertOk();

        Sanctum::actingAs($principal);

        $this->getJson('/api/mis-proyectos')->assertOk()->assertJsonCount(0, 'proyectos');
        $this->getJson('/api/mis-unidades')->assertOk()->assertJsonCount(0, 'unidades');
        $this->getJson('/api/mis-solicitudes-residentes')
            ->assertOk()
            ->assertJsonPath('solicitudes.0.estado', 'pendiente');
        $this->getJson('/api/dashboard/modulos', $this->headers($proyecto))
            ->assertNotFound();
    }

    public function test_usuario_aprobado_ve_proyecto_y_unidad(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);
        $principal = $this->usuario('Aprobado Demo', 'aprobado@example.test');
        $solicitud = $this->crearSolicitud($proyecto, $unidad, 'propietario', $principal);

        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/aprobar', [], $this->headers($proyecto))
            ->assertOk();

        Sanctum::actingAs($principal);

        $this->getJson('/api/mis-proyectos')
            ->assertOk()
            ->assertJsonCount(1, 'proyectos')
            ->assertJsonPath('proyectos.0.id', (string) $proyecto->getKey())
            ->assertJsonPath('proyectos.0.rol', Rol::Propietario->value);
        $this->getJson('/api/mis-unidades')
            ->assertOk()
            ->assertJsonPath('unidades.0.unidad', $unidad->identificador);
        $this->getJson('/api/mis-solicitudes-residentes')
            ->assertOk()
            ->assertJsonPath('solicitudes.0.estado', 'aprobada');
    }

    public function test_usuario_rechazado_puede_login_y_ve_su_motivo(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);
        $principal = $this->usuario('Rechazado Login', 'rechazado.login@example.test');
        $solicitud = $this->crearSolicitud($proyecto, $unidad, 'propietario', $principal);

        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/rechazar', ['motivo' => 'Datos incompletos'], $this->headers($proyecto))
            ->assertOk();

        Sanctum::actingAs($principal);

        $this->getJson('/api/mis-proyectos')->assertOk()->assertJsonCount(0, 'proyectos');
        $this->getJson('/api/mis-solicitudes-residentes')
            ->assertOk()
            ->assertJsonPath('solicitudes.0.estado', 'rechazada')
            ->assertJsonPath('solicitudes.0.motivo_rechazo', 'Datos incompletos');
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

    private function estructuraUnidad(Proyecto $proyecto, string $identificador): UnidadPrincipal
    {
        $agrupacion = $proyecto->agrupaciones()->firstOrCreate(
            ['nombre' => 'Edificio '.$proyecto->nombre],
            ['tipo' => 'edificio'],
        );

        return UnidadPrincipal::factory()->for($agrupacion)->create([
            'identificador' => $identificador,
        ]);
    }

    private function staff(Proyecto $proyecto, Rol $rol): User
    {
        $usuario = $rol === Rol::Administrador
            ? User::factory()->administrador()->create()
            : User::factory()->create(['rol' => $rol->value]);

        $usuario->proyectos()->attach($proyecto, ['rol' => $rol->value, 'estado' => 'activo']);

        return $usuario;
    }

    private function usuario(string $nombre, string $email): User
    {
        return User::factory()->create([
            'nombre' => $nombre,
            'email' => $email,
            'password' => 'password',
            'rol' => Rol::Residente->value,
            'proyecto_id' => null,
        ]);
    }

    /**
     * @param  array<int, array{nombre: string, rut: string, email: string}>  $acompanantes
     */
    private function crearSolicitud(
        Proyecto $proyecto,
        UnidadPrincipal $unidad,
        string $tipo,
        ?User $principal,
        array $acompanantes = [],
    ): SolicitudResidente {
        $solicitud = SolicitudResidente::query()->create([
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'tipo_solicitud' => $tipo,
            'origen' => 'seeder',
            'estado' => 'pendiente',
        ]);

        $solicitud->personas()->create([
            'usuario_id' => $principal?->getKey(),
            'nombre' => $principal?->nombre ?? 'Sin cuenta',
            'rut' => $principal?->rut ?? '16000000-0',
            'email' => $principal?->email ?? 'sin.cuenta@example.test',
            'telefono' => '+56970000001',
            'telefono_emergencia' => '+56971111111',
            'nombre_emergencia' => 'Contacto principal',
            'tipo_vinculo' => $tipo,
            'es_principal' => true,
        ]);

        foreach ($acompanantes as $indice => $acompanante) {
            $solicitud->personas()->create([
                'usuario_id' => null,
                'nombre' => $acompanante['nombre'],
                'rut' => $acompanante['rut'],
                'email' => $acompanante['email'],
                'telefono' => '+5697000000'.($indice + 2),
                'telefono_emergencia' => '+5697111111'.($indice + 2),
                'nombre_emergencia' => 'Contacto acompañante',
                'tipo_vinculo' => 'residente',
                'es_principal' => false,
            ]);
        }

        return $solicitud;
    }

    /** @return array<string, string> */
    private function headers(Proyecto $proyecto): array
    {
        return ['X-Proyecto-Id' => (string) $proyecto->getKey()];
    }
}
