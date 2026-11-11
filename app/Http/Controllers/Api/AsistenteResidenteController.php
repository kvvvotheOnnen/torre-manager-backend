<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asistente\ConsultarAsistenteRequest;
use Features\Asistente\Domain\Entities\FuenteRespuesta;
use Features\Asistente\Domain\Entities\RespuestaAsistente;
use Features\Asistente\Domain\Exceptions\DocumentoNoDisponibleException;
use Features\Asistente\Domain\Exceptions\ModeloNoDisponibleException;
use Features\Asistente\Domain\UseCases\ConsultarAsistenteUseCase;
use Illuminate\Http\JsonResponse;

final class AsistenteResidenteController extends Controller
{
    public function __construct(
        private readonly ConsultarAsistenteUseCase $consultarAsistente,
    ) {}

    public function consultar(ConsultarAsistenteRequest $request): JsonResponse
    {
        $proyectoId = $request->user()->proyecto_id;

        if ($proyectoId === null) {
            return response()->json([
                'message' => 'No tienes un proyecto activo asociado.',
            ], 422);
        }

        try {
            $respuesta = $this->consultarAsistente->execute(
                (string) $proyectoId,
                (string) $request->string('pregunta'),
            );
        } catch (DocumentoNoDisponibleException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        } catch (ModeloNoDisponibleException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        return response()->json($this->serializar($respuesta));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(RespuestaAsistente $respuesta): array
    {
        return [
            'respuesta' => $respuesta->respuesta(),
            'fuentes' => array_map(
                static fn (FuenteRespuesta $fuente) => [
                    'documento' => $fuente->documento(),
                    'pagina' => $fuente->pagina(),
                ],
                $respuesta->fuentes(),
            ),
        ];
    }
}
