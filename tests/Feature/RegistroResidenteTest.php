<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Mascota;
use App\Models\Proyecto;
use App\Models\ProyectoUsuario;
use App\Models\SolicitudResidente;
use App\Models\UnidadPrincipal;
use App\Models\UnidadUsuario;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegistroResidenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_crea_cuenta_solicitud_y_mascotas(): void
    {
        [$proyecto, $unidad] = $this->estructura();

        $respuesta = $this->postJson('/api/registro/solicitud', $this->payload($proyecto, $unidad))
            ->assertCreated()
            ->assertJsonPath('estado', 'pendiente');

        $solicitudId = $respuesta->json('id');

        $this->assertDatabaseHas('usuarios', [
            'email' => 'principal@example.test',
            'rut' => '12345678-5',
            'rol' => Rol::Propietario->value,
        ]);

        $this->assertDatabaseHas('solicitudes_residente', [
            'id' => $solicitudId,
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'estado' => 'pendiente',
            'origen' => 'autoregistro',
        ]);

        $this->assertDatabaseCount('solicitud_residente_personas', 2);
        $this->assertDatabaseHas('solicitud_residente_personas', [
            'solicitud_id' => $solicitudId,
            'email' => 'principal@example.test',
            'es_principal' => true,
            'tipo_vinculo' => 'propietario',
        ]);
        $this->assertDatabaseHas('solicitud_residente_personas', [
            'solicitud_id' => $solicitudId,
            'email' => 'acompanante@example.test',
            'es_principal' => false,
            'tipo_vinculo' => 'residente',
        ]);

        $this->assertDatabaseHas('solicitud_residente_mascotas', [
            'solicitud_id' => $solicitudId,
            'nombre' => 'Firulais',
            'especie' => 'canino',
        ]);

        // El residente puede iniciar sesión de inmediato pero sigue pendiente.
        $this->postJson('/api/login', [
            'email' => 'principal@example.test',
            'password' => 'password123',
        ])->assertOk();

        $usuario = User::query()->where('email', 'principal@example.test')->firstOrFail();
        Sanctum::actingAs($usuario);

        $this->getJson('/api/mis-proyectos')->assertOk()->assertJsonCount(0, 'proyectos');
        $this->getJson('/api/mis-solicitudes-residentes')
            ->assertOk()
            ->assertJsonPath('solicitudes.0.estado', 'pendiente')
            ->assertJsonPath('solicitudes.0.mascotas.0.nombre', 'Firulais');
    }

    public function test_registro_sin_principal_falla(): void
    {
        [$proyecto, $unidad] = $this->estructura();

        $payload = $this->payload($proyecto, $unidad);
        $payload['personas'][0]['es_principal'] = false;
        $payload['personas'][1]['es_principal'] = false;

        $this->postJson('/api/registro/solicitud', $payload)->assertStatus(422);
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_email_duplicado_es_rechazado(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        User::factory()->create(['email' => 'principal@example.test']);

        $this->postJson('/api/registro/solicitud', $this->payload($proyecto, $unidad))
            ->assertStatus(409);
    }

    public function test_rut_invalido_es_rechazado(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $payload = $this->payload($proyecto, $unidad);
        $payload['personas'][0]['rut'] = '12345678-9';

        $this->postJson('/api/registro/solicitud', $payload)->assertStatus(422);
    }

    public function test_no_se_permiten_mas_de_seis_personas(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $payload = $this->payload($proyecto, $unidad);

        for ($i = 0; $i < 5; $i++) {
            $payload['personas'][] = [
                'nombre' => 'Acompanante '.$i,
                'rut' => '1500000'.(2 + $i).'-5',
                'email' => 'acompanante'.$i.'@example.test',
                'telefono' => '+5697000000'.$i,
                'telefono_emergencia' => '+5697111111'.$i,
                'nombre_emergencia' => 'Contacto '.$i,
                'tipo_vinculo' => 'residente',
                'es_principal' => false,
            ];
        }

        $this->postJson('/api/registro/solicitud', $payload)->assertStatus(422);
    }

    public function test_unidad_de_otro_proyecto_es_rechazada(): void
    {
        [$proyecto] = $this->estructura();
        [, $unidadAjena] = $this->estructura('Otro');

        $this->postJson('/api/registro/solicitud', $this->payload($proyecto, $unidadAjena))
            ->assertStatus(422);
    }

    public function test_aprobar_transfiere_mascotas_a_la_unidad(): void
    {
        [$proyecto, $unidad] = $this->estructura();
        $respuesta = $this->postJson('/api/registro/solicitud', $this->payload($proyecto, $unidad))->assertCreated();

        $solicitud = SolicitudResidente::query()->findOrFail($respuesta->json('id'));
        $admin = $this->staff($proyecto);

        Sanctum::actingAs($admin);

        $this->postJson(
            '/api/admin/solicitudes-residentes/'.$solicitud->getKey().'/aprobar',
            [],
            ['X-Proyecto-Id' => (string) $proyecto->getKey()],
        )->assertOk()->assertJsonPath('estado', 'aprobada');

        $principal = User::query()->where('email', 'principal@example.test')->firstOrFail();

        $this->assertDatabaseHas('unidad_usuario', [
            'unidad_principal_id' => $unidad->getKey(),
            'usuario_id' => $principal->getKey(),
            'tipo_vinculo' => 'propietario',
            'es_actual' => true,
        ]);
        $this->assertDatabaseHas('mascotas', [
            'unidad_principal_id' => $unidad->getKey(),
            'nombre' => 'Firulais',
        ]);
        $this->assertSame(1, Mascota::query()->count());
    }

    /** @return array{Proyecto, UnidadPrincipal} */
    private function estructura(string $nombre = 'Proyecto'): array
    {
        $proyecto = Proyecto::factory()->create(['nombre' => $nombre, 'estado' => 'completo']);
        $agrupacion = Agrupacion::factory()->for($proyecto)->create(['nombre' => 'Torre '.$nombre]);
        $unidad = UnidadPrincipal::factory()->for($agrupacion)->create(['identificador' => $nombre.'-101']);

        return [$proyecto, $unidad];
    }

    /** @return array<string, mixed> */
    private function payload(Proyecto $proyecto, UnidadPrincipal $unidad): array
    {
        return [
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad->getKey(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'personas' => [
                [
                    'nombre' => 'Principal Demo',
                    'rut' => '12345678-5',
                    'email' => 'principal@example.test',
                    'telefono' => '+56970000001',
                    'telefono_emergencia' => '+56971111111',
                    'nombre_emergencia' => 'Contacto Emergencia',
                    'tipo_vinculo' => 'propietario',
                    'es_principal' => true,
                ],
                [
                    'nombre' => 'Acompanante Demo',
                    'rut' => '15000002-5',
                    'email' => 'acompanante@example.test',
                    'telefono' => '+56970000002',
                    'telefono_emergencia' => '+56971111112',
                    'nombre_emergencia' => 'Contacto Dos',
                    'tipo_vinculo' => 'residente',
                    'es_principal' => false,
                ],
            ],
            'mascotas' => [
                [
                    'nombre' => 'Firulais',
                    'especie' => 'canino',
                    'raza' => 'Mestizo',
                    'microchip' => '123456789012345',
                ],
            ],
        ];
    }

    private function staff(Proyecto $proyecto): User
    {
        $usuario = User::factory()->administrador()->create();
        $usuario->proyectos()->attach($proyecto, ['rol' => Rol::Administrador->value, 'estado' => 'activo']);

        return $usuario;
    }
}
