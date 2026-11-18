<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\EliminarPublicacionRequest;
use App\Http\Requests\Marketplace\FavoritoRequest;
use App\Http\Requests\Marketplace\GuardarPublicacionRequest;
use Features\Archivos\Domain\Contracts\AlmacenArchivos;
use Features\Marketplace\Domain\Entities\CategoriaProducto;
use Features\Marketplace\Domain\Entities\CondicionProducto;
use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Features\Marketplace\Domain\Entities\PublicacionVisible;
use Features\Marketplace\Domain\Exceptions\AccionMarketplaceNoPermitidaException;
use Features\Marketplace\Domain\Exceptions\MotivoModeracionRequeridoException;
use Features\Marketplace\Domain\Exceptions\PublicacionNoEditableException;
use Features\Marketplace\Domain\Exceptions\PublicacionNoEncontradaException;
use Features\Marketplace\Domain\UseCases\ActualizarPublicacionMarketplaceUseCase;
use Features\Marketplace\Domain\UseCases\AlternarFavoritoMarketplaceUseCase;
use Features\Marketplace\Domain\UseCases\CrearPublicacionMarketplaceUseCase;
use Features\Marketplace\Domain\UseCases\EliminarPublicacionMarketplaceUseCase;
use Features\Marketplace\Domain\UseCases\ListarFavoritosMarketplaceUseCase;
use Features\Marketplace\Domain\UseCases\ListarHistorialMarketplaceUseCase;
use Features\Marketplace\Domain\UseCases\ListarMisPublicacionesMarketplaceUseCase;
use Features\Marketplace\Domain\UseCases\ListarPublicacionesMarketplaceUseCase;
use Features\Marketplace\Domain\UseCases\MarcarPublicacionVendidaUseCase;
use Features\Marketplace\Domain\UseCases\ObtenerPublicacionMarketplaceUseCase;
use Features\Marketplace\Domain\UseCases\RetirarPublicacionMarketplaceUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

final class MarketplaceController extends Controller
{
    public function __construct(
        private readonly ListarPublicacionesMarketplaceUseCase $listar,
        private readonly ObtenerPublicacionMarketplaceUseCase $obtener,
        private readonly ListarMisPublicacionesMarketplaceUseCase $misPublicaciones,
        private readonly ListarHistorialMarketplaceUseCase $historial,
        private readonly ListarFavoritosMarketplaceUseCase $favoritos,
        private readonly CrearPublicacionMarketplaceUseCase $crear,
        private readonly ActualizarPublicacionMarketplaceUseCase $actualizar,
        private readonly RetirarPublicacionMarketplaceUseCase $retirar,
        private readonly MarcarPublicacionVendidaUseCase $vender,
        private readonly EliminarPublicacionMarketplaceUseCase $eliminar,
        private readonly AlternarFavoritoMarketplaceUseCase $alternarFavorito,
        private readonly AlmacenArchivos $almacen,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $proyectoId = $this->proyectoId($request);

        if ($proyectoId === null) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $datos = $request->validate([
            'categoria' => ['nullable', Rule::enum(CategoriaProducto::class)],
            'buscar' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in([
                'todas',
                ...array_map(static fn (EstadoPublicacion $estado): string => $estado->value, EstadoPublicacion::cases()),
            ])],
        ]);

        $todas = ($datos['estado'] ?? null) === 'todas';
        $estado = ! $todas && isset($datos['estado'])
            ? EstadoPublicacion::from((string) $datos['estado'])
            : null;
        $categoria = isset($datos['categoria'])
            ? CategoriaProducto::from((string) $datos['categoria'])
            : null;

        try {
            $publicaciones = $this->listar->execute(
                proyectoId: $proyectoId,
                usuarioId: $this->usuarioId($request),
                categoria: $categoria,
                buscar: $this->texto($datos['buscar'] ?? null),
                estado: $estado,
                todas: $todas,
            );
        } catch (Throwable $exception) {
            $respuesta = $this->respuestaError($exception);

            if ($respuesta !== null) {
                return $respuesta;
            }

            throw $exception;
        }

        return response()->json(['publicaciones' => $this->serializarLista($publicaciones)]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $proyectoId = $this->proyectoId($request);

        if ($proyectoId === null) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        try {
            $publicacion = $this->obtener->execute($id, $proyectoId, $this->usuarioId($request));
        } catch (Throwable $exception) {
            $respuesta = $this->respuestaError($exception);

            if ($respuesta !== null) {
                return $respuesta;
            }

            throw $exception;
        }

        return response()->json(['publicacion' => $this->serializar($publicacion)]);
    }

