<?php

namespace Tests\Feature;

use App\Models\Paquete;
use Features\Archivos\Data\AlmacenEnDisco;
use Features\Archivos\Domain\Contracts\AlmacenArchivos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ArchivosTest extends TestCase
{
    use RefreshDatabase;

    private AlmacenArchivos $almacen;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->almacen = new AlmacenEnDisco('public');
    }

    public function test_guarda_el_archivo_en_la_carpeta_con_extension(): void
    {
        $ruta = $this->almacen->guardar('contenido', 'foto.JPG', 'servicios/1');

        $this->assertStringStartsWith('servicios/1/', $ruta);
        $this->assertStringEndsWith('.jpg', $ruta);
        Storage::disk('public')->assertExists($ruta);
    }

    public function test_reemplazar_elimina_el_archivo_anterior(): void
    {
        $anterior = $this->almacen->guardar('viejo', 'foto.png', 'paquetes');
        $nueva = $this->almacen->reemplazar($anterior, 'nuevo', 'foto.png', 'paquetes');

        Storage::disk('public')->assertMissing($anterior);
        Storage::disk('public')->assertExists($nueva);
    }

    public function test_url_y_existe_toleran_rutas_vacias(): void
    {
        $this->assertNull($this->almacen->url(null));
        $this->assertNull($this->almacen->url('   '));
        $this->assertFalse($this->almacen->existe(null));

        $this->almacen->eliminar(null);

        $this->assertFalse($this->almacen->existe('paquetes/no-existe.jpg'));
    }

    public function test_el_comando_borra_huerfanos_y_conserva_referenciados(): void
    {
        Storage::disk('public')->put('paquetes/en-uso.jpg', 'x');
        Storage::disk('public')->put('paquetes/huerfano.jpg', 'x');
        Storage::disk('public')->put('servicios/huerfano.jpg', 'x');

        Paquete::factory()->create(['foto_path' => 'paquetes/en-uso.jpg']);

        $this->artisan('archivos:limpiar-huerfanos')->assertSuccessful();

        Storage::disk('public')->assertExists('paquetes/en-uso.jpg');
        Storage::disk('public')->assertMissing('paquetes/huerfano.jpg');
        Storage::disk('public')->assertMissing('servicios/huerfano.jpg');
    }

    public function test_el_comando_no_borra_nada_con_dry_run(): void
    {
        Storage::disk('public')->put('paquetes/huerfano.jpg', 'x');

        $this->artisan('archivos:limpiar-huerfanos', ['--dry-run' => true])->assertSuccessful();

        Storage::disk('public')->assertExists('paquetes/huerfano.jpg');
    }
}
