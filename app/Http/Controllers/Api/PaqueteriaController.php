<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paquete as PaqueteModel;
use App\Models\UnidadPrincipal;
use App\Models\User;
use Features\Paqueteria\Domain\Exceptions\PaqueteNoEncontradoException;
use Features\Paqueteria\Domain\Exceptions\PaqueteYaRetiradoException;
use Features\Paqueteria\Domain\UseCases\ActualizarPaqueteUseCase;
use Features\Paqueteria\Domain\UseCases\ListarPaquetesUseCase;
use Features\Paqueteria\Domain\UseCases\MarcarPaqueteRetiradoUseCase;
use Features\Paqueteria\Domain\UseCases\RegistrarPaqueteUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class PaqueteriaController extends Controller
{
    public function __construct(
        private readonly RegistrarPaqueteUseCase $registrarUseCase,
        private readonly ListarPaquetesUseCase $listarUseCase,
        private readonly MarcarPaqueteRetiradoUseCase $marcarRetiradoUseCase,
        private readonly ActualizarPaqueteUseCase $actualizarUseCase,
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

        $unidad = UnidadPrincipal::query()
            ->with('agrupacion')
            ->findOrFail($validated['unidad_principal_id']);
        $proyectoId = (int) $unidad->agrupacion->proyecto_id;
        $usuario = $request->user();

        if (! $this->puedeAccederAProyecto($usuario, $proyectoId)) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }
        if ((int) $validated['proyecto_id'] !== $proyectoId) {
            return response()->json(['message' => 'La unidad no pertenece al proyecto indicado.'], 422);
        }
        if (isset($validated['residente_id'])
            && (int) $validated['residente_id'] !== (int) $unidad->residente_id) {
            return response()->json(['message' => 'El residente no corresponde a la unidad indicada.'], 422);
        }

        $fotoPath = $request->hasFile('foto')
            ? $request->file('foto')->store('paquetes', 'public')
            : null;

        try {
            $paquete = $this->registrarUseCase->execute([
                'proyecto_id' => $proyectoId,
                'unidad_principal_id' => $unidad->getKey(),
                'residente_id' => $unidad->residente_id,
                'descripcion' => $validated['descripcion'],
                'remitente' => $validated['remitente'],
                'foto_path' => $fotoPath,
                'registrado_por' => $usuario->getAuthIdentifier(),
            ]);
        } catch (Throwable $exception) {
            if ($fotoPath !== null) {
                Storage::disk('public')->delete($fotoPath);
            }

            throw $exception;
        }

        return response()->json([
            'id' => $paquete->id(),
            'proyecto_id' => $paquete->proyectoId(),
            'unidad_principal_id' => $paquete->unidadPrincipalId(),
            'unidad' => $paquete->unidadIdentificador(),
            'residente_id' => $paquete->residenteId(),
            'descripcion' => $paquete->descripcion(),
            'remitente' => $paquete->remitente(),
            'foto_url' => $paquete->fotoPath() !== null
                ? Storage::disk('public')->url($paquete->fotoPath())
                : null,
            'fecha_recepcion' => $paquete->fechaRecepcion(),
            'estado' => $paquete->estado(),
            'retirado_por_nombre' => $paquete->retiradoPorNombre(),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proyecto_id' => ['sometimes', 'integer', 'exists:proyectos,id'],
        ]);
        $usuario = $request->user();
        $proyectoId = $validated['proyecto_id'] ?? null;

        if ($proyectoId !== null && ! $this->puedeAccederAProyecto($usuario, (int) $proyectoId)) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $paquetes = $this->listarUseCase->execute(
            (string) $usuario->getAuthIdentifier(),
            $usuario->rol,
            $proyectoId === null ? null : (string) $proyectoId,
        );

        return response()->json([
            'paquetes' => array_map(
                static fn ($p) => [
                    'id' => $p->id(),
                    'proyecto_id' => $p->proyectoId(),
                    'unidad_principal_id' => $p->unidadPrincipalId(),
                    'unidad' => $p->unidadIdentificador(),
                    'residente_id' => $p->residenteId(),
                    'descripcion' => $p->descripcion(),
                    'remitente' => $p->remitente(),
                    'foto_url' => $p->fotoPath() !== null
                        ? Storage::disk('public')->url($p->fotoPath())
                        : null,
                    'fecha_recepcion' => $p->fechaRecepcion(),
                    'estado' => $p->estado(),
                    'fecha_retiro' => $p->fechaRetiro(),
                    'retirado_por_nombre' => $p->retiradoPorNombre(),
                ],
                $paquetes,
            ),
        ]);
    }

    public function unidades(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $proyectoIds = $this->proyectosAccesibles($usuario);

        $query = UnidadPrincipal::query()
            ->with('agrupacion')
            ->orderBy('identificador');

        if ($usuario->rol !== 'superadmin') {
            $query->whereHas('agrupacion', fn ($q) => $q->whereIn('proyecto_id', $proyectoIds));
        }

        $unidades = $query->get(['id', 'agrupacion_id', 'identificador', 'residente_id']);

        return response()->json([
            'unidades' => $unidades->map(fn ($u) => [
                'id' => $u->getKey(),
                'identificador' => $u->identificador,
                'agrupacion' => $u->agrupacion?->nombre,
                'proyecto_id' => $u->agrupacion?->proyecto_id,
                'residente_id' => $u->residente_id,
            ])->values(),
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

        $usuario = $request->user();
        $paqueteActual = $this->paqueteAccesible($usuario, $id);
        if ($paqueteActual === null) {
            return response()->json(['message' => 'Paquete no encontrado.'], 404);
        }

        $unidad = UnidadPrincipal::query()
            ->with('agrupacion')
            ->findOrFail($validated['unidad_principal_id']);
        if ((int) $unidad->agrupacion->proyecto_id !== (int) $paqueteActual->proyecto_id) {
            return response()->json(['message' => 'La unidad debe pertenecer al proyecto del paquete.'], 422);
        }
        if (isset($validated['residente_id'])
            && (int) $validated['residente_id'] !== (int) $unidad->residente_id) {
            return response()->json(['message' => 'El residente no corresponde a la unidad indicada.'], 422);
        }

        $datos = [];
        if (array_key_exists('descripcion', $validated)) {
            $datos['descripcion'] = $validated['descripcion'];
        }
        if (array_key_exists('remitente', $validated)) {
            $datos['remitente'] = $validated['remitente'];
        }
        $datos['residente_id'] = $unidad->residente_id;
        $datos['unidad_principal_id'] = $unidad->getKey();

        $fotoPath = $request->hasFile('foto')
            ? $request->file('foto')->store('paquetes', 'public')
            : null;
        if ($fotoPath !== null) {
            $datos['foto_path'] = $fotoPath;
        }

        try {
            $paquete = $this->actualizarUseCase->execute($id, $datos);
        } catch (PaqueteNoEncontradoException) {
            if ($fotoPath !== null) {
                Storage::disk('public')->delete($fotoPath);
            }

            return response()->json(['message' => 'Paquete no encontrado.'], 404);
        } catch (PaqueteYaRetiradoException $exception) {
            if ($fotoPath !== null) {
                Storage::disk('public')->delete($fotoPath);
            }

            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            if ($fotoPath !== null) {
                Storage::disk('public')->delete($fotoPath);
            }
            throw $exception;
        }

        return response()->json([
            'id' => $paquete->id(),
            'proyecto_id' => $paquete->proyectoId(),
            'unidad_principal_id' => $paquete->unidadPrincipalId(),
            'unidad' => $paquete->unidadIdentificador(),
            'residente_id' => $paquete->residenteId(),
            'descripcion' => $paquete->descripcion(),
            'remitente' => $paquete->remitente(),
            'foto_url' => $paquete->fotoPath() !== null
                ? Storage::disk('public')->url($paquete->fotoPath())
                : null,
            'fecha_recepcion' => $paquete->fechaRecepcion(),
            'estado' => $paquete->estado(),
            'fecha_retiro' => $paquete->fechaRetiro(),
            'retirado_por_nombre' => $paquete->retiradoPorNombre(),
        ]);
    }

    public function retirar(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'retirado_por_nombre' => ['required', 'string', 'max:255'],
        ]);

        if ($this->paqueteAccesible($request->user(), $id) === null) {
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

        return response()->json([
            'id' => $paquete->id(),
            'proyecto_id' => $paquete->proyectoId(),
            'unidad_principal_id' => $paquete->unidadPrincipalId(),
            'unidad' => $paquete->unidadIdentificador(),
            'residente_id' => $paquete->residenteId(),
            'descripcion' => $paquete->descripcion(),
            'remitente' => $paquete->remitente(),
            'foto_url' => $paquete->fotoPath() !== null
                ? Storage::disk('public')->url($paquete->fotoPath())
                : null,
            'fecha_recepcion' => $paquete->fechaRecepcion(),
            'estado' => $paquete->estado(),
            'fecha_retiro' => $paquete->fechaRetiro(),
            'retirado_por_nombre' => $paquete->retiradoPorNombre(),
        ]);
    }

    /** @return int[] */
    private function proyectosAccesibles(User $usuario): array
    {
        $ids = $usuario->proyectos()->pluck('proyectos.id')->map(fn ($id) => (int) $id)->all();
        if ($usuario->proyecto_id !== null) {
            $ids[] = (int) $usuario->proyecto_id;
        }

        return array_values(array_unique($ids));
    }

    private function puedeAccederAProyecto(User $usuario, int $proyectoId): bool
    {
        return $usuario->rol === 'superadmin'
            || in_array($proyectoId, $this->proyectosAccesibles($usuario), true);
    }

    private function paqueteAccesible(User $usuario, string $id): ?PaqueteModel
    {
        $query = PaqueteModel::query()->whereKey($id);
        if ($usuario->rol !== 'superadmin') {
            $query->whereIn('proyecto_id', $this->proyectosAccesibles($usuario));
        }

        return $query->first();
    }
}
