<?php

namespace Tests\Feature;

use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DatosPersonalesUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_devuelve_rut_y_telefonos(): void
    {
        $usuario = User::factory()->create([
            'email' => 'login.demo@example.test',
            'password' => 'password-seguro',
            'rol' => Rol::Residente->value,
            'rut' => '12345678-5',
            'telefono' => '+56911111111',
            'telefono_emergencia' => '+56922222222',
            'nombre_emergencia' => 'Contacto Demo',
        ]);

        $this->postJson('/api/login', [
            'email' => $usuario->email,
            'password' => 'password-seguro',
        ])
            ->assertOk()
            ->assertJsonPath('user.rut', '12345678-5')
            ->assertJsonPath('user.telefono', '+56911111111')
            ->assertJsonPath('user.telefono_emergencia', '+56922222222')
            ->assertJsonPath('user.nombre_emergencia', 'Contacto Demo');
    }

    public function test_endpoint_user_expone_datos_personales(): void
    {
        $usuario = User::factory()->create([
            'rut' => '23456789-6',
            'telefono' => '+56933333333',
            'telefono_emergencia' => '+56944444444',
            'nombre_emergencia' => 'Contacto Usuario',
        ]);

        Sanctum::actingAs($usuario);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('rut', '23456789-6')
            ->assertJsonPath('telefono', '+56933333333')
            ->assertJsonPath('telefono_emergencia', '+56944444444')
            ->assertJsonPath('nombre_emergencia', 'Contacto Usuario');
    }
}
