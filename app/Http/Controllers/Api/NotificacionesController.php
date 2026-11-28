<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DateTimeInterface;
use Features\Notificaciones\Domain\Entities\Notificacion;
use Features\Notificaciones\Domain\Exceptions\NotificacionNoEncontradaException;
use Features\Notificaciones\Domain\UseCases\ContarNotificacionesNoLeidasUseCase;
use Features\Notificaciones\Domain\UseCases\ListarNotificacionesUseCase;
use Features\Notificaciones\Domain\UseCases\MarcarNotificacionLeidaUseCase;
use Features\Notificaciones\Domain\UseCases\MarcarTodasNotificacionesLeidasUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificacionesController extends Controller
{
    public function __construct(
        private readonly ListarNotificacionesUseCase $listar,
        private readonly ContarNotificacionesNoLeidasUseCase $contarNoLeidas,
        private readonly MarcarNotificacionLeidaUseCase $marcarLeida,
        private readonly MarcarTodasNotificacionesLeidasUseCase $marcarTodas,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $usuarioId = (string) $request->user()->getAuthIdentifier();
        $notificaciones = ($this->listar)($usuarioId);

        return response()->json([
            'notificaciones' => array_map(
                fn (Notificacion $notificacion): array => $this->serializar($notificacion),
                $notificaciones,
            ),
        ]);
    }

    public function noLeidas(Request $request): JsonResponse
    {
        $usuarioId = (string) $request->user()->getAuthIdentifier();

        return response()->json([
            'no_leidas' => ($this->contarNoLeidas)($usuarioId),
        ]);
    }

    public function leer(Request $request, string $id): JsonResponse
    {
        $usuarioId = (string) $request->user()->getAuthIdentifier();

        try {
            $notificacion = ($this->marcarLeida)($usuarioId, $id);
        } catch (NotificacionNoEncontradaException $excepcion) {
            return response()->json(['message' => $excepcion->getMessage()], 404);
        }

        return response()->json([
            'notificacion' => $this->serializar($notificacion),
        ]);
    }

    public function leerTodas(Request $request): JsonResponse
    {
        $usuarioId = (string) $request->user()->getAuthIdentifier();

        return response()->json([
            'marcadas' => ($this->marcarTodas)($usuarioId),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(Notificacion $notificacion): array
    {
        return [
            'id' => $notificacion->id(),
            'tipo' => $notificacion->tipo(),
            'titulo' => $notificacion->titulo(),
            'mensaje' => $notificacion->mensaje(),
            'datos' => $notificacion->datos(),
            'leida' => $notificacion->leida(),
            'creado_en' => $notificacion->creadoEn()?->format(DateTimeInterface::ATOM),
        ];
    }
}