    public function misPublicaciones(Request $request): JsonResponse
    {
        return $this->listaSimple($request, fn (string $usuarioId, string $proyectoId) => $this->misPublicaciones->execute($usuarioId, $proyectoId));
    }

    public function historial(Request $request): JsonResponse
    {
        return $this->listaSimple($request, fn (string $usuarioId, string $proyectoId) => $this->historial->execute($usuarioId, $proyectoId));
    }

    public function favoritos(Request $request): JsonResponse
    {
        return $this->listaSimple($request, fn (string $usuarioId, string $proyectoId) => $this->favoritos->execute($usuarioId, $proyectoId));
    }

    public function store(GuardarPublicacionRequest $request): JsonResponse
    {
        $proyectoId = $this->proyectoId($request);

        if ($proyectoId === null) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $datos = $request->validated();
        $fotoPath = $this->guardarFoto($request, $proyectoId);

        try {
            $publicacion = $this->crear->execute(
                proyectoId: $proyectoId,
                usuarioId: $this->usuarioId($request),
                titulo: (string) $datos['titulo'],
                descripcion: (string) $datos['descripcion'],
                precio: (int) $datos['precio'],
                categoria: CategoriaProducto::from((string) $datos['categoria']),
                condicion: CondicionProducto::from((string) $datos['condicion']),
                fotoPath: $fotoPath,
            );
        } catch (Throwable $exception) {
            $this->almacen->eliminar($fotoPath);

            $respuesta = $this->respuestaError($exception);

            if ($respuesta !== null) {
                return $respuesta;
            }

            throw $exception;
        }

        return response()->json(['publicacion' => $this->serializar($publicacion)], 201);
    }

    public function update(GuardarPublicacionRequest $request, string $id): JsonResponse
    {
        $proyectoId = $this->proyectoId($request);

        if ($proyectoId === null) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $datos = $request->validated();
        $fotoNueva = $this->guardarFoto($request, $proyectoId);

        $cambios = [
            'titulo' => (string) $datos['titulo'],
            'descripcion' => (string) $datos['descripcion'],
            'precio' => (int) $datos['precio'],
            'categoria' => (string) $datos['categoria'],
            'condicion' => (string) $datos['condicion'],
        ];

        if ($fotoNueva !== null) {
            $cambios['foto_path'] = $fotoNueva;
        }

        try {
            $resultado = $this->actualizar->execute(
                $id,
                $proyectoId,
                $this->usuarioId($request),
                $cambios,
            );
        } catch (Throwable $exception) {
            $this->almacen->eliminar($fotoNueva);

            $respuesta = $this->respuestaError($exception);

            if ($respuesta !== null) {
                return $respuesta;
            }

            throw $exception;
        }

        if ($fotoNueva !== null) {
            $this->almacen->eliminar($resultado['fotoAnterior']);
        }

        return response()->json(['publicacion' => $this->serializar($resultado['publicacion'])]);
    }

    public function retirar(Request $request, string $id): JsonResponse
    {
        return $this->accionPropia($request, $id, fn (string $proyectoId, string $usuarioId) => $this->retirar->execute($id, $proyectoId, $usuarioId));
    }

    public function vender(Request $request, string $id): JsonResponse
    {
        return $this->accionPropia($request, $id, fn (string $proyectoId, string $usuarioId) => $this->vender->execute($id, $proyectoId, $usuarioId));
    }

    public function destroy(EliminarPublicacionRequest $request, string $id): JsonResponse
    {
        $proyectoId = $this->proyectoId($request);

        if ($proyectoId === null) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        try {
            $this->eliminar->execute(
                id: $id,
                proyectoId: $proyectoId,
                moderadorId: $this->usuarioId($request),
                motivo: (string) $request->validated('motivo'),
            );
        } catch (Throwable $exception) {
            $respuesta = $this->respuestaError($exception);

            if ($respuesta !== null) {
                return $respuesta;
            }

            throw $exception;
        }

        return response()->json(null, 204);
    }

