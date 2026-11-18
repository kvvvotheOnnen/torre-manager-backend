<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\GuardarTrabajadorRequest;
use Features\Archivos\Domain\Contracts\AlmacenArchivos;
use Features\Servicios\Domain\Entities\TipoServicio;
use Features\Servicios\Domain\Entities\TrabajadorServicio;
use Features\Servicios\Domain\Exceptions\TrabajadorNoEncontradoException;
use Features\Servicios\Domain\UseCases\ActualizarTrabajadorUseCase;
use Features\Servicios\Domain\UseCases\CrearTrabajadorUseCase;
use Features\Servicios\Domain\UseCases\EliminarTrabajadorUseCase;
use Features\Servicios\Domain\UseCases\ListarTrabajadoresUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiciosController extends Controller
{
    public function __construct(
        private readonly ListarTrabajadoresUseCase $listar,
        private readonly CrearTrabajadorUseCase $crear,
        private readonly ActualizarTrabajadorUseCase $actualizar,
        private readonly EliminarTrabajadorUseCase $eliminar,
        private readonly AlmacenArchivos $almacen,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $proyectoId = $this->proyectoId($request);

        if ($proyectoId === null) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $trabajadores = $this->listar->execute(
            $proyectoId,
            $this->texto($request->query('tipo')),
            $this->texto($request->query('buscar')),
        );

        return response()->json([
            'trabajadores' => array_map(fn (TrabajadorServicio $trabajador) => $this->serializar($trabajador), $trabajadores),
        ]);
    }

    public function store(GuardarTrabajadorRequest $request): JsonResponse
    {
        $proyectoId = $this->proyectoId($request);

        if ($proyectoId === null) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $datos = $request->validated();
        $fotoPath = $this->guardarFoto($request, $proyectoId);

        try {
            $trabajador = $this->crear->execute(
                proyectoId: $proyectoId,
                creadoPor: (string) $request->user()->getAuthIdentifier(),
                nombre: (string) $datos['nombre'],
                nombreEmpresa: $this->texto($datos['nombre_empresa'] ?? null),
                telefono: (string) $datos['telefono'],
                tipo: TipoServicio::from((string) $datos['tipo_servicio']),
                descripcion: $this->texto($datos['descripcion'] ?? null),
                fotoPath: $fotoPath,
            );
        } catch (\Throwable $exception) {
            $this->almacen->eliminar($fotoPath);

            throw $exception;
        }

        return response()->json(['trabajador' => $this->serializar($trabajador)], 201);
    }

    public function update(GuardarTrabajadorRequest $request, string $id): JsonResponse
    {
        $proyectoId = $this->proyectoId($request);

        if ($proyectoId === null) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $datos = $request->validated();
        $fotoNueva = $this->guardarFoto($request, $proyectoId);

        $cambios = [
            'nombre' => (string) $datos['nombre'],
            'nombre_empresa' => $this->texto($datos['nombre_empresa'] ?? null),
            'telefono' => (string) $datos['telefono'],
            'tipo_servicio' => (string) $datos['tipo_servicio'],
            'descripcion' => $this->texto($datos['descripcion'] ?? null),
        ];

        if ($fotoNueva !== null) {
            $cambios['foto_path'] = $fotoNueva;
        }

        try {
            $resultado = $this->actualizar->execute($id, $proyectoId, $cambios);
        } catch (TrabajadorNoEncontradoException $exception) {
            $this->almacen->eliminar($fotoNueva);

            return response()->json(['message' => $exception->getMessage()], 404);
        } catch (\Throwable $exception) {
            $this->almacen->eliminar($fotoNueva);

            throw $exception;
        }

        if ($fotoNueva !== null) {
            $this->almacen->eliminar($resultado['fotoAnterior']);
        }

        return response()->json(['trabajador' => $this->serializar($resultado['trabajador'])]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $proyectoId = $this->proyectoId($request);

        if ($proyectoId === null) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        try {
            $fotoPath = $this->eliminar->execute($id, $proyectoId);
        } catch (TrabajadorNoEncontradoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        $this->almacen->eliminar($fotoPath);

        return response()->json(null, 204);
    }

    private function proyectoId(Request $request): ?string
    {
        $proyectoId = $request->header('X-Proyecto-Id') ?? $request->input('proyecto_id');

        return $proyectoId === null || $proyectoId === '' ? null : (string) $proyectoId;
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
            "servicios/{$proyectoId}",
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
     * @return array<string, mixed>
     */
    private function serializar(TrabajadorServicio $trabajador): array
    {
        return [
            'id' => $trabajador->id(),
            'nombre' => $trabajador->nombre(),
            'nombre_empresa' => $trabajador->nombreEmpresa(),
            'telefono' => $trabajador->telefono(),
            'tipo_servicio' => $trabajador->tipo()->value,
            'tipo_etiqueta' => $trabajador->tipo()->etiqueta(),
            'descripcion' => $trabajador->descripcion(),
            'foto_url' => $this->almacen->url($trabajador->fotoPath()),
        ];
    }
}
