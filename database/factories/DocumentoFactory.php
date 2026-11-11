<?php

namespace Database\Factories;

use App\Models\Documento;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Documento>
 */
class DocumentoFactory extends Factory
{
    protected $model = Documento::class;

    public function definition(): array
    {
        $texto = "[Documento: reglamento.pdf]\n\n[Página 1]\nContenido de ejemplo.";

        return [
            'proyecto_id' => Proyecto::factory(),
            'subido_por' => null,
            'nombre_original' => 'reglamento.pdf',
            'ruta' => 'proyectos/1/documento_asistente.pdf',
            'paginas' => 2,
            'texto' => $texto,
            'tamano_tokens' => (int) ceil(mb_strlen($texto, 'UTF-8') / 4),
        ];
    }
}
