<?php

namespace Database\Seeders;

use App\Models\Documento;
use App\Models\Proyecto;
use App\Models\User;
use Features\Asistente\Data\Services\PdfTextExtractor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Restaura el reglamento de ejemplo del asistente IA.
 *
 * El PDF fuente está versionado en el repositorio, por lo que sobrevive a
 * cualquier `migrate:fresh --seed` o limpieza de `storage/`. El seeder es
 * idempotente: siempre deja exactamente un documento asociado al proyecto demo.
 */
final class DocumentoReglamentoSeeder extends Seeder
{
    private const NOMBRE_PROYECTO = 'Vanguardia Center';

    private const NOMBRE_DOCUMENTO = 'Reglamento Vanguardia Center (ejemplo).pdf';

    private const ARCHIVO_FUENTE = 'docs/ejemplos/vanguardia_center_infraestructura.pdf';

    private const RUTA_DESTINO = 'proyectos/%s/reglamento_vanguardia_center.pdf';

    public function run(): void
    {
        $proyecto = Proyecto::query()
            ->where('nombre', self::NOMBRE_PROYECTO)
            ->first();

        if ($proyecto === null) {
            throw new RuntimeException(
                'No existe el proyecto demo "'.self::NOMBRE_PROYECTO.'" para cargar el reglamento.',
            );
        }

        $origen = base_path(self::ARCHIVO_FUENTE);

        if (! File::exists($origen)) {
            throw new RuntimeException("No se encontró el PDF de ejemplo en {$origen}.");
        }

        $ruta = sprintf(self::RUTA_DESTINO, $proyecto->getKey());
        $disco = Storage::disk('local');
        $disco->put($ruta, File::get($origen));

        $paginas = (new PdfTextExtractor)->extraer($disco->path($ruta));

        if ($paginas->estaVacio()) {
            throw new RuntimeException('El PDF de ejemplo no contiene texto extraíble.');
        }

        $texto = $paginas->textoConMarcadores(self::NOMBRE_DOCUMENTO);

        Documento::query()->updateOrCreate(
            ['proyecto_id' => $proyecto->getKey()],
            [
                'subido_por' => $this->administradorId($proyecto->getKey()),
                'nombre_original' => self::NOMBRE_DOCUMENTO,
                'ruta' => $ruta,
                'paginas' => $paginas->cantidad(),
                'texto' => $texto,
                'tamano_tokens' => max(1, (int) ceil(mb_strlen($texto, 'UTF-8') / 4)),
            ],
        );
    }

    private function administradorId(int|string $proyectoId): ?int
    {
        $email = env('ADMIN_EMAIL', 'admin@torremanager.test');

        $id = User::query()->where('email', $email)->value('id');

        if ($id !== null) {
            return (int) $id;
        }

        $id = User::query()
            ->where('proyecto_id', $proyectoId)
            ->where('rol', 'administrador')
            ->value('id');

        return $id === null ? null : (int) $id;
    }
}
