<?php

namespace Features\Paqueteria\Data\Models;

use App\Models\Paquete as PaqueteModel;
use Features\Paqueteria\Domain\Entities\Paquete;

/**
 * Representación serializable de un paquete tal como vive en la base de datos.
 *
 * Es la única clase de la capa de Datos que conoce la forma exacta del modelo
 * Eloquent; el repositorio la usa para construir la entidad de dominio.
 */
final class PaqueteDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $proyectoId,
        public readonly ?string $unidadPrincipalId,
        public readonly ?string $residenteId,
        public readonly ?string $unidadIdentificador,
        public readonly string $descripcion,
        public readonly ?string $remitente,
        public readonly ?string $fotoPath,
        public readonly string $fechaRecepcion,
        public readonly string $estado,
        public readonly ?string $fechaRetiro,
        public readonly ?string $retiradoPorNombre,
    ) {}

    public static function fromModel(PaqueteModel $model): self
    {
        return new self(
            id: (string) $model->getKey(),
            proyectoId: (string) $model->proyecto_id,
            unidadPrincipalId: $model->unidad_principal_id !== null ? (string) $model->unidad_principal_id : null,
            residenteId: $model->residente_id !== null ? (string) $model->residente_id : null,
            unidadIdentificador: $model->unidadPrincipal?->identificador,
            descripcion: (string) $model->descripcion,
            remitente: $model->remitente,
            fotoPath: $model->foto_path,
            fechaRecepcion: $model->fecha_recepcion?->toDateTimeString() ?? '',
            estado: (string) $model->estado,
            fechaRetiro: $model->fecha_retiro?->toDateTimeString(),
            retiradoPorNombre: $model->retirado_por_nombre,
        );
    }

    public function toEntity(): Paquete
    {
        return new Paquete(
            id: $this->id,
            proyectoId: $this->proyectoId,
            unidadPrincipalId: $this->unidadPrincipalId,
            residenteId: $this->residenteId,
            unidadIdentificador: $this->unidadIdentificador,
            descripcion: $this->descripcion,
            remitente: $this->remitente,
            fotoPath: $this->fotoPath,
            fechaRecepcion: $this->fechaRecepcion,
            estado: $this->estado,
            fechaRetiro: $this->fechaRetiro,
            retiradoPorNombre: $this->retiradoPorNombre,
        );
    }
}
