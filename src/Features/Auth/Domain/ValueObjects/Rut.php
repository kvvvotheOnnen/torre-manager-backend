<?php

namespace Features\Auth\Domain\ValueObjects;

use Features\Auth\Domain\Exceptions\RutInvalidoException;

final class Rut
{
    private function __construct(
        private readonly string $cuerpo,
        private readonly string $dv,
    ) {}

    public static function desde(string $valor): self
    {
        $limpio = strtoupper((string) preg_replace('/[^0-9kK]/', '', $valor));

        if (strlen($limpio) < 2) {
            throw RutInvalidoException::porFormato();
        }

        $cuerpo = substr($limpio, 0, -1);
        $dv = substr($limpio, -1);

        if ($cuerpo === '' || strlen($cuerpo) > 8 || ! ctype_digit($cuerpo)) {
            throw RutInvalidoException::porFormato();
        }

        if ((int) $cuerpo === 0) {
            throw RutInvalidoException::porFormato();
        }

        if ($dv !== self::calcularDv($cuerpo)) {
            throw RutInvalidoException::porDigitoVerificador();
        }

        return new self($cuerpo, $dv);
    }

    public static function esValido(string $valor): bool
    {
        try {
            self::desde($valor);
        } catch (RutInvalidoException) {
            return false;
        }

        return true;
    }

    public function valor(): string
    {
        return $this->cuerpo.'-'.$this->dv;
    }

    public function cuerpo(): string
    {
        return $this->cuerpo;
    }

    public function dv(): string
    {
        return $this->dv;
    }

    private static function calcularDv(string $cuerpo): string
    {
        $suma = 0;
        $multiplicador = 2;

        for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
            $suma += (int) $cuerpo[$i] * $multiplicador;
            $multiplicador = $multiplicador === 7 ? 2 : $multiplicador + 1;
        }

        $resto = 11 - ($suma % 11);

        return match ($resto) {
            11 => '0',
            10 => 'K',
            default => (string) $resto,
        };
    }
}
