<?php

namespace Features\Superadmin\Domain\Services;

use InvalidArgumentException;

/**
 * Valida y normaliza las instalaciones extra de un proyecto.
 */
final class NormalizadorInstalacionesProyecto
{
    /**
     * @param  array<int, array<string, mixed>>  $instalaciones
     * @return array<int, array{nombre: string, capacidad: int, descripcion: ?string, atributos: ?array<string, mixed>}>
     *
     * @throws InvalidArgumentException
     */
    public function normalizar(array $instalaciones): array
    {
        $normalizadas = [];

        foreach ($instalaciones as $instalacion) {
            $nombre = trim((string) ($instalacion['nombre'] ?? ''));

            if ($nombre === '') {
                throw new InvalidArgumentException('Cada instalación extra debe tener un nombre.');
            }

            $capacidad = (int) ($instalacion['capacidad'] ?? 0);

            if ($capacidad < 1 || $capacidad > 500) {
                throw new InvalidArgumentException("La capacidad de '{$nombre}' debe estar entre 1 y 500.");
            }

            $descripcion = $instalacion['descripcion'] ?? null;
            $descripcion = is_string($descripcion) ? trim($descripcion) : null;
            $descripcion = $descripcion !== '' ? $descripcion : null;

            if ($descripcion !== null && mb_strlen($descripcion) > 100) {
                throw new InvalidArgumentException("La descripción de '{$nombre}' no puede superar 100 caracteres.");
            }

            $normalizadas[] = [
                'nombre' => $nombre,
                'capacidad' => $capacidad,
                'descripcion' => $descripcion,
                'atributos' => $instalacion['atributos'] ?? null,
            ];
        }

        return $normalizadas;
    }
}
