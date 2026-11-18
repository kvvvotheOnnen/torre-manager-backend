<?php

namespace Features\Archivos\Data;

use Features\Archivos\Domain\Contracts\AlmacenArchivos;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Implementación sobre un disco de Laravel (local, público o S3).
 *
 * El disco se inyecta por configuración, así que la misma clase sirve para
 * `public` en desarrollo y para un disco S3 compatible en producción.
 */
final class AlmacenEnDisco implements AlmacenArchivos
{
    public function __construct(private readonly string $disco) {}

    public function guardar(string $contenido, string $nombreOriginal, string $carpeta): string
    {
        $ruta = $this->construirRuta($carpeta, $nombreOriginal);

        Storage::disk($this->disco)->put($ruta, $contenido);

        return $ruta;
    }

    public function reemplazar(
        ?string $rutaAnterior,
        string $contenido,
        string $nombreOriginal,
        string $carpeta,
    ): string {
        $ruta = $this->guardar($contenido, $nombreOriginal, $carpeta);

        $this->eliminar($rutaAnterior);

        return $ruta;
    }

    public function eliminar(?string $ruta): void
    {
        if (! $this->tieneRuta($ruta)) {
            return;
        }

        $disco = Storage::disk($this->disco);

        if ($disco->exists($ruta)) {
            $disco->delete($ruta);
        }
    }

    public function url(?string $ruta): ?string
    {
        if (! $this->tieneRuta($ruta)) {
            return null;
        }

        return Storage::disk($this->disco)->url($ruta);
    }

    public function existe(?string $ruta): bool
    {
        if (! $this->tieneRuta($ruta)) {
            return false;
        }

        return Storage::disk($this->disco)->exists($ruta);
    }

    private function construirRuta(string $carpeta, string $nombreOriginal): string
    {
        $carpeta = trim($carpeta, '/');
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $nombre = Str::uuid()->toString();

        if ($extension !== '' && preg_match('/^[a-z0-9]{1,8}$/', $extension) === 1) {
            $nombre .= '.'.$extension;
        }

        return $carpeta === '' ? $nombre : $carpeta.'/'.$nombre;
    }

    private function tieneRuta(?string $ruta): bool
    {
        return $ruta !== null && trim($ruta) !== '';
    }
}
