<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\Proyecto;
use Database\Seeders\DocumentoReglamentoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentoReglamentoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_restaura_el_reglamento_demo_con_archivo_y_texto(): void
    {
        Storage::fake('local');

        $this->seed();

        $proyecto = Proyecto::query()
            ->where('nombre', 'Vanguardia Center')
            ->firstOrFail();

        $documento = Documento::query()
            ->where('proyecto_id', $proyecto->getKey())
            ->firstOrFail();

        $this->assertSame(1, Documento::query()->count());
        $this->assertSame('Reglamento Vanguardia Center (ejemplo).pdf', $documento->nombre_original);
        $this->assertSame(2, (int) $documento->paginas);
        $this->assertGreaterThan(0, (int) $documento->tamano_tokens);
        $this->assertStringContainsString('Quincho', (string) $documento->texto);
        $this->assertStringContainsString('[Página 1]', (string) $documento->texto);
        Storage::disk('local')->assertExists((string) $documento->ruta);
    }

    public function test_seed_es_idempotente_y_no_duplica_el_documento(): void
    {
        Storage::fake('local');

        $this->seed();

        $this->seed(DocumentoReglamentoSeeder::class);
        $this->seed(DocumentoReglamentoSeeder::class);

        $this->assertSame(1, Documento::query()->count());
    }
}
