<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\User;
use Features\Dashboard\Domain\Contracts\AutorizadorCapacidades;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CapacidadesTest extends TestCase
{
    use RefreshDatabase;

    private Proyecto $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proyecto = Proyecto::factory()->create();

        Route::middleware(['auth:sanctum', 'puede:control_acceso.registrar'])
            ->get('/api/_prueba/capacidad', fn () => response()->json(['ok' => true]));
    }

    public function test_el_administrador_pasa_el_middleware_de_capacidad(): void
    {
        Sanctum::actingAs($this->usuarioEn('administrador'));

        $this->getJson('/api/_prueba/capacidad', $this->cabecera())->assertOk();
    }

    public function test_un_rol_sin_la_capacidad_recibe_403(): void
    {
        Sanctum::actingAs($this->usuarioEn('residente'));

        $this->getJson('/api/_prueba/capacidad', $this->cabecera())
            ->assertForbidden()
            ->assertJsonPath('message', 'No tienes permisos para realizar esta acción.');
    }

    public function test_sin_proyecto_activo_responde_404(): void
    {
        Sanctum::actingAs($this->usuarioEn('administrador'));

        $this->getJson('/api/_prueba/capacidad')->assertNotFound();
    }

    public function test_una_capacidad_nueva_solo_requiere_configuracion(): void
    {
        config(['capacidades.roles.residente' => ['modulo_futuro.accion']]);

        $residente = $this->usuarioEn('residente');
        $autorizador = $this->app->make(AutorizadorCapacidades::class);

        $this->assertTrue($autorizador->puede(
            (string) $residente->getKey(),
            (string) $this->proyecto->getKey(),
            'modulo_futuro.accion',
        ));

        $this->assertFalse($autorizador->puede(
            (string) $residente->getKey(),
            (string) $this->proyecto->getKey(),
            'modulo_futuro.otra_accion',
        ));
    }

    /** @return array<string, string> */
    private function cabecera(): array
    {
        return ['X-Proyecto-Id' => (string) $this->proyecto->getKey()];
    }

    private function usuarioEn(string $rol): User
    {
        $usuario = User::factory()->create(['rol' => $rol]);
        $usuario->proyectos()->attach($this->proyecto, ['rol' => $rol, 'estado' => 'activo']);

        return $usuario;
    }
}
