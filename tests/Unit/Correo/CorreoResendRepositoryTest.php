<?php

namespace Tests\Unit\Correo;

use Features\Correo\Data\Repositories\CorreoResendRepository;
use Features\Correo\Domain\Entities\Correo;
use Features\Correo\Domain\Exceptions\EnvioCorreoException;
use RuntimeException;
use Tests\TestCase;

class CorreoResendRepositoryTest extends TestCase
{
    public function test_envia_el_correo_con_el_payload_esperado(): void
    {
        config([
            'services.resend.key' => 'clave-de-prueba',
            'services.resend.from' => 'onboarding@resend.dev',
        ]);

        $repositorio = $this->repositorioEspia();

        $repositorio->enviar(new Correo(
            destinatario: 'destinatario@example.test',
            asunto: 'Bienvenido',
            html: '<p>Hola</p>',
        ));

        $this->assertCount(1, $repositorio->envios);
        $this->assertSame('clave-de-prueba', $repositorio->envios[0]['apiKey']);
        $this->assertSame([
            'from' => 'onboarding@resend.dev',
            'to' => ['destinatario@example.test'],
            'subject' => 'Bienvenido',
            'html' => '<p>Hola</p>',
        ], $repositorio->envios[0]['payload']);
    }

    public function test_falla_si_la_api_key_no_esta_configurada(): void
    {
        config(['services.resend.key' => null]);

        $this->expectException(EnvioCorreoException::class);
        $this->expectExceptionMessage('La API key de Resend no está configurada.');

        $this->repositorioEspia()->enviar(new Correo(
            destinatario: 'destinatario@example.test',
            asunto: 'Asunto',
            html: '<p>Hola</p>',
        ));
    }

    public function test_envuelve_los_errores_del_proveedor(): void
    {
        config(['services.resend.key' => 'clave-de-prueba']);

        $repositorio = new class extends CorreoResendRepository
        {
            protected function despachar(string $apiKey, array $payload): void
            {
                throw new RuntimeException('Fallo de red');
            }
        };

        $this->expectException(EnvioCorreoException::class);

        try {
            $repositorio->enviar(new Correo(
                destinatario: 'destinatario@example.test',
                asunto: 'Asunto',
                html: '<p>Hola</p>',
            ));
        } catch (EnvioCorreoException $exception) {
            $this->assertInstanceOf(RuntimeException::class, $exception->getPrevious());

            throw $exception;
        }
    }

    private function repositorioEspia(): CorreoResendRepository
    {
        return new class extends CorreoResendRepository
        {
            /** @var array<int, array{apiKey: string, payload: array<string, mixed>}> */
            public array $envios = [];

            protected function despachar(string $apiKey, array $payload): void
            {
                $this->envios[] = ['apiKey' => $apiKey, 'payload' => $payload];
            }
        };
    }
}
