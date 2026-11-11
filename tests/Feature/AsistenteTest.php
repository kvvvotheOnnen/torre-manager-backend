<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\Proyecto;
use App\Models\User;
use Features\Asistente\Domain\Entities\PaginasDocumento;
use Features\Asistente\Domain\Repositories\ExtractorPdfInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AsistenteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->app->instance(ExtractorPdfInterface::class, new class implements ExtractorPdfInterface
        {
            public function extraer(string $rutaFisica): PaginasDocumento
            {
                return new PaginasDocumento([
                    1 => 'El quincho está en el piso 7 y tiene capacidad para 25 personas.',
                    2 => 'Los estacionamientos de visita son el 14, 15, 16 y 17.',
                ]);
            }
        });
    }

    private function adminConProyecto(): array
    {
        $proyecto = Proyecto::factory()->create();
        $admin = User::factory()->administrador()->create();
        $admin->proyectos()->attach($proyecto);
        Sanctum::actingAs($admin);

        return [$admin, $proyecto];
    }

    public function test_admin_puede_subir_un_pdf_a_su_proyecto(): void
    {
        [, $proyecto] = $this->adminConProyecto();

        $response = $this->post(
            '/api/admin/asistente/documento',
            [
                'archivo' => UploadedFile::fake()->create('reglamento.pdf', 100, 'application/pdf'),
                'proyecto_id' => $proyecto->getKey(),
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertCreated()
            ->assertJsonPath('documento.nombre', 'reglamento.pdf')
            ->assertJsonPath('documento.paginas', 2);

        $this->assertDatabaseHas('documentos', [
            'proyecto_id' => $proyecto->getKey(),
            'nombre_original' => 'reglamento.pdf',
        ]);
    }

    public function test_solo_se_permite_un_pdf_por_proyecto(): void
    {
        [, $proyecto] = $this->adminConProyecto();
        Documento::factory()->create(['proyecto_id' => $proyecto->getKey()]);

        $response = $this->post(
            '/api/admin/asistente/documento',
            [
                'archivo' => UploadedFile::fake()->create('otro.pdf', 100, 'application/pdf'),
                'proyecto_id' => $proyecto->getKey(),
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertStatus(409);
    }

    public function test_admin_no_puede_subir_a_un_proyecto_ajeno(): void
    {
        $this->adminConProyecto();
        $ajeno = Proyecto::factory()->create();

        $response = $this->post(
            '/api/admin/asistente/documento',
            [
                'archivo' => UploadedFile::fake()->create('reglamento.pdf', 100, 'application/pdf'),
                'proyecto_id' => $ajeno->getKey(),
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertForbidden();
    }

    public function test_admin_puede_eliminar_el_documento(): void
    {
        [, $proyecto] = $this->adminConProyecto();
        Documento::factory()->create(['proyecto_id' => $proyecto->getKey()]);

        $response = $this->deleteJson('/api/admin/asistente/documento', [
            'proyecto_id' => $proyecto->getKey(),
        ], ['X-Proyecto-Id' => (string) $proyecto->getKey()]);

        $response->assertNoContent();
        $this->assertDatabaseMissing('documentos', ['proyecto_id' => $proyecto->getKey()]);
    }

    public function test_residente_consulta_y_recibe_fuentes(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = User::factory()->create([
            'rol' => 'residente',
            'proyecto_id' => $proyecto->getKey(),
        ]);
        Documento::factory()->create(['proyecto_id' => $proyecto->getKey()]);
        Sanctum::actingAs($residente);

        Http::fake([
            '*/api/chat' => Http::response([
                'message' => ['content' => 'El quincho está en el piso 7 [Página 1].'],
            ]),
        ]);

        $response = $this->postJson('/api/residente/asistente/consultas', [
            'pregunta' => '¿Dónde está el quincho?',
        ]);

        $response->assertOk()
            ->assertJsonPath('respuesta', 'El quincho está en el piso 7 [Página 1].')
            ->assertJsonPath('fuentes.0.pagina', 1);
    }

    public function test_residente_sin_documento_recibe_404(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = User::factory()->create([
            'rol' => 'residente',
            'proyecto_id' => $proyecto->getKey(),
        ]);
        Sanctum::actingAs($residente);

        $this->postJson('/api/residente/asistente/consultas', [
            'pregunta' => '¿Dónde está el quincho?',
        ])->assertNotFound();
    }

    public function test_modelo_no_disponible_devuelve_503(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = User::factory()->create([
            'rol' => 'residente',
            'proyecto_id' => $proyecto->getKey(),
        ]);
        Documento::factory()->create(['proyecto_id' => $proyecto->getKey()]);
        Sanctum::actingAs($residente);

        Http::fake([
            '*/api/chat' => Http::response(['error' => 'boom'], 500),
        ]);

        $this->postJson('/api/residente/asistente/consultas', [
            'pregunta' => '¿Dónde está el quincho?',
        ])->assertStatus(503);
    }

    public function test_admin_no_puede_consultar_como_residente(): void
    {
        $this->adminConProyecto();

        $this->postJson('/api/residente/asistente/consultas', [
            'pregunta' => '¿Dónde está el quincho?',
        ])->assertForbidden();
    }

    public function test_residente_no_puede_subir_documentos(): void
    {
        $proyecto = Proyecto::factory()->create();
        $residente = User::factory()->create([
            'rol' => 'residente',
            'proyecto_id' => $proyecto->getKey(),
        ]);
        Sanctum::actingAs($residente);

        $this->post(
            '/api/admin/asistente/documento',
            ['archivo' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf')],
            ['Accept' => 'application/json'],
        )->assertForbidden();
    }

    public function test_superadmin_no_puede_subir_documentos(): void
    {
        Sanctum::actingAs(User::factory()->superadmin()->create());

        $this->post(
            '/api/admin/asistente/documento',
            ['archivo' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf')],
            ['Accept' => 'application/json'],
        )->assertForbidden();
    }

    public function test_archivo_que_no_es_pdf_es_rechazado(): void
    {
        $this->adminConProyecto();

        $this->post(
            '/api/admin/asistente/documento',
            ['archivo' => UploadedFile::fake()->create('notas.txt', 10, 'text/plain')],
            ['Accept' => 'application/json'],
        )->assertStatus(422);
    }

    public function test_documento_que_excede_el_presupuesto_es_rechazado(): void
    {
        $this->app->instance(ExtractorPdfInterface::class, new class implements ExtractorPdfInterface
        {
            public function extraer(string $rutaFisica): PaginasDocumento
            {
                return new PaginasDocumento([1 => str_repeat('contenido ', 5000)]);
            }
        });

        $this->adminConProyecto();

        $this->post(
            '/api/admin/asistente/documento',
            [
                'archivo' => UploadedFile::fake()->create('grande.pdf', 100, 'application/pdf'),
            ],
            ['Accept' => 'application/json'],
        )->assertStatus(422);
    }
}
