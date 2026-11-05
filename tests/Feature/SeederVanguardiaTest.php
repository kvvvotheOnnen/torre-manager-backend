<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\UnidadPrincipal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederVanguardiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_crea_vanguardia_center_con_estructura_y_usuarios(): void
    {
        $this->seed();

        $proyecto = Proyecto::query()
            ->where('nombre', 'Vanguardia Center')
            ->firstOrFail();

        $this->assertSame('edificio', $proyecto->tipo_condominio);
        $this->assertSame('Edifico de residentes', $proyecto->descripcion);
        $this->assertSame(['infraestructura'], $proyecto->modulos);
        $this->assertSame('completo', $proyecto->estado);

        $this->assertSame(1, Proyecto::query()->count());
        $this->assertSame(1, $proyecto->agrupaciones()->count());
        $this->assertSame('Edificio', $proyecto->agrupaciones()->value('nombre'));
        $this->assertSame('edificio', $proyecto->agrupaciones()->value('tipo'));
        $this->assertSame(304, $proyecto->unidadesPrincipales()->count());

        $this->assertDatabaseHas('unidades_principales', [
            'identificador' => 'Edificio-801',
        ]);
        $this->assertDatabaseHas('unidades_principales', [
            'identificador' => 'Edificio-2616',
        ]);

        $this->assertDatabaseHas('instalaciones_extra', [
            'proyecto_id' => $proyecto->getKey(),
            'nombre' => 'Quincho',
            'capacidad' => 25,
            'descripcion' => 'Quincho para arriendo en el edificio',
        ]);

        $this->assertSame(
            50,
            $proyecto->unidadesAccesorias()->where('tipo', 'bodega')->count(),
        );
        $this->assertSame(
            100,
            $proyecto->unidadesAccesorias()->where('tipo', 'estacionamiento')->count(),
        );
        $this->assertSame(
            3,
            $proyecto->unidadesAccesorias()
                ->where('tipo', 'bodega')
                ->where('asignacion', 'departamento')
                ->count(),
        );
        $this->assertSame(
            4,
            $proyecto->unidadesAccesorias()
                ->where('tipo', 'estacionamiento')
                ->where('asignacion', 'departamento')
                ->count(),
        );

        $unidad801 = UnidadPrincipal::query()
            ->whereHas(
                'agrupacion',
                fn ($query) => $query->where('proyecto_id', $proyecto->getKey()),
            )
            ->where('identificador', 'Edificio-801')
            ->value('id');
        $unidad804 = UnidadPrincipal::query()
            ->whereHas(
                'agrupacion',
                fn ($query) => $query->where('proyecto_id', $proyecto->getKey()),
            )
            ->where('identificador', 'Edificio-804')
            ->value('id');

        $this->assertDatabaseHas('unidades_accesorias', [
            'proyecto_id' => $proyecto->getKey(),
            'identificador' => 'B-001',
            'tipo' => 'bodega',
            'asignacion' => 'departamento',
            'unidad_principal_id' => $unidad801,
        ]);
        $this->assertDatabaseHas('unidades_accesorias', [
            'proyecto_id' => $proyecto->getKey(),
            'identificador' => 'E-004',
            'tipo' => 'estacionamiento',
            'asignacion' => 'departamento',
            'unidad_principal_id' => $unidad804,
        ]);

        $this->assertSame(4, User::query()->count());
        $this->assertDatabaseHas('usuarios', [
            'email' => 'superadmin@torremanager.test',
            'rol' => 'superadmin',
            'proyecto_id' => null,
        ]);
        $this->assertDatabaseHas('usuarios', [
            'email' => 'admin@torremanager.test',
            'rol' => 'administrador',
            'proyecto_id' => $proyecto->getKey(),
        ]);
        $this->assertDatabaseHas('usuarios', [
            'email' => 'residente@torremanager.test',
            'rol' => 'residente',
            'proyecto_id' => $proyecto->getKey(),
        ]);
        $this->assertDatabaseHas('usuarios', [
            'email' => 'conserje@torremanager.test',
            'rol' => 'conserje',
            'proyecto_id' => $proyecto->getKey(),
        ]);

        $admin = User::query()
            ->where('email', 'admin@torremanager.test')
            ->firstOrFail();
        $this->assertDatabaseHas('administrador_proyecto', [
            'proyecto_id' => $proyecto->getKey(),
            'usuario_id' => $admin->getKey(),
        ]);
    }
}
