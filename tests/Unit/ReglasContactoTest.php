<?php

namespace Tests\Unit;

use App\Rules\RutChileno;
use App\Rules\TelefonoValido;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ReglasContactoTest extends TestCase
{
    public function test_regla_rut_acepta_formatos_validos(): void
    {
        foreach (['12345678-5', '12.345.678-5', '123456785'] as $rut) {
            $validator = Validator::make(['rut' => $rut], ['rut' => [new RutChileno]]);

            $this->assertFalse($validator->fails(), "RUT válido rechazado: {$rut}");
        }
    }

    public function test_regla_rut_rechaza_invalidos(): void
    {
        $validator = Validator::make(['rut' => '12345678-9'], ['rut' => [new RutChileno]]);

        $this->assertTrue($validator->fails());
    }

    public function test_regla_telefono_acepta_formatos_validos(): void
    {
        foreach (['+56912345678', '912345678', '+56 9 1234 5678'] as $telefono) {
            $validator = Validator::make(['telefono' => $telefono], ['telefono' => [new TelefonoValido]]);

            $this->assertFalse($validator->fails(), "Teléfono válido rechazado: {$telefono}");
        }
    }

    public function test_regla_telefono_rechaza_invalidos(): void
    {
        $validator = Validator::make(['telefono' => '1234'], ['telefono' => [new TelefonoValido]]);

        $this->assertTrue($validator->fails());
    }
}
