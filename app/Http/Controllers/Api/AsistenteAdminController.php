<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asistente\SubirDocumentoRequest;
use Features\Archivos\Domain\Contracts\AlmacenArchivos;
use Features\Asistente\Domain\Entities\Documento;
use Features\Asistente\Domain\Exceptions\DocumentoMuyGrandeException;
use Features\Asistente\Domain\Exceptions\DocumentoNoDisponibleException;
use Features\Asistente\Domain\Exceptions\DocumentoYaExisteException;
use Features\Asistente\Domain\Exceptions\ProyectoNoDisponibleException;
use Features\Asistente\Domain\UseCases\EliminarDocumentoUseCase;
use Features\Asistente\Domain\UseCases\ObtenerDocumentoUseCase;
use Features\Asistente\Domain\UseCases\ResolverProyectoAdministradorUseCase;
use Features\Asistente\Domain\UseCases\SubirDocumentoUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AsistenteAdminController extends Controller
{
    public function __construct(
        private readonly ResolverProyectoAdministradorUseCase $resolverProyecto,
        private readonly ObtenerDocumentoUseCase $obtenerDocumento,
        private readonly SubirDocumentoUseCase $subirDocumento,
        private readonly EliminarDocumentoUseCase $eliminarDocumento,
        private readonly AlmacenArchivos $almacen,
    ) {}

    public function mostrar(Request $request): JsonResponse
    {
        try {
            $proyectoId = $this->resolverProyecto->execute(
                (string) $request->user()->getAuthIdentifier(),
                $this->proyectoIdSolicitado($request),
            );
        } catch (ProyectoNoDisponibleException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        $documento = $this->obtenerDocumento->execute($proyectoId);

        return response()->json([
            'documento' => $documento === null ? null : $this->serializar($documento),
        ]);
    }

    public function subir(SubirDocumentoRequest $request): JsonResponse
    {
        try {
            $proyectoId = $this->resolverProyecto->execute(
                (string) $request->user()->getAuthIdentifier(),
                $this->proyectoIdSolicitado($request),
            );
        } catch (ProyectoNoDisponibleException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        if ($this->obtenerDocumento->execute($proyectoId) !== null) {
            return response()->json([
                'message' => 'Este proyecto ya tiene un documento cargado. Elimínalo antes de subir otro.',
            ], 409);
        }

        $archivo = $request->file('archivo');
        $nombreOriginal = $archivo->getClientOriginalName();
        $rutaAlmacenada = $this->almacen->guardar(
            $archivo->get(),
            $nombreOriginal,
            "proyectos/{$proyectoId}",
        );
        $rutaFisica = $this->almacen->rutaFisica($rutaAlmacenada);

        if ($rutaFisica === null) {
            $this->almacen->eliminar($rutaAlmacenada);

            return response()->json(['message' => 'No se pudo procesar el documento.'], 422);
        }

        try {
            $documento = $this->subirDocumento->execute(
                proyectoId: $proyectoId,
                subidoPor: (string) $request->user()->getAuthIdentifier(),
                nombreOriginal: $nombreOriginal,
                rutaFisica: $rutaFisica,
                rutaAlmacenada: $rutaAlmacenada,
            );
        } catch (DocumentoYaExisteException $exception) {
            $this->eliminarArchivo($rutaAlmacenada);

            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (DocumentoMuyGrandeException $exception) {
            $this->eliminarArchivo($rutaAlmacenada);

            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            $this->eliminarArchivo($rutaAlmacenada);

            throw $exception;
        }

        return response()->json(['documento' => $this->serializar($documento)], 201);
    }

    public function eliminar(Request $request): JsonResponse
    {
        try {
            $proyectoId = $this->resolverProyecto->execute(
                (string) $request->user()->getAuthIdentifier(),
                $this->proyectoIdSolicitado($request),
            );
            $this->eliminarDocumento->execute($proyectoId);
        } catch (ProyectoNoDisponibleException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        } catch (DocumentoNoDisponibleException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json(null, 204);
    }

    private function proyectoIdSolicitado(Request $request): ?string
    {
        $proyectoId = $request->header('X-Proyecto-Id') ?? $request->input('proyecto_id');

        return $proyectoId === null || $proyectoId === '' ? null : (string) $proyectoId;
    }

    private function eliminarArchivo(string $rutaAlmacenada): void
    {
        $this->almacen->eliminar($rutaAlmacenada);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(Documento $documento): array
    {
        return [
            'id' => $documento->id(),
            'nombre' => $documento->nombreOriginal(),
            'paginas' => $documento->paginas(),
            'tamano_tokens' => $documento->tamanoTokens(),
        ];
    }
}
