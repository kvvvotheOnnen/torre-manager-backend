<?php

namespace Tests\Unit;

use Features\Auth\Domain\Exceptions\RutInvalidoException;
use Features\Auth\Domain\ValueObjects\Rut;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RutTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function rutsValidos(): array
    {
        return [
            'formato canonico' => ['12345678-5', '12345678-5'],
            'sin guion' => ['123456785', '12345678-5'],
            'con puntos' => ['12.345.678-5', '12345678-5'],
            'dv k minuscula' => ['10000013-k', '10000013-K'],
            'dv cero' => ['56789012-0', '56789012-0'],
        ];
    }

    #[DataProvider('rutsValidos')]
    public function test_normaliza_y_valida_ruts_correctos(string $entrada, string $esperado): void
    {
        $this->assertSame($esperado, Rut::desde($entrada)->valor());
    }

    /** @return array<string, array{string}> */
    public static function rutsInvalidos(): array
    {
        return [
            'vacio' => [''],
            'solo dv' => ['5'],
            'dv incorrecto' => ['12345678-9'],
            'letras' => ['abcdefgh-1'],
            'cuerpo cero' => ['00000000-0'],
        ];
    }

    #[DataProvider('rutsInvalidos')]
    public function test_rechaza_ruts_invalidos(string $entrada): void
    {
        $this->expectException(RutInvalidoException::class);

        Rut::desde($entrada);
    }

    public function test_es_valido_no_lanza_excepcion(): void
    {
        $this->assertTrue(Rut::esValido('11.111.111-1'));
        $this->assertFalse(Rut::esValido('11.111.111-2'));
    }

    public function test_expone_cuerpo_y_dv(): void
    {
        $rut = Rut::desde('12.345.678-5');

        $this->assertSame('12345678', $rut->cuerpo());
        $this->assertSame('5', $rut->dv());
    }
}
