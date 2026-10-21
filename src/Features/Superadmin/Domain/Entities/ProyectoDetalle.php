<?php

namespace Features\Superadmin\Domain\Entities;

final class ProyectoDetalle
{
    /**
     * @param ModuloSistema[] $modulos
     * @param InstalacionExtra[] $instalacionesExtra
     * @param string[] $administradoresIds
     */
    public function __construct(
        private readonly string $id,
        private readonly string $nombre,
        private readonly ?string $descripcion,
        private readonly TipoCondominio $tipoCondominio,
        private readonly array $modulos,
        private readonly array $instalacionesExtra,
        private readonly array $administradoresIds,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function descripcion(): ?string
    {
        return $this->descripcion;
    }

    public function tipoCondominio(): TipoCondominio
    {
        return $this->tipoCondominio;
    }

    /** @return ModuloSistema[] */
    public function modulos(): array
    {
        return $this->modulos;
    }

    /** @return InstalacionExtra[] */
    public function instalacionesExtra(): array
    {
        return $this->instalacionesExtra;
    }

    /** @return string[] */
    public function administradoresIds(): array
    {
        return $this->administradoresIds;
    }
}
