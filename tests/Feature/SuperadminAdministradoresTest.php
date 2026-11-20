<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperadminAdministradoresTest extends TestCase
{
    use RefreshDatabase;

    private function autenticarSuperadmin(): void
    {
        Sanctum::actingAs(User::factory()->superadmin()->create());
    }

    public function test_crea_administrador_sin_proyectos(): void
    {
        $this->autenticarSuperadmin();

        $this->postJson('/api/superadmin/administradores', [
            'nombre' => 'Admin Sin Proyecto',
            'email' => 'sin-proyecto@example.test',
            'password' => 'password123',
        ])->assertCreated()
            ->assertJsonPath('rol', 'administrador')
            ->assertJsonPath('proyectos', []);

        $this->assertDatabaseHas('usuarios', [
            'email' => 'sin-proyecto@example.test',
            'rol' => 'administrador',
        ]);
    }

    public function test_crea_administrador_anexado_a_proyectos(): void
    {
        $this->autenticarSuperadmin();

        $proyectoA = Proyecto::factory()->create(['nombre' => 'Proyecto A']);
        $proyectoB = Proyecto::factory()->create(['nombre' => 'Proyecto B']);

        $response = $this->postJson('/api/superadmin/administradores', [
            'nombre' => 'Admin Multi Proyecto',
            'email' => 'multi@example.test',
            'password' => 'password123',
            'proyecto_ids' => [$proyectoA->getKey(), $proyectoB->getKey()],
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'proyectos');

        $admin = User::query()->where('email', 'multi@example.test')->firstOrFail();

        $this->assertDatabaseHas('proyecto_usuario', [
            'proyecto_id' => $proyectoA->getKey(),
            'usuario_id' => $admin->getKey(),
            'rol' => 'administrador',
            'estado' => 'activo',
        ]);
        $this->assertDatabaseHas('proyecto_usuario', [
            'proyecto_id' => $proyectoB->getKey(),
            'usuario_id' => $admin->getKey(),
            'rol' => 'administrador',
            'estado' => 'activo',
        ]);
    }

    public function test_rechaza_proyecto_inexistente_y_no_crea_al_usuario(): void
    {
        $this->autenticarSuperadmin();

        $this->postJson('/api/superadmin/administradores', [
            'nombre' => 'Admin Inválido',
            'email' => 'invalido@example.test',
            'password' => 'password123',
            'proyecto_ids' => [999999],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('proyecto_ids.0');

        $this->assertDatabaseMissing('usuarios', ['email' => 'invalido@example.test']);
    }

    public function test_rechaza_correo_duplicado(): void
    {
        $this->autenticarSuperadmin();

        User::factory()->administrador()->create(['email' => 'duplicado@example.test']);

        $this->postJson('/api/superadmin/administradores', [
            'nombre' => 'Duplicado',
            'email' => 'duplicado@example.test',
            'password' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_listado_incluye_los_proyectos_de_cada_administrador(): void
    {
        $this->autenticarSuperadmin();

        $proyecto = Proyecto::factory()->create(['nombre' => 'Proyecto Visible']);
        $admin = User::factory()->administrador()->create([
            'nombre' => 'Admin Con Proyecto',
            'email' => 'con-proyecto@example.test',
        ]);
        $admin->proyectos()->attach($proyecto, [
            'rol' => 'administrador',
            'estado' => 'activo',
        ]);

        $response = $this->getJson('/api/superadmin/administradores')->assertOk();

        $administrador = collect($response->json('administradores'))
            ->firstWhere('email', 'con-proyecto@example.test');

        $this->assertNotNull($administrador);
        $this->assertSame(
            [['id' => (string) $proyecto->getKey(), 'nombre' => 'Proyecto Visible']],
            $administrador['proyectos'],
        );
    }
}
