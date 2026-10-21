<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\CrearAdministradorRequest;
use Features\Superadmin\Domain\Entities\Administrador;
use Features\Superadmin\Domain\Entities\ResumenProyecto;
use Features\Superadmin\Domain\Exceptions\EmailDuplicadoException;
use Features\Superadmin\Domain\UseCases\CrearAdministradorUseCase;
use Features\Superadmin\Domain\UseCases\ListarAdministradoresUseCase;
use Features\Superadmin\Domain\UseCases\ListarProyectosConConteosUseCase;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

final class SuperadminController extends Controller
{
    public function __construct(
        private readonly ListarProyectosConConteosUseCase $listarProyectosUseCase,
        private readonly ListarAdministradoresUseCase $listarAdministradoresUseCase,
        private readonly CrearAdministradorUseCase $crearAdministradorUseCase,
    ) {}

    public function proyectos(): JsonResponse
    {
        $proyectos = array_map(
            fn (ResumenProyecto $proyecto) => [
                'id' => $proyecto->id(),
                'nombre' => $proyecto->nombre(),
                'descripcion' => $proyecto->descripcion(),
                'agrupaciones' => $proyecto->agrupaciones(),
                'unidades_principales' => $proyecto->unidadesPrincipales(),
                'unidades_accesorias' => $proyecto->unidadesAccesorias(),
            ],
            $this->listarProyectosUseCase->execute(),
        );

        return response()->json(['proyectos' => $proyectos]);
    }

    public function administradores(): JsonResponse
    {
        $administradores = array_map(
            fn (Administrador $administrador) => [
                'id' => $administrador->id(),
                'nombre' => $administrador->nombre(),
                'email' => $administrador->correo(),
                'rol' => $administrador->rol()->value,
            ],
            $this->listarAdministradoresUseCase->execute(),
        );

        return response()->json(['administradores' => $administradores]);
    }

    public function crearAdministrador(CrearAdministradorRequest $request): JsonResponse
    {
        try {
            $administrador = $this->crearAdministradorUseCase->execute(
                $request->string('nombre')->toString(),
                $request->string('email')->toString(),
                $request->string('password')->toString(),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (EmailDuplicadoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json([
            'id' => $administrador->id(),
            'nombre' => $administrador->nombre(),
            'email' => $administrador->correo(),
            'rol' => $administrador->rol()->value,
        ], 201);
    }
}