    public function favorito(FavoritoRequest $request, string $id): JsonResponse
    {
        $proyectoId = $this->proyectoId($request);

        if ($proyectoId === null) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        try {
            $esFavorita = $this->alternarFavorito->execute(
                publicacionId: $id,
                proyectoId: $proyectoId,
                usuarioId: $this->usuarioId($request),
                favorito: (bool) $request->validated('favorito'),
            );
        } catch (Throwable $exception) {
            $respuesta = $this->respuestaError($exception);

            if ($respuesta !== null) {
                return $respuesta;
            }

            throw $exception;
        }

        return response()->json(['es_favorita' => $esFavorita]);
    }

    /**
     * @param  callable(string, string): array<int, PublicacionVisible>  $listado
     */
    private function listaSimple(Request $request, callable $listado): JsonResponse
    {
        $proyectoId = $this->proyectoId($request);

        if ($proyectoId === null) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        return response()->json([
            'publicaciones' => $this->serializarLista($listado($this->usuarioId($request), $proyectoId)),
        ]);
    }

    /**
     * @param  callable(string, string): PublicacionVisible  $accion
     */
    private function accionPropia(Request $request, string $id, callable $accion): JsonResponse
    {
        $proyectoId = $this->proyectoId($request);

        if ($proyectoId === null) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        try {
            $publicacion = $accion($proyectoId, $this->usuarioId($request));
        } catch (Throwable $exception) {
            $respuesta = $this->respuestaError($exception);

            if ($respuesta !== null) {
                return $respuesta;
            }

            throw $exception;
        }

        return response()->json(['publicacion' => $this->serializar($publicacion)]);
    }

    private function respuestaError(Throwable $exception): ?JsonResponse
    {
        $status = match (true) {
            $exception instanceof PublicacionNoEncontradaException => 404,
            $exception instanceof AccionMarketplaceNoPermitidaException => 403,
            $exception instanceof PublicacionNoEditableException => 409,
            $exception instanceof MotivoModeracionRequeridoException => 422,
            default => null,
        };

        return $status === null
            ? null
            : response()->json(['message' => $exception->getMessage()], $status);
    }

    private function proyectoId(Request $request): ?string
    {
        $proyectoId = $request->header('X-Proyecto-Id') ?? $request->input('proyecto_id');

        return $proyectoId === null || $proyectoId === '' ? null : (string) $proyectoId;
    }

    private function usuarioId(Request $request): string
    {
        return (string) $request->user()->getAuthIdentifier();
    }

    private function guardarFoto(Request $request, string $proyectoId): ?string
    {
        $archivo = $request->file('foto');

        if ($archivo === null) {
            return null;
        }

        return $this->almacen->guardar(
            $archivo->get(),
            $archivo->getClientOriginalName(),
            "marketplace/{$proyectoId}",
        );
    }

    private function texto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    /**
     * @param  PublicacionVisible[]  $publicaciones
     * @return array<int, array<string, mixed>>
     */
    private function serializarLista(array $publicaciones): array
    {
        return array_map(fn (PublicacionVisible $publicacion) => $this->serializar($publicacion), $publicaciones);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(PublicacionVisible $visible): array
    {
        $publicacion = $visible->publicacion();

        return [
            'id' => $publicacion->id(),
            'titulo' => $publicacion->titulo(),
            'descripcion' => $publicacion->descripcion(),
            'precio' => $publicacion->precio(),
            'categoria' => $publicacion->categoria()->value,
            'categoria_etiqueta' => $publicacion->categoria()->etiqueta(),
            'condicion' => $publicacion->condicion()->value,
            'condicion_etiqueta' => $publicacion->condicion()->etiqueta(),
            'estado' => $publicacion->estado()->value,
            'estado_etiqueta' => $publicacion->estado()->etiqueta(),
            'foto_url' => $this->almacen->url($publicacion->fotoPath()),
            'es_mia' => $visible->esMia(),
            'es_favorita' => $visible->esFavorita(),
            'puede_editar' => $visible->puedeEditar(),
            'puede_eliminar' => $visible->puedeEliminar(),
            'puede_retirar' => $visible->puedeRetirar(),
            'puede_marcar_vendida' => $visible->puedeMarcarVendida(),
            'contacto_disponible' => false,
            'vendido_en' => $publicacion->vendidoEn(),
            'motivo_eliminacion' => $publicacion->motivoEliminacion(),
            'eliminado_por_rol' => $publicacion->eliminadoPorRol(),
            'eliminado_en' => $publicacion->eliminadoEn(),
            'creado_en' => $publicacion->creadoEn(),
        ];
    }
}
