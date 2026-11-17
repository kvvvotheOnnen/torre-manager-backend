<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Proyecto;
use App\Models\UnidadAccesoria;
use App\Models\UnidadPrincipal;
use App\Models\UnidadUsuario;
use App\Models\User;
use Features\Auth\Domain\Entities\EstadoUsuario;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SolicitudResidenteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_crear_solicitud_publica_crea_personas_y_usuarios_pendientes(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $personas = $this->personas();

        $response = $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'tipo_solicitud' => 'propietario',
            'personas' => $personas,
        ])->assertCreated()
            ->assertJsonPath('estado', 'pendiente')
            ->assertJsonPath('tipo_solicitud', 'propietario')
            ->assertJsonCount(2, 'personas')
            ->assertJsonPath('personas.0.es_principal', true)
            ->assertJsonPath('personas.1.es_principal', false);

        $solicitudId = $response->json('id');
        $this->assertNotNull($solicitudId);

        foreach ([$personas[0]['email'], $personas[1]['email']] as $email) {
            $usuario = User::query()->where('email', $email)->firstOrFail();
            $this->assertSame(EstadoUsuario::Pendiente->value, $usuario->estado);
            $this->assertNotNull($usuario->rut);
        }
    }

    public function test_crear_solicitud_rechaza_unidad_de_otro_proyecto(): void
    {
        [$proyectoA] = $this->estructura('Proyecto A');
        [, $unidadB] = $this->estructura('Proyecto B');

        $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $proyectoA->getKey(),
            'unidad_principal_id' => $unidadB->getKey(),
            'tipo_solicitud' => 'propietario',
            'personas' => $this->personas(1),
        ])->assertStatus(422);
    }

    public function test_crear_solicitud_valida_rut_y_limite_de_personas(): void
    {
        [$proyecto, $unidad] = $this->estructura();

        $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'tipo_solicitud' => 'propietario',
            'personas' => [[
                'nombre' => 'RUT Inválido',
                'rut' => '12345678-9',
                'email' => 'rut.invalido@example.test',
                'telefono' => '+56911111111',
                'telefono_emergencia' => '+56922222222',
                'nombre_emergencia' => 'Contacto',
            ]],
        ])->assertStatus(422);

        $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'tipo_solicitud' => 'propietario',
            'personas' => $this->personas(7),
        ])->assertStatus(422);
    }

    public function test_no_permite_solicitud_pendiente_duplicada(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $payload = [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'tipo_solicitud' => 'propietario',
            'personas' => $this->personas(1),
        ];

        $this->postJson('/api/solicitudes-residentes', $payload)->assertCreated();
        $this->postJson('/api/solicitudes-residentes', $payload)->assertStatus(409);
    }

    public function test_admin_lista_y_detalle_incluye_instalaciones(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        UnidadAccesoria::query()->create([
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'identificador' => 'B-900',
            'tipo' => 'bodega',
            'asignacion' => 'departamento',
        ]);
        UnidadAccesoria::query()->create([
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'identificador' => 'E-900',
            'tipo' => 'estacionamiento',
            'asignacion' => 'departamento',
        ]);

        $creada = $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'tipo_solicitud' => 'propietario',
            'personas' => $this->personas(1),
        ])->assertCreated()->json();

        $admin = $this->staff($proyecto, Rol::Administrador);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/solicitudes-residentes', $this->headers($proyecto))
            ->assertOk()
            ->assertJsonCount(1, 'solicitudes');

        $this->getJson('/api/admin/solicitudes-residentes/'.$creada['id'], $this->headers($proyecto))
            ->assertOk()
            ->assertJsonCount(2, 'instalaciones')
            ->assertJsonCount(1, 'personas');
    }

    public function test_aprobar_crea_vinculos_membresias_y_cierra_propietario_anterior(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);

        $anterior = User::factory()->create(['rol' => Rol::Propietario->value]);
        $anterior->proyectos()->attach($proyecto, ['rol' => Rol::Propietario->value, 'estado' => 'activo']);
        UnidadUsuario::query()->create([
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $anterior->getKey(),
            'tipo_vinculo' => 'propietario',
            'fecha_inicio' => '2025-01-01',
            'es_actual' => true,
        ]);

        $personas = $this->personas();
        $creada = $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'tipo_solicitud' => 'propietario',
            'personas' => $personas,
        ])->assertCreated()->json();

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/solicitudes-residentes/'.$creada['id'].'/aprobar', [], $this->headers($proyecto))
            ->assertOk()
            ->assertJsonPath('estado', 'aprobada');

        $principal = User::query()->where('email', $personas[0]['email'])->firstOrFail();
        $acompanante = User::query()->where('email', $personas[1]['email'])->firstOrFail();

        $this->assertSame(EstadoUsuario::Activo->value, $principal->estado);
        $this->assertDatabaseHas('proyecto_usuario', [
            'proyecto_id' => $proyecto->getKey(),
            'usuario_id' => $principal->getKey(),
            'rol' => Rol::Propietario->value,
            'estado' => 'activo',
        ]);
        $this->assertDatabaseHas('proyecto_usuario', [
            'proyecto_id' => $proyecto->getKey(),
            'usuario_id' => $acompanante->getKey(),
            'rol' => Rol::Residente->value,
            'estado' => 'activo',
        ]);
        $this->assertDatabaseHas('unidad_usuario', [
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $principal->getKey(),
            'tipo_vinculo' => 'propietario',
            'es_actual' => true,
        ]);
        $this->assertDatabaseHas('unidad_usuario', [
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $acompanante->getKey(),
            'tipo_vinculo' => 'familiar',
            'es_actual' => true,
        ]);
        $this->assertDatabaseHas('unidad_usuario', [
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $anterior->getKey(),
            'tipo_vinculo' => 'propietario',
            'es_actual' => false,
        ]);
    }

    public function test_aprobar_arrendatario_sincroniza_ocupante_legacy(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);
        $personas = $this->personas(1);

        $creada = $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'tipo_solicitud' => 'arrendatario',
            'personas' => $personas,
        ])->assertCreated()->json();

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/solicitudes-residentes/'.$creada['id'].'/aprobar', [], $this->headers($proyecto))
            ->assertOk();

        $usuario = User::query()->where('email', $personas[0]['email'])->firstOrFail();

        $this->assertDatabaseHas('unidad_usuario', [
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $usuario->getKey(),
            'tipo_vinculo' => 'arrendatario',
            'es_actual' => true,
        ]);
        $this->assertDatabaseHas('unidades_principales', [
            'id' => $unidad->getKey(),
            'residente_id' => $usuario->getKey(),
        ]);
    }

    public function test_no_se_puede_decidir_dos_veces(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);

        $creada = $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'tipo_solicitud' => 'propietario',
            'personas' => $this->personas(1),
        ])->assertCreated()->json();

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/solicitudes-residentes/'.$creada['id'].'/aprobar', [], $this->headers($proyecto))
            ->assertOk();
        $this->postJson('/api/admin/solicitudes-residentes/'.$creada['id'].'/aprobar', [], $this->headers($proyecto))
            ->assertStatus(409);
        $this->postJson('/api/admin/solicitudes-residentes/'.$creada['id'].'/rechazar', ['motivo' => 'Duplicada'], $this->headers($proyecto))
            ->assertStatus(409);
    }

    public function test_rechazar_exige_motivo_y_marca_usuarios_rechazados(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);
        $personas = $this->personas(1);

        $creada = $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'tipo_solicitud' => 'propietario',
            'personas' => $personas,
        ])->assertCreated()->json();

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/solicitudes-residentes/'.$creada['id'].'/rechazar', [], $this->headers($proyecto))
            ->assertStatus(422);

        $this->postJson('/api/admin/solicitudes-residentes/'.$creada['id'].'/rechazar', [
            'motivo' => 'Los datos no coinciden con la unidad.',
        ], $this->headers($proyecto))
            ->assertOk()
            ->assertJsonPath('estado', 'rechazada')
            ->assertJsonPath('motivo_rechazo', 'Los datos no coinciden con la unidad.');

        $usuario = User::query()->where('email', $personas[0]['email'])->firstOrFail();
        $this->assertSame(EstadoUsuario::Rechazado->value, $usuario->estado);
    }

    public function test_historial_solo_incluye_solicitudes_decididas(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $admin = $this->staff($proyecto, Rol::Administrador);

        $primera = $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'tipo_solicitud' => 'propietario',
            'personas' => $this->personas(1),
        ])->assertCreated()->json();

        $segunda = $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => UnidadPrincipal::factory()->for($unidad->agrupacion)->create(['identificador' => 'U-2'])->getKey(),
            'tipo_solicitud' => 'arrendatario',
            'personas' => $this->personas(1),
        ])->assertCreated()->json();

        $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => UnidadPrincipal::factory()->for($unidad->agrupacion)->create(['identificador' => 'U-3'])->getKey(),
            'tipo_solicitud' => 'propietario',
            'personas' => $this->personas(1),
        ])->assertCreated();

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/solicitudes-residentes/'.$primera['id'].'/aprobar', [], $this->headers($proyecto))
            ->assertOk();
        $this->postJson('/api/admin/solicitudes-residentes/'.$segunda['id'].'/rechazar', ['motivo' => 'Documentación incompleta'], $this->headers($proyecto))
            ->assertOk();

        $this->getJson('/api/admin/solicitudes-residentes/historial', $this->headers($proyecto))
            ->assertOk()
            ->assertJsonCount(2, 'solicitudes')
            ->assertJsonPath('solicitudes.0.estado', 'rechazada')
            ->assertJsonPath('solicitudes.1.estado', 'aprobada');
    }

    public function test_permisos_por_rol_y_proyecto(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        [$otroProyecto, $otraUnidad] = $this->estructura('Otro Proyecto');
        $admin = $this->staff($proyecto, Rol::Administrador);
        $conserje = $this->staff($proyecto, Rol::Conserje);
        $residente = $this->staff($proyecto, Rol::Residente);

        $creada = $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'tipo_solicitud' => 'propietario',
            'personas' => $this->personas(1),
        ])->assertCreated()->json();

        $ajena = $this->postJson('/api/solicitudes-residentes', [
            'proyecto_id' => $otroProyecto->getKey(),
            'unidad_principal_id' => $otraUnidad->getKey(),
            'tipo_solicitud' => 'propietario',
            'personas' => $this->personas(1),
        ])->assertCreated()->json();

        Sanctum::actingAs($residente);
        $this->getJson('/api/admin/solicitudes-residentes', $this->headers($proyecto))->assertForbidden();

        Sanctum::actingAs($conserje);
        $this->getJson('/api/admin/solicitudes-residentes', $this->headers($proyecto))->assertOk();

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/solicitudes-residentes/'.$ajena['id'], $this->headers($proyecto))->assertNotFound();
        $this->getJson('/api/admin/solicitudes-residentes/'.$creada['id'], $this->headers($proyecto))->assertOk();
    }

    public function test_login_bloquea_usuarios_pendientes_y_rechazados(): void
    {
        $pendiente = User::factory()->create([
            'email' => 'pendiente@example.test',
            'password' => 'password-seguro',
            'estado' => EstadoUsuario::Pendiente->value,
        ]);
        $rechazado = User::factory()->create([
            'email' => 'rechazado@example.test',
            'password' => 'password-seguro',
            'estado' => EstadoUsuario::Rechazado->value,
        ]);

        $this->postJson('/api/login', ['email' => $pendiente->email, 'password' => 'password-seguro'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tu solicitud está pendiente de aprobación.');

        $this->postJson('/api/login', ['email' => $rechazado->email, 'password' => 'password-seguro'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tu solicitud de registro fue rechazada.');
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

    private function staff(Proyecto $proyecto, Rol $rol): User
    {
        $usuario = $rol === Rol::Administrador
            ? User::factory()->administrador()->create()
            : User::factory()->create(['rol' => $rol->value]);

        $usuario->proyectos()->attach($proyecto, ['rol' => $rol->value, 'estado' => 'activo']);

        return $usuario;
    }

    /** @return array<string, string> */
    private function headers(Proyecto $proyecto): array
    {
        return ['X-Proyecto-Id' => (string) $proyecto->getKey()];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function personas(int $cantidad = 2): array
    {
        $base = [
            ['Ana Demo', '12345678-5', 'ana.solicitud@example.test', '+56970000001'],
            ['Luis Demo', '23456789-6', 'luis.solicitud@example.test', '+56970000002'],
            ['Carla Demo', '34567890-5', 'carla.solicitud@example.test', '+56970000003'],
            ['Diego Demo', '45678901-3', 'diego.solicitud@example.test', '+56970000004'],
            ['Elena Demo', '56789012-0', 'elena.solicitud@example.test', '+56970000005'],
            ['Felipe Demo', '10000013-K', 'felipe.solicitud@example.test', '+56970000006'],
            ['Gabriela Demo', '11111111-1', 'gabriela.solicitud@example.test', '+56970000007'],
        ];

        $personas = [];

        foreach (array_slice($base, 0, $cantidad) as $indice => [$nombre, $rut, $email, $telefono]) {
            $personas[] = [
                'nombre' => $nombre,
                'rut' => $rut,
                'email' => $email,
                'telefono' => $telefono,
                'telefono_emergencia' => '+56971111111',
                'nombre_emergencia' => 'Contacto '.$nombre,
                'es_principal' => $indice === 0,
            ];
        }

        return $personas;
    }
}
