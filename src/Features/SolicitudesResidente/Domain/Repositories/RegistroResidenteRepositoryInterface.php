<?php

namespace Features\SolicitudesResidente\Domain\Repositories;

use Features\SolicitudesResidente\Domain\Entities\DatosRegistroResidente;
use Features\SolicitudesResidente\Domain\Entities\ProyectoRegistro;
use Features\SolicitudesResidente\Domain\Entities\UnidadRegistro;

interface RegistroResidenteRepositoryInterface
{
    /** @return ProyectoRegistro[] */
    public function listarProyectosDisponibles(): array;

    /** @return UnidadRegistro[] */
    public function listarUnidades(string $proyectoId): array;

    public function proyectoDisponible(string $proyectoId): bool;

    public function unidadPerteneceAProyecto(string $unidadPrincipalId, string $proyectoId): bool;

    public function existeEmail(string $email): bool;

    public function existeRut(string $rut): bool;

    public function crearCuentaYSolicitud(DatosRegistroResidente $datos): string;
}
