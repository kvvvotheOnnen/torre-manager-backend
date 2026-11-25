<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SolicitudesResidente\RegistrarSolicitudRequest;
use Features\Acceso\Domain\Entities\TipoVinculo;
use Features\SolicitudesResidente\Domain\Entities\DatosPersonaRegistro;
use Features\SolicitudesResidente\Domain\Entities\DatosRegistroResidente;
use Features\SolicitudesResidente\Domain\Entities\EspecieMascota;
use Features\SolicitudesResidente\Domain\Entities\MascotaRegistro;
use Features\SolicitudesResidente\Domain\Entities\ProyectoRegistro;
use Features\SolicitudesResidente\Domain\Entities\UnidadRegistro;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudDuplicadaException;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudInvalidaException;
use Features\SolicitudesResidente\Domain\Repositories\RegistroResidenteRepositoryInterface;
use Features\SolicitudesResidente\Domain\UseCases\RegistrarSolicitudResidenteUseCase;
use Illuminate\Http\JsonResponse;

final class RegistroResidenteController extends Controller
{
    public function __construct(
        private readonly RegistroResidenteRepositoryInterface $registro,
        private readonly RegistrarSolicitudResidenteUseCase $registrarUseCase,
    ) {}

    public function proyectos(): JsonResponse
    {
        return response()->json([
            'proyectos' => array_map(fn (ProyectoRegistro $proyecto) => [
                'id' => $proyecto->id(),
                'nombre' => $proyecto->nombre(),
            ], $this->registro->listarProyectosDisponibles()),
        ]);
    }

    public function unidades(string $proyectoId): JsonResponse
    {
        if (! $this->registro->proyectoDisponible($proyectoId)) {
            return response()->json(['message' => 'El proyecto seleccionado no está disponible.'], 404);
        }

        return response()->json([
            'unidades' => array_map(fn (UnidadRegistro $unidad) => [
                'id' => $unidad->id(),
                'identificador' => $unidad->identificador(),
                'agrupacion' => $unidad->agrupacion(),
                'etiqueta' => $unidad->etiqueta(),
            ], $this->registro->listarUnidades($proyectoId)),
        ]);
    }

    public function store(RegistrarSolicitudRequest $request): JsonResponse
    {
        $datos = $this->mapearDatos($request);

        try {
            $solicitudId = $this->registrarUseCase->execute($datos);
        } catch (SolicitudDuplicadaException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (SolicitudInvalidaException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'id' => $solicitudId,
            'estado' => 'pendiente',
            'message' => 'Tu solicitud fue registrada. Un administrador debe aprobarla para completar tu ingreso.',
        ], 201);
    }

    private function mapearDatos(RegistrarSolicitudRequest $request): DatosRegistroResidente
    {
        $personas = [];

        foreach ($request->input('personas', []) as $persona) {
            $esPrincipal = (bool) ($persona['es_principal'] ?? false);

            $personas[] = new DatosPersonaRegistro(
                nombre: (string) $persona['nombre'],
                rut: (string) $persona['rut'],
                email: (string) $persona['email'],
                telefono: (string) $persona['telefono'],
                telefonoEmergencia: (string) $persona['telefono_emergencia'],
                nombreEmergencia: (string) $persona['nombre_emergencia'],
                tipoVinculo: $esPrincipal
                    ? TipoVinculo::from((string) $persona['tipo_vinculo'])
                    : TipoVinculo::Residente,
                esPrincipal: $esPrincipal,
            );
        }

        $mascotas = [];

        foreach ($request->input('mascotas', []) as $mascota) {
            $mascotas[] = new MascotaRegistro(
                nombre: (string) $mascota['nombre'],
                especie: EspecieMascota::from((string) $mascota['especie']),
                raza: (string) $mascota['raza'],
                microchip: isset($mascota['microchip']) && $mascota['microchip'] !== ''
                    ? (string) $mascota['microchip']
                    : null,
                fotoUrl: isset($mascota['foto_url']) && $mascota['foto_url'] !== ''
                    ? (string) $mascota['foto_url']
                    : null,
            );
        }

        return new DatosRegistroResidente(
            proyectoId: (string) $request->input('proyecto_id'),
            unidadPrincipalId: (string) $request->input('unidad_principal_id'),
            password: (string) $request->input('password'),
            personas: $personas,
            mascotas: $mascotas,
        );
    }
}
