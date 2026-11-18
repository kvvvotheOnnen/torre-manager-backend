<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Proyecto;
use App\Models\UnidadPrincipal;
use App\Models\UnidadUsuario;
use App\Models\User;
use Features\Dashboard\Domain\Contracts\ResolutorAmbito;
use Features\Dashboard\Domain\Entities\ReferenciaRecurso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolutorAmbitoTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_residente_alcanza_su_unidad_pero_no_la_del_vecino(): void
    {
        $proyecto = Proyecto::factory()->create();
        [$propia, $vecina] = $this->dosUnidades($proyecto);
        $residente = $this->usuarioEn($proyecto, 'residente');

        UnidadUsuario::query()->create([
            'unidad_principal_id' => $propia->getKey(),
            'usuario_id' => $residente->getKey(),
            'tipo_vinculo' => 'arrendatario',
            'fecha_inicio' => now()->toDateString(),
            'es_actual' => true,
        ]);

        $resolutor = $this->app->make(ResolutorAmbito::class);

        $this->assertTrue($resolutor->puedeAccederA(
            (string) $residente->getKey(),
            (string) $proyecto->getKey(),
            new ReferenciaRecurso('unidad', (string) $propia->getKey()),
        ));

        $this->assertFalse($resolutor->puedeAccederA(
            (string) $residente->getKey(),
            (string) $proyecto->getKey(),
            new ReferenciaRecurso('unidad', (string) $vecina->getKey()),
        ));
    }

    public function test_un_administrador_alcanza_cualquier_unidad_del_proyecto(): void
    {
        $proyecto = Proyecto::factory()->create();
        [$primera, $segunda] = $this->dosUnidades($proyecto);
        $admin = $this->usuarioEn($proyecto, 'administrador');

        $resolutor = $this->app->make(ResolutorAmbito::class);

        foreach ([$primera, $segunda] as $unidad) {
            $this->assertTrue($resolutor->puedeAccederA(
                (string) $admin->getKey(),
                (string) $proyecto->getKey(),
                new ReferenciaRecurso('unidad', (string) $unidad->getKey()),
            ));
        }
    }

    public function test_niega_el_acceso_a_usuario_de_otro_proyecto(): void
    {
        $proyecto = Proyecto::factory()->create();
        [$unidad] = $this->dosUnidades($proyecto);

        $otroProyecto = Proyecto::factory()->create();
        $ajeno = $this->usuarioEn($otroProyecto, 'administrador');

        $resolutor = $this->app->make(ResolutorAmbito::class);

        $this->assertFalse($resolutor->puedeAccederA(
            (string) $ajeno->getKey(),
            (string) $proyecto->getKey(),
            new ReferenciaRecurso('unidad', (string) $unidad->getKey()),
        ));
    }

    public function test_niega_el_acceso_a_un_usuario_sin_vinculo_ni_membresia(): void
    {
        $proyecto = Proyecto::factory()->create();
        [$unidad] = $this->dosUnidades($proyecto);
        $sinVinculo = User::factory()->create(['rol' => 'residente']);

        $resolutor = $this->app->make(ResolutorAmbito::class);

        $this->assertFalse($resolutor->puedeAccederA(
            (string) $sinVinculo->getKey(),
            (string) $proyecto->getKey(),
            new ReferenciaRecurso('unidad', (string) $unidad->getKey()),
        ));
    }

    public function test_el_ambito_propio_solo_concede_al_dueno_salvo_roles_de_proyecto_completo(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = $this->usuarioEn($proyecto, 'residente');
        $vecino = $this->usuarioEn($proyecto, 'residente');
        $admin = $this->usuarioEn($proyecto, 'administrador');

        $resolutor = $this->app->make(ResolutorAmbito::class);

        $this->assertTrue($resolutor->puedeAccederA(
            (string) $residente->getKey(),
            (string) $proyecto->getKey(),
            new ReferenciaRecurso('propio', (string) $residente->getKey()),
        ));

        $this->assertFalse($resolutor->puedeAccederA(
            (string) $residente->getKey(),
            (string) $proyecto->getKey(),
            new ReferenciaRecurso('propio', (string) $vecino->getKey()),
        ));

        $this->assertTrue($resolutor->puedeAccederA(
            (string) $admin->getKey(),
            (string) $proyecto->getKey(),
            new ReferenciaRecurso('propio', (string) $vecino->getKey()),
        ));
    }

    /** @return array{0: UnidadPrincipal, 1: UnidadPrincipal} */
    private function dosUnidades(Proyecto $proyecto): array
    {
        $agrupacion = Agrupacion::factory()->create([
            'proyecto_id' => $proyecto->getKey(),
            'nombre' => 'Edificio',
        ]);

        return [
            UnidadPrincipal::factory()->create([
                'agrupacion_id' => $agrupacion->getKey(),
                'identificador' => '101',
            ]),
            UnidadPrincipal::factory()->create([
                'agrupacion_id' => $agrupacion->getKey(),
                'identificador' => '102',
            ]),
        ];
    }

    private function usuarioEn(Proyecto $proyecto, string $rol): User
    {
        $usuario = User::factory()->create(['rol' => $rol, 'estado' => 'activo']);
        $usuario->proyectos()->attach($proyecto, ['rol' => $rol, 'estado' => 'activo']);

        return $usuario;
    }
}
