<?php

namespace Features\Marketplace\Data\Repositories;

use App\Models\FavoritoMarketplace as FavoritoModel;
use App\Models\PublicacionMarketplace as PublicacionModel;
use Features\Marketplace\Domain\Entities\CategoriaProducto;
use Features\Marketplace\Domain\Entities\CondicionProducto;
use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Features\Marketplace\Domain\Entities\PublicacionMarketplace;
use Features\Marketplace\Domain\Exceptions\PublicacionNoEncontradaException;
use Features\Marketplace\Domain\Repositories\MarketplaceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class MarketplaceRepository implements MarketplaceRepositoryInterface
{
    public function listar(
        string $proyectoId,
        ?CategoriaProducto $categoria = null,
        ?string $buscar = null,
        ?EstadoPublicacion $estado = null,
        bool $todas = false,
        ?string $excluirUsuarioId = null,
    ): array {
        $consulta = PublicacionModel::query()->where('proyecto_id', $proyectoId);

        if ($excluirUsuarioId !== null) {
            $consulta->where(function (Builder $subconsulta) use ($excluirUsuarioId): void {
                $subconsulta
                    ->whereNull('usuario_id')
                    ->orWhere('usuario_id', '!=', $excluirUsuarioId);
            });
        }

        if ($estado !== null) {
            $consulta->where('estado', $estado->value);
        } elseif (! $todas) {
            $consulta->where('estado', EstadoPublicacion::Disponible->value);
        } else {
            $consulta->where('estado', '!=', EstadoPublicacion::Eliminada->value);
        }

        if ($categoria !== null) {
            $consulta->where('categoria', $categoria->value);
        }

        $termino = trim((string) $buscar);

        if ($termino !== '') {
            $patron = '%'.Str::lower(Str::ascii(str_replace(['%', '_'], '', $termino))).'%';

            $consulta->where(function (Builder $subconsulta) use ($patron): void {
                $subconsulta->whereRaw($this->sqlSinAcentos('titulo').' LIKE ?', [$patron])
                    ->orWhereRaw($this->sqlSinAcentos('descripcion').' LIKE ?', [$patron]);
            });
        }

        return $consulta
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PublicacionModel $modelo) => $this->aEntidad($modelo))
            ->all();
    }

    public function listarDeUsuario(string $usuarioId, string $proyectoId, bool $soloDisponibles): array
    {
        $consulta = PublicacionModel::query()
            ->where('proyecto_id', $proyectoId)
            ->where('usuario_id', $usuarioId);

        if ($soloDisponibles) {
            $consulta->where('estado', EstadoPublicacion::Disponible->value);
        } else {
            $consulta->whereIn('estado', [
                EstadoPublicacion::Vendida->value,
                EstadoPublicacion::Retirada->value,
                EstadoPublicacion::Eliminada->value,
            ]);
        }

        return $consulta
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (PublicacionModel $modelo) => $this->aEntidad($modelo))
            ->all();
    }

    public function listarFavoritas(string $usuarioId, string $proyectoId): array
    {
        $limite = now()->subDays(3);

        return PublicacionModel::query()
            ->where('proyecto_id', $proyectoId)
            ->whereHas('favoritos', fn (Builder $favorito) => $favorito->where('usuario_id', $usuarioId))
            ->where(function (Builder $consulta) use ($limite): void {
                $consulta->where('estado', EstadoPublicacion::Disponible->value)
                    ->orWhere(function (Builder $vendida) use ($limite): void {
                        $vendida->where('estado', EstadoPublicacion::Vendida->value)
                            ->where('vendido_en', '>=', $limite);
                    });
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PublicacionModel $modelo) => $this->aEntidad($modelo))
            ->all();
    }

    public function obtener(string $id, string $proyectoId): PublicacionMarketplace
    {
        return $this->aEntidad($this->buscarModelo($id, $proyectoId));
    }

    public function crear(array $datos): PublicacionMarketplace
    {
        return $this->aEntidad(PublicacionModel::query()->create($datos));
    }

    public function actualizar(string $id, string $proyectoId, array $datos): PublicacionMarketplace
    {
        $modelo = $this->buscarModelo($id, $proyectoId);
        $modelo->fill($datos);
        $modelo->save();

        return $this->aEntidad($modelo->refresh());
    }

    public function esFavorita(string $publicacionId, string $usuarioId): bool
    {
        return FavoritoModel::query()
            ->where('publicacion_id', $publicacionId)
            ->where('usuario_id', $usuarioId)
            ->exists();
    }

    public function idsFavoritas(string $usuarioId, string $proyectoId): array
    {
        return FavoritoModel::query()
            ->where('usuario_id', $usuarioId)
            ->whereHas('publicacion', fn (Builder $publicacion) => $publicacion->where('proyecto_id', $proyectoId))
            ->pluck('publicacion_id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();
    }

    public function agregarFavorito(string $publicacionId, string $usuarioId): void
    {
        FavoritoModel::query()->firstOrCreate([
            'publicacion_id' => $publicacionId,
            'usuario_id' => $usuarioId,
        ]);
    }

    public function quitarFavorito(string $publicacionId, string $usuarioId): void
    {
        FavoritoModel::query()
            ->where('publicacion_id', $publicacionId)
            ->where('usuario_id', $usuarioId)
            ->delete();
    }

    /**
     * Expresión SQL portable que compara texto sin mayúsculas ni tildes.
     */
    private function sqlSinAcentos(string $columna): string
    {
        $expresion = "COALESCE({$columna}, '')";

        $reemplazos = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ];

        foreach ($reemplazos as $acento => $plano) {
            $expresion = "REPLACE({$expresion}, '{$acento}', '{$plano}')";
        }

        return "LOWER({$expresion})";
    }

    private function buscarModelo(string $id, string $proyectoId): PublicacionModel
    {
        $modelo = PublicacionModel::query()
            ->whereKey($id)
            ->where('proyecto_id', $proyectoId)
            ->first();

        if ($modelo === null) {
            throw PublicacionNoEncontradaException::becauseDoesNotExist();
        }

        return $modelo;
    }

    private function aEntidad(PublicacionModel $modelo): PublicacionMarketplace
    {
        return new PublicacionMarketplace(
            id: (string) $modelo->getKey(),
            proyectoId: (string) $modelo->proyecto_id,
            usuarioId: $modelo->usuario_id === null ? null : (string) $modelo->usuario_id,
            titulo: (string) $modelo->titulo,
            descripcion: (string) $modelo->descripcion,
            precio: (int) $modelo->precio,
            categoria: CategoriaProducto::tryFrom((string) $modelo->categoria) ?? CategoriaProducto::Otros,
            condicion: CondicionProducto::tryFrom((string) $modelo->condicion) ?? CondicionProducto::Usado,
            estado: EstadoPublicacion::tryFrom((string) $modelo->estado) ?? EstadoPublicacion::Retirada,
            fotoPath: $modelo->foto_path === null ? null : (string) $modelo->foto_path,
            vendidoEn: $modelo->vendido_en?->toIso8601String(),
            motivoEliminacion: $modelo->motivo_eliminacion === null ? null : (string) $modelo->motivo_eliminacion,
            eliminadoPor: $modelo->eliminado_por === null ? null : (string) $modelo->eliminado_por,
            eliminadoPorRol: $modelo->eliminado_por_rol === null ? null : (string) $modelo->eliminado_por_rol,
            eliminadoEn: $modelo->eliminado_en?->toIso8601String(),
            creadoEn: $modelo->created_at?->toIso8601String(),
        );
    }
}
