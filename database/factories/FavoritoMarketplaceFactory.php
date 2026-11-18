<?php

namespace Database\Factories;

use App\Models\FavoritoMarketplace;
use App\Models\PublicacionMarketplace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FavoritoMarketplace>
 */
final class FavoritoMarketplaceFactory extends Factory
{
    protected $model = FavoritoMarketplace::class;

    public function definition(): array
    {
        return [
            'publicacion_id' => PublicacionMarketplace::factory(),
            'usuario_id' => User::factory(),
        ];
    }
}
