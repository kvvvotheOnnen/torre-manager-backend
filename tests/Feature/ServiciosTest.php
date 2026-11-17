<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\TrabajadorServicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ServiciosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_lista_los_trabajadores_de_su_proyecto(): void
    {
        $proyecto = Proyecto::factory()->create();
        $administrador = $this->usuarioConRol('administrador', $proyecto);

        TrabajadorServicio::query()->create([
            'proyecto_id' => $proyecto->getKey(),
            'nombre' => 'Carlos Ramírez',
            'telefono' => '+56 9 1111 1111',
            'tipo_servicio' => 'electricista',
        ]);

        Sanctum::actingAs($administrador);

        $response = $this->getJson('/api/servicios', ['X-Proyecto-Id' => (string) $proyecto->getKey()]);

        $response->assertOk()
            ->assertJsonCount(1, 'trabajadores')
            ->assertJsonPath('trabajadores.0.nombre', 'Carlos Ramírez')
            ->assertJsonPath('trabajadores.0.tipo_etiqueta', 'Electricista')
            ->assertJsonPath('trabajadores.0.foto_url', null);
    }

    public function test_filtra_por_tipo_y_por_busqueda(): void
    {
        $proyecto = Proyecto::factory()->create();
        $administrador = $this->usuarioConRol('administrador', $proyecto);

        $this->crearTrabajador($proyecto, 'Carlos Ramírez', 'Electricidad Ramírez', 'electricista');
        $this->crearTrabajador($proyecto, 'Luis Fuentes', 'Gasfitería Fuentes', 'gasfiter');

        Sanctum::actingAs($administrador);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->getJson('/api/servicios?tipo=gasfiter', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'trabajadores')
            ->assertJsonPath('trabajadores.0.nombre', 'Luis Fuentes');

        $this->getJson('/api/servicios?buscar=Ramírez', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'trabajadores')
            ->assertJsonPath('trabajadores.0.nombre', 'Carlos Ramírez');

        // La búsqueda no distingue mayúsculas ni tildes de la consulta.
        $this->getJson('/api/servicios?buscar=ramírez', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'trabajadores')
            ->assertJsonPath('trabajadores.0.nombre', 'Carlos Ramírez');

        $this->getJson('/api/servicios?buscar=ramirez', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'trabajadores')
            ->assertJsonPath('trabajadores.0.nombre', 'Carlos Ramírez');

        // También encuentra por el tipo de servicio, no solo por nombre.
        $this->getJson('/api/servicios?buscar=GASFITER', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'trabajadores')
            ->assertJsonPath('trabajadores.0.nombre', 'Luis Fuentes');

        $this->getJson('/api/servicios?buscar=electricista', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'trabajadores')
            ->assertJsonPath('trabajadores.0.nombre', 'Carlos Ramírez');
    }

    public function test_el_administrador_crea_un_trabajador_sin_foto(): void
    {
        $proyecto = Proyecto::factory()->create();
        $administrador = $this->usuarioConRol('administrador', $proyecto);
        Sanctum::actingAs($administrador);

        $response = $this->postJson('/api/servicios', [
            'nombre' => 'María Díaz',
            'nombre_empresa' => 'Cerrajería Díaz',
            'telefono' => '+56 9 4444 4444',
            'tipo_servicio' => 'cerrajero',
            'descripcion' => 'Apertura de puertas y cambio de cerraduras.',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()]);

        $response->assertCreated()
            ->assertJsonPath('trabajador.nombre', 'María Díaz')
            ->assertJsonPath('trabajador.tipo_servicio', 'cerrajero')
            ->assertJsonPath('trabajador.foto_url', null);

        $this->assertDatabaseHas('trabajadores_servicio', [
            'proyecto_id' => $proyecto->getKey(),
            'nombre' => 'María Díaz',
            'tipo_servicio' => 'cerrajero',
        ]);
    }

    public function test_guarda_la_foto_del_trabajador(): void
    {
        Storage::fake('public');
        $proyecto = Proyecto::factory()->create();
        $administrador = $this->usuarioConRol('administrador', $proyecto);
        Sanctum::actingAs($administrador);

        $response = $this->post('/api/servicios', [
            'nombre' => 'Andrés Vera',
            'telefono' => '+56 9 5555 5555',
            'tipo_servicio' => 'tecnico_informatico',
            'foto' => UploadedFile::fake()->create('andres.jpg', 120, 'image/jpeg'),
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()]);

        $response->assertCreated();

        $ruta = TrabajadorServicio::query()->firstOrFail()->foto_path;

        $this->assertNotNull($ruta);
        Storage::disk('public')->assertExists($ruta);
    }

    public function test_rechaza_un_tipo_de_servicio_invalido(): void
    {
        $proyecto = Proyecto::factory()->create();
        $administrador = $this->usuarioConRol('administrador', $proyecto);
        Sanctum::actingAs($administrador);

        $this->postJson('/api/servicios', [
            'nombre' => 'Sin tipo',
            'telefono' => '+56 9 0000 0000',
            'tipo_servicio' => 'astronauta',
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()])
            ->assertStatus(422)
            ->assertJsonValidationErrors('tipo_servicio');
    }

    public function test_el_administrador_actualiza_y_elimina(): void
    {
        $proyecto = Proyecto::factory()->create();
        $administrador = $this->usuarioConRol('administrador', $proyecto);
        $trabajador = $this->crearTrabajador($proyecto, 'Pedro Soto', null, 'maestro');
        Sanctum::actingAs($administrador);
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        $this->putJson('/api/servicios/'.$trabajador->getKey(), [
            'nombre' => 'Pedro Soto',
            'nombre_empresa' => 'Obras Soto',
            'telefono' => '+56 9 3333 3333',
            'tipo_servicio' => 'maestro',
            'descripcion' => 'Pintura y terminaciones.',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('trabajador.nombre_empresa', 'Obras Soto');

        $this->deleteJson('/api/servicios/'.$trabajador->getKey(), [], $headers)
            ->assertNoContent();

        $this->assertDatabaseMissing('trabajadores_servicio', ['id' => $trabajador->getKey()]);
    }

    public function test_conserje_y_residente_solo_pueden_ver(): void
    {
        $proyecto = Proyecto::factory()->create();
        $this->crearTrabajador($proyecto, 'Carlos Ramírez', null, 'electricista');
        $headers = ['X-Proyecto-Id' => (string) $proyecto->getKey()];

        foreach (['conserje', 'residente'] as $rol) {
            $usuario = $this->usuarioConRol($rol, $proyecto);
            Sanctum::actingAs($usuario);

            $this->getJson('/api/servicios', $headers)->assertOk();

            $this->postJson('/api/servicios', [
                'nombre' => 'Intruso',
                'telefono' => '+56 9 0000 0000',
                'tipo_servicio' => 'otro',
            ], $headers)->assertForbidden();
        }
    }

    public function test_no_ve_ni_modifica_trabajadores_de_otro_proyecto(): void
    {
        $propio = Proyecto::factory()->create();
        $ajeno = Proyecto::factory()->create();
        $administrador = $this->usuarioConRol('administrador', $propio);
        $trabajadorAjeno = $this->crearTrabajador($ajeno, 'Ajeno', null, 'otro');
        Sanctum::actingAs($administrador);
        $headers = ['X-Proyecto-Id' => (string) $propio->getKey()];

        $this->getJson('/api/servicios', $headers)
            ->assertOk()
            ->assertJsonCount(0, 'trabajadores');

        $this->putJson('/api/servicios/'.$trabajadorAjeno->getKey(), [
            'nombre' => 'Robado',
            'telefono' => '+56 9 0000 0000',
            'tipo_servicio' => 'otro',
        ], $headers)->assertNotFound();

        $this->deleteJson('/api/servicios/'.$trabajadorAjeno->getKey(), [], $headers)
            ->assertNotFound();
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

    private function crearTrabajador(Proyecto $proyecto, string $nombre, ?string $empresa, string $tipo): TrabajadorServicio
    {
        return TrabajadorServicio::query()->create([
            'proyecto_id' => $proyecto->getKey(),
            'nombre' => $nombre,
            'nombre_empresa' => $empresa,
            'telefono' => '+56 9 0000 0000',
            'tipo_servicio' => $tipo,
        ]);
    }
}
