<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Infraestructura\AsignarUnidadAccesoriaRequest;
use App\Http\Requests\Infraestructura\CrearProyectoRequest;
use App\Http\Requests\Infraestructura\CrearUnidadAccesoriaRequest;
use App\Http\Requests\Infraestructura\CrearUnidadesAccesoriasLoteRequest;
use App\Http\Requests\Infraestructura\RegistrarAgrupacionRequest;
use Features\Infraestructura\Domain\Entities\Agrupacion;
use Features\Infraestructura\Domain\Entities\ArbolAgrupacion;
use Features\Infraestructura\Domain\Entities\ArbolInfraestructura;
use Features\Infraestructura\Domain\Entities\ArbolUnidadPrincipal;
use Features\Infraestructura\Domain\Entities\Proyecto;
use Features\Infraestructura\Domain\Entities\UnidadAccesoria;
use Features\Infraestructura\Domain\Entities\UnidadPrincipal;
use Features\Infraestructura\Domain\Exceptions\RecursoDuplicadoException;
use Features\Infraestructura\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Infraestructura\Domain\UseCases\AsignarUnidadAccesoriaUseCase;
use Features\Infraestructura\Domain\UseCases\CrearProyectoUseCase;
use Features\Infraestructura\Domain\UseCases\CrearUnidadAccesoriaUseCase;
use Features\Infraestructura\Domain\UseCases\CrearUnidadesAccesoriasEnLoteUseCase;
use Features\Infraestructura\Domain\UseCases\GeneradorIdentificacionesUnidades;
use Features\Infraestructura\Domain\UseCases\ObtenerArbolInfraestructuraUseCase;
use Features\Infraestructura\Domain\UseCases\RegistrarAgrupacionEnCascadaUseCase;
use Features\Infraestructura\Domain\UseCases\ResolverProyectoActivoUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class InfraestructuraController extends Controller
{
    public function __construct(
        private readonly CrearProyectoUseCase $crearProyectoUseCase,
        private readonly RegistrarAgrupacionEnCascadaUseCase $registrarAgrupacionEnCascadaUseCase,
        private readonly CrearUnidadAccesoriaUseCase $crearUnidadAccesoriaUseCase,
        private readonly CrearUnidadesAccesoriasEnLoteUseCase $crearUnidadesAccesoriasEnLoteUseCase,
        private readonly AsignarUnidadAccesoriaUseCase $asignarUnidadAccesoriaUseCase,
        private readonly ObtenerArbolInfraestructuraUseCase $obtenerArbolUseCase,
        private readonly ResolverProyectoActivoUseCase $resolverProyectoActivoUseCase,
        private readonly GeneradorIdentificacionesUnidades $generador,
    ) {}

    public function miProyecto(Request $request): JsonResponse
    {
        try {
            $proyecto = $this->resolverProyectoActivoUseCase->execute(
                (string) auth()->id(),
                $this->proyectoIdSolicitado($request),
            );
        } catch (RecursoNoEncontradoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json($this->serializarProyecto($proyecto));
    }

    public function crearProyecto(CrearProyectoRequest $request): JsonResponse
    {
        try {
            $proyecto = $this->crearProyectoUseCase->execute(
                $request->string('nombre')->toString(),
                $request->input('descripcion'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RecursoDuplicadoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json($this->serializarProyecto($proyecto), 201);
    }

    public function crearAgrupacion(RegistrarAgrupacionRequest $request): JsonResponse
    {
        try {
            $agrupacion = $this->registrarAgrupacionEnCascadaUseCase->execute(
                $this->resolverProyectoActivoUseCase->execute(
                    (string) auth()->id(),
                    $this->proyectoIdSolicitado($request),
                )->id(),
                $request->string('nombre')->toString(),
                $request->string('tipo')->toString(),
                $request->input('prefijo'),
                (int) $request->input('piso_desde'),
                (int) $request->input('piso_hasta'),
                (int) $request->input('unidades_por_piso'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RecursoDuplicadoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (RecursoNoEncontradoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json($this->serializarAgrupacion($agrupacion), 201);
    }

    public function crearUnidadAccesoria(CrearUnidadAccesoriaRequest $request): JsonResponse
    {
        try {
            $unidad = $this->crearUnidadAccesoriaUseCase->execute(
                $this->resolverProyectoActivoUseCase->execute(
                    (string) auth()->id(),
                    $this->proyectoIdSolicitado($request),
                )->id(),
                $request->string('identificador')->toString(),
                $request->string('tipo')->toString(),
                $this->asignacionSolicitada($request),
                $request->input('unidad_principal_id'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RecursoDuplicadoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (RecursoNoEncontradoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json($this->serializarUnidadAccesoria($unidad), 201);
    }

    public function crearUnidadesAccesoriasEnLote(CrearUnidadesAccesoriasLoteRequest $request): JsonResponse
    {
        try {
            $unidades = $this->crearUnidadesAccesoriasEnLoteUseCase->execute(
                $this->resolverProyectoActivoUseCase->execute(
                    (string) auth()->id(),
                    $this->proyectoIdSolicitado($request),
                )->id(),
                $request->string('tipo')->toString(),
                (int) $request->input('cantidad'),
                $this->asignacionSolicitada($request),
                $request->input('unidad_principal_id'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RecursoDuplicadoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (RecursoNoEncontradoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json([
            'unidades' => array_map(
                fn (UnidadAccesoria $unidad) => $this->serializarUnidadAccesoria($unidad),
                $unidades,
            ),
        ], 201);
    }

    public function asignarUnidadAccesoria(AsignarUnidadAccesoriaRequest $request, string $unidadAccesoriaId): JsonResponse
    {
        try {
            $unidad = $this->asignarUnidadAccesoriaUseCase->execute(
                $unidadAccesoriaId,
                $this->asignacionSolicitada($request),
                $request->input('unidad_principal_id'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RecursoNoEncontradoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json($this->serializarUnidadAccesoria($unidad));
    }

    public function arbol(Request $request): JsonResponse
    {
        try {
            $arbol = $this->obtenerArbolUseCase->execute(
                $this->resolverProyectoActivoUseCase->execute(
                    (string) auth()->id(),
                    $this->proyectoIdSolicitado($request),
                )->id(),
            );
        } catch (RecursoNoEncontradoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json($this->serializarArbol($arbol));
    }

    public function vistaPreviaIdentificaciones(Request $request): JsonResponse
    {
        try {
            $identificaciones = $this->generador->generar(
                $request->input('prefijo'),
                (int) $request->input('piso_desde'),
                (int) $request->input('piso_hasta'),
                (int) $request->input('unidades_por_piso'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['identificadores' => $identificaciones]);
    }

    private function proyectoIdSolicitado(Request $request): ?string
    {
        return $request->header('X-Proyecto-Id') ?? $request->input('proyecto_id');
    }

    private function asignacionSolicitada(Request $request): ?string
    {
        $asignacion = $request->input('asignacion');

        if (is_string($asignacion) && $asignacion !== '') {
            return $asignacion;
        }

        return $request->input('unidad_principal_id') !== null ? 'departamento' : null;
    }

    private function serializarProyecto(Proyecto $proyecto): array
    {
        return [
            'id' => $proyecto->id(),
            'nombre' => $proyecto->nombre(),
            'descripcion' => $proyecto->descripcion(),
        ];
    }

    private function serializarAgrupacion(Agrupacion $agrupacion): array
    {
        return [
            'id' => $agrupacion->id(),
            'proyecto_id' => $agrupacion->proyectoId(),
            'nombre' => $agrupacion->nombre(),
            'tipo' => $agrupacion->tipo()->value,
        ];
    }

    private function serializarUnidadPrincipal(UnidadPrincipal $unidad): array
    {
        return [
            'id' => $unidad->id(),
            'agrupacion_id' => $unidad->agrupacionId(),
            'identificador' => $unidad->identificador(),
        ];
    }

    private function serializarUnidadAccesoria(UnidadAccesoria $unidad): array
    {
        return [
            'id' => $unidad->id(),
            'proyecto_id' => $unidad->proyectoId(),
            'identificador' => $unidad->identificador(),
            'tipo' => $unidad->tipo()->value,
            'asignacion' => $unidad->asignacion()?->value,
            'unidad_principal_id' => $unidad->unidadPrincipalId(),
        ];
    }

    private function serializarArbol(ArbolInfraestructura $arbol): array
    {
        return [
            'proyecto' => $this->serializarProyecto($arbol->proyecto()),
            'unidades_accesorias' => array_map(
                fn (UnidadAccesoria $accesoria) => $this->serializarUnidadAccesoria($accesoria),
                $arbol->unidadesAccesorias(),
            ),
            'agrupaciones' => array_map(
                fn (ArbolAgrupacion $arbolAgrupacion): array => [
                    ...$this->serializarAgrupacion($arbolAgrupacion->agrupacion()),
                    'unidades_principales' => array_map(
                        fn (ArbolUnidadPrincipal $arbolUnidad): array => [
                            ...$this->serializarUnidadPrincipal($arbolUnidad->unidadPrincipal()),
                            'unidades_accesorias' => array_map(
                                fn (UnidadAccesoria $accesoria) => $this->serializarUnidadAccesoria($accesoria),
                                $arbolUnidad->unidadesAccesorias(),
                            ),
                        ],
                        $arbolAgrupacion->unidadesPrincipales(),
                    ),
                ],
                $arbol->agrupaciones(),
            ),
        ];
    }
}
