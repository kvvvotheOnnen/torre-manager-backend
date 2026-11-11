<?php

namespace Features\Asistente\Domain\Entities;

final class PaginasDocumento
{
    /**
     * @param  array<int, string>  $paginas  Texto indexado por número de página (1-based).
     */
    public function __construct(
        private readonly array $paginas,
    ) {}

    /**
     * @return array<int, string>
     */
    public function paginas(): array
    {
        return $this->paginas;
    }

    public function cantidad(): int
    {
        return count($this->paginas);
    }

    public function estaVacio(): bool
    {
        return trim(implode('', $this->paginas)) === '';
    }

    /**
     * Construye el texto que se inyecta al modelo con marcadores de página
     * para que pueda citar la fuente.
     */
    public function textoConMarcadores(string $nombreDocumento): string
    {
        $partes = ["[Documento: {$nombreDocumento}]"];

        foreach ($this->paginas as $numero => $contenido) {
            $contenido = trim($contenido);

            if ($contenido === '') {
                continue;
            }

            $partes[] = "[Página {$numero}]\n{$contenido}";
        }

        return implode("\n\n", $partes);
    }
}
