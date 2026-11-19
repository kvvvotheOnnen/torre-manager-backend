<?php

namespace Features\Archivos\Domain\Contracts;

/**
 * Puerto de almacenamiento de archivos.
 *
 * La capa de dominio y los controladores solo conocen contenido y rutas
 * lógicas; el disco real (público local, S3, Garage) es un detalle de la
 * implementación. Ninguna implementación puede depender de modelos.
 */
interface AlmacenArchivos
{
    /**
     * Guarda el contenido y devuelve la ruta lógica resultante.
     */
    public function guardar(string $contenido, string $nombreOriginal, string $carpeta): string;

    /**
     * Guarda el contenido nuevo y elimina el archivo anterior si existe.
     */
    public function reemplazar(
        ?string $rutaAnterior,
        string $contenido,
        string $nombreOriginal,
        string $carpeta,
    ): string;

    public function eliminar(?string $ruta): void;

    public function url(?string $ruta): ?string;

    public function existe(?string $ruta): bool;

    /**
     * Ruta física en el servidor, si el disco subyacente la expone.
     *
     * Solo se usa para procesos locales (p. ej. extraer texto de un PDF).
     * Los discos remotos devuelven null.
     */
    public function rutaFisica(string $ruta): ?string;
}
