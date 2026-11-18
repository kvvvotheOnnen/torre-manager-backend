<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SolicitudesResidente\RechazarSolicitudRequest;
use Features\Acceso\Domain\Services\AccesoProyectoService;
use Features\Auth\Domain\Entities\Rol;
use Features\SolicitudesResidente\Domain\Entities\InstalacionUnidad;
use Features\SolicitudesResidente\Domain\Entities\PersonaSolicitud;
use Features\SolicitudesResidente\Domain\Entities\SolicitudResidente;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudInvalidaException;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudNoEncontradaException;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudYaDecididaException;
use Features\SolicitudesResidente\Domain\UseCases\AprobarSolicitudUseCase;
use Features\SolicitudesResidente\Domain\UseCases\ListarHistorialSolicitudesUseCase;
use Features\SolicitudesResidente\Domain\UseCases\ListarMisSolicitudesUseCase;
use Features\SolicitudesResidente\Domain\UseCases\ListarSolicitudesPendientesUseCase;
use Features\SolicitudesResidente\Domain\UseCases\ObtenerSolicitudUseCase;
use Features\SolicitudesResidente\Domain\UseCases\RechazarSolicitudUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SolicitudResidenteController extends Controller
{
    public function __construct(
        private readonly ListarSolicitudesPendientesUseCase $listarPendientesUseCase,
        private readonly ObtenerSolicitudUseCase $obtenerUseCase,
        private readonly ListarHistorialSolicitudesUseCase $historialUseCase,
        private readonly ListarMisSolicitudesUseCase $misSolicitudesUseCase,
        private readonly AprobarSolicitudUseCase $aprobarUseCase,
        private readonly RechazarSolicitudUseCase $rechazarUseCase,
        private readonly AccesoProyectoService $acceso,
    ) {}

    public function misSolicitudes(Request $request): JsonResponse
    {
        $solicitudes = $this->misSolicitudesUseCase->execute(
            (string) $request->user()->getAuthIdentifier(),
        );

        return response()->json([
            'solicitudes' => array_map(fn (SolicitudResidente $s) => $this->serializar($s), $solicitudes),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $contexto = $this->contexto($request);

        if ($contexto instanceof JsonResponse) {
            return $contexto;
        }

        $solicitudes = $this->listarPendientesUseCase->execute($contexto);

        return response()->json([
            'solicitudes' => array_map(fn (SolicitudResidente $s) => $this->serializar($s), $solicitudes),
        ]);
    }

    public function historial(Request $request): JsonResponse
    {
        $contexto = $this->contexto($request);

        if ($contexto instanceof JsonResponse) {
            return $contexto;
        }

        $solicitudes = $this->historialUseCase->execute($contexto);

        return response()->json([
            'solicitudes' => array_map(fn (SolicitudResidente $s) => $this->serializar($s), $solicitudes),
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $contexto = $this->contexto($request);

        if ($contexto instanceof JsonResponse) {
            return $contexto;
        }

        try {
            $solicitud = $this->obtenerUseCase->execute($id);
        } catch (SolicitudNoEncontradaException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        if ($solicitud->proyectoId() !== $contexto) {
            return response()->json(['message' => 'Solicitud de residente no encontrada.'], 404);
        }

        return response()->json($this->serializar($solicitud));
    }

    public function aprobar(Request $request, string $id): JsonResponse
    {
        $contexto = $this->contexto($request);

        if ($contexto instanceof JsonResponse) {
            return $contexto;
        }

        try {
            $solicitud = $this->obtenerUseCase->execute($id);
        } catch (SolicitudNoEncontradaException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        if ($solicitud->proyectoId() !== $contexto) {
            return response()->json(['message' => 'Solicitud de residente no encontrada.'], 404);
        }

        try {
            $solicitud = $this->aprobarUseCase->execute($id, (string) $request->user()->getAuthIdentifier());
        } catch (SolicitudYaDecididaException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (SolicitudInvalidaException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($this->serializar($solicitud));
    }

    public function rechazar(RechazarSolicitudRequest $request, string $id): JsonResponse
    {
        $contexto = $this->contexto($request);

        if ($contexto instanceof JsonResponse) {
            return $contexto;
        }

        try {
            $solicitud = $this->obtenerUseCase->execute($id);
        } catch (SolicitudNoEncontradaException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        if ($solicitud->proyectoId() !== $contexto) {
            return response()->json(['message' => 'Solicitud de residente no encontrada.'], 404);
        }

        try {
            $solicitud = $this->rechazarUseCase->execute(
                $id,
                (string) $request->user()->getAuthIdentifier(),
                $request->string('motivo')->toString(),
            );
        } catch (SolicitudYaDecididaException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json($this->serializar($solicitud));
    }

    private function contexto(Request $request): string|JsonResponse
    {
        $proyectoId = $request->header('X-Proyecto-Id')
            ?? $request->input('proyecto_id')
            ?? $request->user()?->proyecto_id;

        if ($proyectoId === null || $proyectoId === '') {
            return response()->json(['message' => 'Debes indicar el proyecto activo.'], 422);
        }

        $proyectoId = (string) $proyectoId;
        $rol = $this->acceso->rolEn((string) $request->user()->getAuthIdentifier(), $proyectoId);

        if (! in_array($rol, [Rol::Administrador, Rol::Conserje, Rol::Superadmin], true)) {
            return response()->json(['message' => 'No tienes permisos en el proyecto activo.'], 403);
        }

        return $proyectoId;
    }

    /** @return array<string, mixed> */
    private function serializar(SolicitudResidente $solicitud): array
    {
        return [
            'id' => $solicitud->id(),
            'proyecto_id' => $solicitud->proyectoId(),
            'unidad_principal_id' => $solicitud->unidadPrincipalId(),
            'unidad' => $solicitud->unidad(),
            'agrupacion' => $solicitud->agrupacion(),
            'tipo_solicitud' => $solicitud->tipoSolicitud()->value,
            'origen' => $solicitud->origen()->value,
            'estado' => $solicitud->estado()->value,
            'motivo_rechazo' => $solicitud->motivoRechazo(),
            'solicitado_por_id' => $solicitud->solicitadoPorId(),
            'solicitado_por_nombre' => $solicitud->solicitadoPorNombre(),
            'decidido_por_id' => $solicitud->decididoPorId(),
            'decidido_por_nombre' => $solicitud->decididoPorNombre(),
            'decidido_at' => $solicitud->decididoAt(),
            'created_at' => $solicitud->createdAt(),
            'personas' => array_map(fn (PersonaSolicitud $persona) => [
                'id' => $persona->id(),
                'usuario_id' => $persona->usuarioId(),
                'nombre' => $persona->nombre(),
                'rut' => $persona->rut(),
                'email' => $persona->email(),
                'telefono' => $persona->telefono(),
                'telefono_emergencia' => $persona->telefonoEmergencia(),
                'nombre_emergencia' => $persona->nombreEmergencia(),
                'tipo_vinculo' => $persona->tipoVinculo()->value,
                'es_principal' => $persona->esPrincipal(),
            ], $solicitud->personas()),
            'instalaciones' => array_map(fn (InstalacionUnidad $instalacion) => [
                'id' => $instalacion->id(),
                'identificador' => $instalacion->identificador(),
                'tipo' => $instalacion->tipo(),
                'asignacion' => $instalacion->asignacion(),
            ], $solicitud->instalaciones()),
        ];
    }
}
