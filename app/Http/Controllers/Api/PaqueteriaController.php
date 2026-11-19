<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Features\Acceso\Domain\Entities\UnidadContexto;
use Features\Acceso\Domain\Services\AccesoProyectoService;
use Features\Acceso\Domain\UseCases\ListarContextosUnidadesUseCase;
use Features\Archivos\Domain\Contracts\AlmacenArchivos;
use Features\Paqueteria\Domain\Entities\Paquete;
use Features\Paqueteria\Domain\Exceptions\PaqueteNoEncontradoException;
use Features\Paqueteria\Domain\Exceptions\PaqueteYaRetiradoException;
use Features\Paqueteria\Domain\Exceptions\ResidenteNoCorrespondeException;
use Features\Paqueteria\Domain\Exceptions\UnidadNoEncontradaException;
use Features\Paqueteria\Domain\Exceptions\UnidadNoPerteneceAlProyectoException;
use Features\Paqueteria\Domain\UseCases\ActualizarPaqueteUseCase;
use Features\Paqueteria\Domain\UseCases\ListarPaquetesUseCase;
use Features\Paqueteria\Domain\UseCases\MarcarPaqueteRetiradoUseCase;
use Features\Paqueteria\Domain\UseCases\ObtenerPaqueteUseCase;
use Features\Paqueteria\Domain\UseCases\RegistrarPaqueteUseCase;
use Features\Paqueteria\Domain\UseCases\ResolverDestinoPaqueteUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class PaqueteriaController extends Controller
{
    public function __construct(
        private readonly RegistrarPaqueteUseCase $registrarUseCase,
        private readonly ListarPaquetesUseCase $listarUseCase,
        private readonly MarcarPaqueteRetiradoUseCase $marcarRetiradoUseCase,
        private readonly ActualizarPaqueteUseCase $actualizarUseCase,
        private readonly ObtenerPaqueteUseCase $obtenerUseCase,
        private readonly ResolverDestinoPaqueteUseCase $resolverDestinoUseCase,
        private readonly ListarContextosUnidadesUseCase $listarUnidadesUseCase,
        private readonly AccesoProyectoService $acceso,
        private readonly AlmacenArchivos $almacen,
    ) {}

    public function registrar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proyecto_id' => ['required', 'integer', 'exists:proyectos,id'],
            'unidad_principal_id' => ['required', 'integer', 'exists:unidades_principales,id'],
            'residente_id' => ['nullable', 'integer', 'exists:usuarios,id'],
            'descripcion' => ['required', 'string', 'max:500'],
            'remitente' => ['required', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'max:5120'],
        ]);

        $usuarioId = (string) $request->user()->getAuthIdentifier();

        try {
            $destino = $this->resolverDestinoUseCase->execute(
                $usuarioId,
                (string) $validated['unidad_principal_id'],
                (string) $validated['proyecto_id'],
                isset($validated['residente_id']) ? (string) $validated['residente_id'] : null,
            );
        } catch (UnidadNoEncontradaException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        } catch (UnidadNoPerteneceAlProyectoException|ResidenteNoCorrespondeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $foto = $request->hasFile('foto') ? $request->file('foto') : null;
        $fotoPath = $foto !== null
            ? $this->almacen->guardar($foto->get(), $foto->getClientOriginalName(), 'paquetes')
            : null;

        try {
            $paquete = $this->registrarUseCase->execute([
                'proyecto_id' => $destino->proyectoId(),
                'unidad_principal_id' => $destino->unidadPrincipalId(),
                'residente_id' => $destino->residenteId(),
                'descripcion' => $validated['descripcion'],
                'remitente' => $validated['remitente'],
                'foto_path' => $fotoPath,
                'registrado_por' => $usuarioId,
            ]);
        } catch (Throwable $exception) {
            if ($fotoPath !== null) {
                $this->almacen->eliminar($fotoPath);
            }

            throw $exception;
        }

        return response()->json($this->serializar($paquete), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $proyectoId = $this->proyectoIdSolicitado($request);

        $paquetes = $this->listarUseCase->execute(
            (string) $usuario->getAuthIdentifier(),
            $usuario->rol,
            $proyectoId,
        );

        return response()->json([
            'paquetes' => array_map(fn (Paquete $p) => $this->serializar($p), $paquetes),
        ]);
    }

    public function unidades(Request $request): JsonResponse
    {
        $usuarioId = (string) $request->user()->getAuthIdentifier();
        $proyectoIds = $this->acceso->esSuperadmin($usuarioId)
            ? null
            : $this->acceso->proyectosIdsDe($usuarioId);

        $unidades = $this->listarUnidadesUseCase->execute($proyectoIds);

        return response()->json([
            'unidades' => array_map(
                static fn (UnidadContexto $u) => [
                    'id' => (int) $u->unidadPrincipalId(),
                    'identificador' => $u->identificador(),
                    'agrupacion' => $u->agrupacionNombre(),
                    'proyecto_id' => (int) $u->proyectoId(),
                    'residente_id' => $u->ocupanteId() !== null ? (int) $u->ocupanteId() : null,
                    'propietario_id' => $u->propietarioId() !== null ? (int) $u->propietarioId() : null,
                    'propietario_nombre' => $u->propietarioNombre(),
                    'ocupante_id' => $u->ocupanteId() !== null ? (int) $u->ocupanteId() : null,
                    'ocupante_nombre' => $u->ocupanteNombre(),
                ],
                $unidades,
            ),
        ]);
    }

    public function actualizar(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'descripcion' => ['sometimes', 'required', 'string', 'max:500'],
            'remitente' => ['required', 'string', 'max:255'],
            'residente_id' => ['nullable', 'integer', 'exists:usuarios,id'],
            'unidad_principal_id' => ['required', 'integer', 'exists:unidades_principales,id'],
            'foto' => ['nullable', 'image', 'max:5120'],
        ]);

        $usuarioId = (string) $request->user()->getAuthIdentifier();
        $paqueteActual = $this->obtenerUseCase->execute($usuarioId, $id);
        if ($paqueteActual === null) {
            return response()->json(['message' => 'Paquete no encontrado.'], 404);
        }

        try {
            $destino = $this->resolverDestinoUseCase->execute(
                $usuarioId,
                (string) $validated['unidad_principal_id'],
                $paqueteActual->proyectoId(),
                isset($validated['residente_id']) ? (string) $validated['residente_id'] : null,
            );
        } catch (UnidadNoEncontradaException) {
            return response()->json(['message' => 'Unidad no encontrada.'], 404);
        } catch (UnidadNoPerteneceAlProyectoException) {
            return response()->json(['message' => 'La unidad debe pertenecer al proyecto del paquete.'], 422);
        } catch (ResidenteNoCorrespondeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $datos = [];
        if (array_key_exists('descripcion', $validated)) {
            $datos['descripcion'] = $validated['descripcion'];
        }
        if (array_key_exists('remitente', $validated)) {
            $datos['remitente'] = $validated['remitente'];
        }
        $datos['residente_id'] = $destino->residenteId();
        $datos['unidad_principal_id'] = $destino->unidadPrincipalId();

        $foto = $request->hasFile('foto') ? $request->file('foto') : null;
        $fotoPath = $foto !== null
            ? $this->almacen->guardar($foto->get(), $foto->getClientOriginalName(), 'paquetes')
            : null;
        if ($fotoPath !== null) {
            $datos['foto_path'] = $fotoPath;
        }

        try {
            $paquete = $this->actualizarUseCase->execute($id, $datos);
        } catch (PaqueteNoEncontradoException) {
            if ($fotoPath !== null) {
                $this->almacen->eliminar($fotoPath);
            }

            return response()->json(['message' => 'Paquete no encontrado.'], 404);
        } catch (PaqueteYaRetiradoException $exception) {
            if ($fotoPath !== null) {
                $this->almacen->eliminar($fotoPath);
            }

            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            if ($fotoPath !== null) {
                $this->almacen->eliminar($fotoPath);
            }
            throw $exception;
        }

        return response()->json($this->serializar($paquete));
    }

    public function retirar(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'retirado_por_nombre' => ['required', 'string', 'max:255'],
        ]);

        $usuarioId = (string) $request->user()->getAuthIdentifier();

        if ($this->obtenerUseCase->execute($usuarioId, $id) === null) {
            return response()->json(['message' => 'Paquete no encontrado.'], 404);
        }

        try {
            $paquete = $this->marcarRetiradoUseCase->execute(
                $id,
                trim($validated['retirado_por_nombre']),
            );
        } catch (PaqueteNoEncontradoException) {
            return response()->json(['message' => 'Paquete no encontrado.'], 404);
        }

        return response()->json($this->serializar($paquete));
    }

    private function proyectoIdSolicitado(Request $request): ?string
    {
        $proyectoId = $request->header('X-Proyecto-Id') ?? $request->input('proyecto_id');

        return $proyectoId === null || $proyectoId === '' ? null : (string) $proyectoId;
    }

    /** @return array<string, mixed> */
    private function serializar(Paquete $paquete): array
    {
        return [
            'id' => $paquete->id(),
            'proyecto_id' => $paquete->proyectoId(),
            'unidad_principal_id' => $paquete->unidadPrincipalId(),
            'unidad' => $paquete->unidadIdentificador(),
            'residente_id' => $paquete->residenteId(),
            'descripcion' => $paquete->descripcion(),
            'remitente' => $paquete->remitente(),
            'foto_url' => $paquete->fotoPath() !== null
                ? $this->almacen->url($paquete->fotoPath())
                : null,
            'fecha_recepcion' => $paquete->fechaRecepcion(),
            'estado' => $paquete->estado(),
            'fecha_retiro' => $paquete->fechaRetiro(),
            'retirado_por_nombre' => $paquete->retiradoPorNombre(),
        ];
    }
}
