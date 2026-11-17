<?php

namespace Features\Dashboard\Domain\Entities;

/**
 * Función de negocio expuesta en un dashboard.
 *
 * Es la unidad que el frontend dibuja: una tarjeta con título, ícono y ruta.
 * No conoce roles; solo declara las capacidades que exige para mostrarse.
 */
final class Funcion
{
    /**
     * @param  string[]  $capacidades  Capacidades exigidas solo para ver la función.
     * @param  array<string, string>  $acciones  Acciones declaradas: nombre visible => capacidad que las habilita.
     */
    public function __construct(
        private readonly string $id,
        private readonly string $titulo,
        private readonly string $descripcion,
        private readonly string $icono,
        private readonly array $capacidades,
        private readonly AmbitoFuncion $ambito,
        private readonly string $ruta,
        private readonly ?string $requiereModulo = null,
        private readonly array $acciones = [],
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function titulo(): string
    {
        return $this->titulo;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function icono(): string
    {
        return $this->icono;
    }

    /** @return string[] */
    public function capacidades(): array
    {
        return $this->capacidades;
    }

    public function ambito(): AmbitoFuncion
    {
        return $this->ambito;
    }

    public function ruta(): string
    {
        return $this->ruta;
    }

    public function requiereModulo(): ?string
    {
        return $this->requiereModulo;
    }

    /**
     * Acciones que la función ofrece, con la capacidad que habilita cada una.
     *
     * No autoriza nada por sí mismo: el caso de uso resuelve cada capacidad
     * y entrega los permisos ya calculados al cliente.
     *
     * @return array<string, string>
     */
    public function acciones(): array
    {
        return $this->acciones;
    }
}
