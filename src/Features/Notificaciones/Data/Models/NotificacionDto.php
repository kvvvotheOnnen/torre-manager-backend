<?php

namespace Features\Notificaciones\Data\Models;

use App\Models\Notificacion as NotificacionModel;
use DateTimeImmutable;
use Features\Notificaciones\Domain\Entities\Notificacion;

final class NotificacionDto
{
    /**
     * @param  array<string, mixed>|null  $datos
     */
    public function __construct(
        public readonly string $id,
        public readonly string $usuarioId,
        public readonly ?string $proyectoId,
        public readonly string $tipo,
        public readonly string $titulo,
        public readonly string $mensaje,
        public readonly ?array $datos,
        public readonly bool $leida,
        public readonly ?DateTimeImmutable $creadoEn,
    ) {}

    public static function fromModel(NotificacionModel $modelo): self
    {
        $creadoEn = $modelo->created_at;

        return new self(
            id: (string) $modelo->getKey(),
            usuarioId: (string) $modelo->usuario_id,
            proyectoId: $modelo->proyecto_id !== null ? (string) $modelo->proyecto_id : null,
            tipo: (string) $modelo->tipo,
            titulo: (string) $modelo->titulo,
            mensaje: (string) $modelo->mensaje,
            datos: is_array($modelo->datos) ? $modelo->datos : null,
            leida: (bool) $modelo->leida,
            creadoEn: $creadoEn !== null
                ? DateTimeImmutable::createFromInterface($creadoEn)
                : null,
        );
    }

    public function toEntity(): Notificacion
    {
        return new Notificacion(
            id: $this->id,
            usuarioId: $this->usuarioId,
            proyectoId: $this->proyectoId,
            tipo: $this->tipo,
            titulo: $this->titulo,
            mensaje: $this->mensaje,
            datos: $this->datos,
            leida: $this->leida,
            creadoEn: $this->creadoEn,
        );
    }
}
