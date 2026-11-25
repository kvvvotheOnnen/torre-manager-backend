<?php

namespace Database\Seeders;

use App\Models\FavoritoMarketplace;
use App\Models\Proyecto;
use App\Models\PublicacionMarketplace;
use App\Models\User;
use Features\Marketplace\Domain\Entities\CategoriaProducto;
use Features\Marketplace\Domain\Entities\CondicionProducto;
use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Illuminate\Database\Seeder;

/**
 * Publicaciones de ejemplo del marketplace.
 *
 * Se siembran sin imagen para que el cliente muestre el recuadro "Sin imagen".
 * Incluye estados vendida, retirada, eliminada y pendiente para probar historial,
 * favoritos y solicitudes sin tener que ejecutar el flujo completo.
 */
final class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $proyecto = Proyecto::query()->orderBy('id')->first();

        if ($proyecto === null) {
            return;
        }

        $residente = User::query()
            ->where('rol', 'residente')
            ->where('proyecto_id', $proyecto->getKey())
            ->first();

        $propietario = User::query()
            ->where('rol', 'propietario')
            ->where('proyecto_id', $proyecto->getKey())
            ->first();

        $administrador = User::query()
            ->where('rol', 'administrador')
            ->where('proyecto_id', $proyecto->getKey())
            ->first();

        $publicador = $residente ?? $propietario ?? $administrador;

        if ($publicador === null) {
            return;
        }

        $dueno = $propietario ?? $residente ?? $administrador;

        $publicaciones = [
            [
                'usuario_id' => $publicador->getKey(),
                'titulo' => 'Refrigerador no frost',
                'descripcion' => 'Refrigerador de 320 litros, funcionando perfectamente. Se vende por cambio de departamento.',
                'precio' => 180000,
                'categoria' => CategoriaProducto::Electrodomesticos->value,
                'condicion' => CondicionProducto::Usado->value,
                'estado' => EstadoPublicacion::Disponible->value,
            ],
            [
                'usuario_id' => $dueno->getKey(),
                'titulo' => 'Juego de living de 3 cuerpos',
                'descripcion' => 'Sofá y dos sillones en tela gris, semi nuevos. Incluye cojines.',
                'precio' => 250000,
                'categoria' => CategoriaProducto::Muebles->value,
                'condicion' => CondicionProducto::SemiNuevo->value,
                'estado' => EstadoPublicacion::Disponible->value,
            ],
            [
                'usuario_id' => $publicador->getKey(),
                'titulo' => 'Notebook Lenovo IdeaPad',
                'descripcion' => 'Notebook de 14 pulgadas, 8 GB de RAM y 256 GB SSD. Con cargador original.',
                'precio' => 320000,
                'categoria' => CategoriaProducto::Electronica->value,
                'condicion' => CondicionProducto::Usado->value,
                'estado' => EstadoPublicacion::Disponible->value,
            ],
            [
                'usuario_id' => $publicador->getKey(),
                'titulo' => 'Patinete eléctrico con batería nueva',
                'descripcion' => 'Patinete eléctrico de 250 W con 20 km de autonomía. Incluye cargador original.',
                'precio' => 150000,
                'categoria' => CategoriaProducto::Electronica->value,
                'condicion' => CondicionProducto::SemiNuevo->value,
                'estado' => EstadoPublicacion::Pendiente->value,
            ],
            [
                'usuario_id' => $dueno->getKey(),
                'titulo' => 'Chaqueta de invierno talla M',
                'descripcion' => 'Chaqueta impermeable color azul, usada una temporada. Sin detalles.',
                'precio' => 25000,
                'categoria' => CategoriaProducto::Ropa->value,
                'condicion' => CondicionProducto::Usado->value,
                'estado' => EstadoPublicacion::Vendida->value,
                'vendido_en' => now()->subDay(),
            ],
            [
                'usuario_id' => $publicador->getKey(),
                'titulo' => 'Set de herramientas básico',
                'descripcion' => 'Taladro, llaves y destornilladores. Ideal para reparaciones del hogar.',
                'precio' => 45000,
                'categoria' => CategoriaProducto::Accesorios->value,
                'condicion' => CondicionProducto::SemiNuevo->value,
                'estado' => EstadoPublicacion::Retirada->value,
            ],
            [
                'usuario_id' => $dueno->getKey(),
                'titulo' => 'Bicicleta aro 26',
                'descripcion' => 'Bicicleta de paseo con cambios. Se publicó por error y fue retirada por moderación.',
                'precio' => 90000,
                'categoria' => CategoriaProducto::Otros->value,
                'condicion' => CondicionProducto::Usado->value,
                'estado' => EstadoPublicacion::Eliminada->value,
                'motivo_eliminacion' => 'La publicación no corresponde a una venta entre residentes.',
                'eliminado_por' => $administrador?->getKey(),
                'eliminado_por_rol' => 'administrador',
                'eliminado_en' => now()->subDays(2),
            ],
        ];

        foreach ($publicaciones as $publicacion) {
            PublicacionMarketplace::query()->updateOrCreate(
                [
                    'proyecto_id' => $proyecto->getKey(),
                    'titulo' => $publicacion['titulo'],
                ],
                $publicacion + ['proyecto_id' => $proyecto->getKey()],
            );
        }

        if ($residente !== null) {
            $favorita = PublicacionMarketplace::query()
                ->where('proyecto_id', $proyecto->getKey())
                ->where('titulo', 'Juego de living de 3 cuerpos')
                ->first();

            if ($favorita !== null) {
                FavoritoMarketplace::query()->firstOrCreate([
                    'publicacion_id' => $favorita->getKey(),
                    'usuario_id' => $residente->getKey(),
                ]);
            }
        }
    }
}
