<?php

namespace Database\Factories;

use App\Models\SolicitudResidente;
use App\Models\SolicitudResidentePersona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitudResidentePersona>
 */
class SolicitudResidentePersonaFactory extends Factory
{
    protected $model = SolicitudResidentePersona::class;

    protected static int $rutSequence = 20000000;

    public function definition(): array
    {
        $cuerpo = (string) static::$rutSequence++;

        return [
            'solicitud_id' => SolicitudResidente::factory(),
            'usuario_id' => null,
            'nombre' => fake()->name(),
            'rut' => $cuerpo.'-'.self::calcularDv($cuerpo),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->numerify('+569########'),
            'telefono_emergencia' => fake()->numerify('+569########'),
            'nombre_emergencia' => fake()->name(),
            'tipo_vinculo' => 'propietario',
            'es_principal' => true,
        ];
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
