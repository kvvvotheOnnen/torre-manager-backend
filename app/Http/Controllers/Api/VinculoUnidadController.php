<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnidadPrincipal;
use App\Models\User;
use Features\Acceso\Domain\Entities\TipoVinculo;
use Features\Acceso\Domain\Entities\VinculoUnidad;
use Features\Acceso\Domain\Exceptions\UnidadNoDisponibleException;
use Features\Acceso\Domain\Exceptions\VinculoNoEncontradoException;
use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;
use Features\Acceso\Domain\Services\AccesoProyectoService;
use Features\Acceso\Domain\UseCases\AsignarVinculoUnidadUseCase;
use Features\Acceso\Domain\UseCases\CerrarVinculoUnidadUseCase;
use Features\Acceso\Domain\UseCases\ListarVinculosUnidadUseCase;
use Features\Acceso\Domain\UseCases\ListarVinculosUsuarioUseCase;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VinculoUnidadController extends Controller
{
    public function __construct(
        private readonly AsignarVinculoUnidadUseCase $asignarUseCase,
        private readonly CerrarVinculoUnidadUseCase $cerrarUseCase,
        private readonly ListarVinculosUnidadUseCase $listarUnidadUseCase,
        private readonly ListarVinculosUsuarioUseCase $listarUsuarioUseCase,
        private readonly VinculoUnidadRepositoryInterface $vinculos,
        private readonly AccesoProyectoService $acceso,
    ) {}

    public function misVinculos(Request $request): JsonResponse
    {
        $vinculos = $this->listarUsuarioUseCase->execute(
            (string) $request->user()->getAuthIdentifier(),
            $this->proyectoIdSolicitado($request),
        );

        return response()->json([
            'vinculos' => array_map(fn (VinculoUnidad $v) => $this->serializar($v), $vinculos),
        ]);
    }

    public function misUnidades(Request $request): JsonResponse
    {
        $vinculos = $this->listarUsuarioUseCase->execute(
            (string) $request->user()->getAuthIdentifier(),
            $this->proyectoIdSolicitado($request),
        );

        $porUnidad = [];
        foreach ($vinculos as $vinculo) {
            $clave = $vinculo->unidadPrincipalId();
            $porUnidad[$clave] ??= [
                'unidad_principal_id' => $vinculo->unidadPrincipalId(),
                'unidad' => $vinculo->unidadIdentificador(),
                'agrupacion' => $vinculo->agrupacionNombre(),
                'proyecto_id' => $vinculo->proyectoId(),
                'vinculos' => [],
            ];
            $porUnidad[$clave]['vinculos'][] = $this->serializar($vinculo);
        }

        return response()->json(['unidades' => array_values($porUnidad)]);
    }

    public function index(Request $request, string $unidadId): JsonResponse
    {
        $unidad = UnidadPrincipal::query()->with('agrupacion')->find($unidadId);

        if ($unidad === null || ! $this->puedeAdministrar($request, (int) $unidad->agrupacion->proyecto_id)) {
            return response()->json(['message' => 'Unidad no encontrada.'], 404);
        }

        $vinculos = $this->listarUnidadUseCase->execute((string) $unidad->getKey());

        return response()->json([
            'vinculos' => array_map(fn (VinculoUnidad $v) => $this->serializar($v), $vinculos),
        ]);
    }

    public function store(Request $request, string $unidadId): JsonResponse
    {
        $validated = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'tipo_vinculo' => ['required', 'string', 'in:propietario,arrendatario,residente,familiar'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $unidad = UnidadPrincipal::query()->with('agrupacion')->find($unidadId);

        if ($unidad === null || ! $this->puedeAdministrar($request, (int) $unidad->agrupacion->proyecto_id)) {
            return response()->json(['message' => 'Unidad no encontrada.'], 404);
        }

        $usuario = User::query()->find($validated['usuario_id']);

        if ($usuario === null || $usuario->rol === Rol::Superadmin->value) {
            return response()->json(['message' => 'El usuario indicado no puede vincularse a una unidad.'], 422);
        }

        try {
            $vinculo = $this->asignarUseCase->execute(
                (string) $unidad->getKey(),
                (string) $usuario->getKey(),
                (string) $unidad->agrupacion->proyecto_id,
                TipoVinculo::from($validated['tipo_vinculo']),
                $validated['fecha_inicio'],
                $validated['fecha_fin'] ?? null,
            );
        } catch (UnidadNoDisponibleException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json($this->serializar($vinculo), 201);
    }

    public function destroy(Request $request, string $vinculoId): JsonResponse
    {
        $vinculo = $this->vinculos->obtener($vinculoId);

        if ($vinculo === null || ! $this->puedeAdministrar($request, (int) $vinculo->proyectoId())) {
            return response()->json(['message' => 'Vínculo no encontrado.'], 404);
        }

        $validated = $request->validate([
            'fecha_fin' => ['nullable', 'date'],
        ]);

        try {
            $cerrado = $this->cerrarUseCase->execute($vinculoId, $validated['fecha_fin'] ?? null);
        } catch (VinculoNoEncontradoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json($this->serializar($cerrado));
    }

    private function puedeAdministrar(Request $request, int $proyectoId): bool
    {
        $rol = $this->acceso->rolEn((string) $request->user()->getAuthIdentifier(), (string) $proyectoId);

        return $rol === Rol::Superadmin || $rol === Rol::Administrador;
    }

    private function proyectoIdSolicitado(Request $request): ?string
    {
        $proyectoId = $request->header('X-Proyecto-Id') ?? $request->input('proyecto_id');

        return $proyectoId === null || $proyectoId === '' ? null : (string) $proyectoId;
    }

    /** @return array<string, mixed> */
    private function serializar(VinculoUnidad $vinculo): array
    {
        return [
            'id' => $vinculo->id(),
            'unidad_principal_id' => $vinculo->unidadPrincipalId(),
            'unidad' => $vinculo->unidadIdentificador(),
            'agrupacion' => $vinculo->agrupacionNombre(),
            'proyecto_id' => $vinculo->proyectoId(),
            'usuario_id' => $vinculo->usuarioId(),
            'usuario_nombre' => $vinculo->usuarioNombre(),
            'tipo_vinculo' => $vinculo->tipo()->value,
            'fecha_inicio' => $vinculo->fechaInicio(),
            'fecha_fin' => $vinculo->fechaFin(),
            'es_actual' => $vinculo->esActual(),
        ];
    }
}
