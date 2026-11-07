<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigracionModulosGlobalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_elimina_modulos_globales_de_proyectos_existentes(): void
    {
        $proyecto = Proyecto::factory()->create([
            'modulos' => [
                'infraestructura',
                'paqueteria',
                'quincho',
                'gastos_comunes',
                'control_acceso',
            ],
        ]);

        $migracion = require database_path(
            'migrations/2026_11_06_000001_remove_general_modules_from_proyectos.php',
        );
        $migracion->up();

        $this->assertSame(['infraestructura'], $proyecto->fresh()->modulos);
    }
}
