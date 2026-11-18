<?php

namespace Database\Factories;

use App\Models\Proyecto;
use App\Models\PublicacionMarketplace;
use App\Models\User;
use Features\Marketplace\Domain\Entities\CategoriaProducto;
use Features\Marketplace\Domain\Entities\CondicionProducto;
use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PublicacionMarketplace>
 */
final class PublicacionMarketplaceFactory extends Factory
{
    protected $model = PublicacionMarketplace::class;

    public function definition(): array
    {
        return [
            'proyecto_id' => Proyecto::factory(),
            'usuario_id' => User::factory(),
            'titulo' => fake()->sentence(3),
            'descripcion' => fake()->paragraph(),
            'precio' => fake()->numberBetween(0, 500000),
            'categoria' => fake()->randomElement(CategoriaProducto::cases())->value,
            'condicion' => fake()->randomElement(CondicionProducto::cases())->value,
            'estado' => EstadoPublicacion::Disponible->value,
        ];
    }

    public function vendida(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado' => EstadoPublicacion::Vendida->value,
            'vendido_en' => now(),
        ]);
    }
}
