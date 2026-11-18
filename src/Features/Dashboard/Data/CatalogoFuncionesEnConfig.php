<?php

namespace Features\Dashboard\Data;

use Features\Dashboard\Domain\Entities\AmbitoFuncion;
use Features\Dashboard\Domain\Entities\Funcion;
use Features\Dashboard\Domain\Repositories\CatalogoFuncionesRepositoryInterface;

final class CatalogoFuncionesEnConfig implements CatalogoFuncionesRepositoryInterface
{
    /** @return Funcion[] */
    public function listar(): array
    {
        /** @var array<int, array<string, mixed>> $definiciones */
        $definiciones = config('funciones.funciones', []);

        return array_map(
            fn (array $definicion): Funcion => new Funcion(
                id: (string) ($definicion['id'] ?? ''),
                titulo: (string) ($definicion['titulo'] ?? ''),
                descripcion: (string) ($definicion['descripcion'] ?? ''),
                icono: (string) ($definicion['icono'] ?? ''),
                capacidades: array_values(array_map(
                    static fn (mixed $capacidad): string => (string) $capacidad,
                    (array) ($definicion['capacidades'] ?? []),
                )),
                ambito: AmbitoFuncion::from((string) ($definicion['ambito'] ?? AmbitoFuncion::Proyecto->value)),
                ruta: (string) ($definicion['ruta'] ?? ''),
                requiereModulo: isset($definicion['requiere_modulo']) ? (string) $definicion['requiere_modulo'] : null,
                acciones: array_map(
                    static fn (mixed $capacidad): string => (string) $capacidad,
                    (array) ($definicion['acciones'] ?? []),
                ),
            ),
            array_values($definiciones),
        );
    }
}
