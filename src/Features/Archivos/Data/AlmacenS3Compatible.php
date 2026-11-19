<?php

namespace Features\Archivos\Data;

use Features\Archivos\Domain\Contracts\AlmacenArchivos;

/**
 * Almacén para S3 o servicios compatibles (Garage, MinIO, Wasabi).
 *
 * No está registrado en el contenedor de servicios: hoy `FabricaAlmacenes`
 * solo construye el almacén local. Para activarlo:
 *
 * 1. `composer require league/flysystem-aws-s3-v3`
 * 2. definir el disco `s3` en `config/filesystems.php` apuntando a Garage/S3;
 * 3. `ARCHIVOS_DRIVER=s3` y `ARCHIVOS_DISCO=s3` en el entorno.
 *
 * Como el contrato solo habla de rutas lógicas y contenido, cambiar de disco
 * no altera controladores, modelos ni el cliente Flutter.
 */
final class AlmacenS3Compatible implements AlmacenArchivos
{
    private readonly AlmacenEnDisco $almacen;

    public function __construct(string $disco = 's3')
    {
        $this->almacen = new AlmacenEnDisco($disco);
    }

    public function guardar(string $contenido, string $nombreOriginal, string $carpeta): string
    {
        return $this->almacen->guardar($contenido, $nombreOriginal, $carpeta);
    }

    public function reemplazar(
        ?string $rutaAnterior,
        string $contenido,
        string $nombreOriginal,
        string $carpeta,
    ): string {
        return $this->almacen->reemplazar($rutaAnterior, $contenido, $nombreOriginal, $carpeta);
    }

    public function eliminar(?string $ruta): void
    {
        $this->almacen->eliminar($ruta);
    }

    public function url(?string $ruta): ?string
    {
        return $this->almacen->url($ruta);
    }

    public function existe(?string $ruta): bool
    {
        return $this->almacen->existe($ruta);
    }

    public function rutaFisica(string $ruta): ?string
    {
        return null;
    }
}
