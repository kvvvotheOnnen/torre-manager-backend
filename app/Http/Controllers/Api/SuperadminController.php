<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\ActualizarProyectoRequest;
use App\Http\Requests\Superadmin\CrearAdministradorRequest;
use App\Http\Requests\Superadmin\CrearProyectoRequest;
use Features\Superadmin\Domain\Entities\Administrador;
use Features\Superadmin\Domain\Entities\InstalacionExtra;
use Features\Superadmin\Domain\Entities\ModuloSistema;
use Features\Superadmin\Domain\Entities\ProyectoDetalle;
use Features\Superadmin\Domain\Entities\ResumenProyecto;
use Features\Superadmin\Domain\Exceptions\EmailDuplicadoException;
use Features\Superadmin\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Superadmin\Domain\UseCases\ActualizarProyectoUseCase;
use Features\Superadmin\Domain\UseCases\BuscarProyectoUseCase;
use Features\Superadmin\Domain\UseCases\CrearAdministradorUseCase;
use Features\Superadmin\Domain\UseCases\CrearProyectoYAsignarAdministradoresUseCase;
use Features\Superadmin\Domain\UseCases\ListarAdministradoresUseCase;
use Features\Superadmin\Domain\UseCases\ListarProyectosConConteosUseCase;
use Features\Superadmin\Domain\UseCases\ListarProyectosDelUsuarioUseCase;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

final class SuperadminController extends Controller
{
    public function __construct(
        private readonly ListarProyectosConConteosUseCase $listarProyectosUseCase,
        private readonly ListarAdministradoresUseCase $listarAdministradoresUseCase,
        private readonly CrearAdministradorUseCase $crearAdministradorUseCase,
        private readonly CrearProyectoYAsignarAdministradoresUseCase $crearProyectoUseCase,
        private readonly ListarProyectosDelUsuarioUseCase $listarProyectosDelUsuarioUseCase,
        private readonly BuscarProyectoUseCase $buscarProyectoUseCase,
        private readonly ActualizarProyectoUseCase $actualizarProyectoUseCase,
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

    public function crearProyecto(CrearProyectoRequest $request): JsonResponse
    {
        try {
            $proyecto = $this->crearProyectoUseCase->execute(
                $request->string('nombre')->toString(),
                $request->input('descripcion'),
                $request->string('tipo_condominio')->toString(),
                $request->input('modulos', []),
                $request->input('agrupaciones', []),
                $request->input('instalaciones', []),
                array_map('strval', $request->input('admin_ids', [])),
                (int) $request->input('estacionamientos', 0),
                (int) $request->input('bodegas', 0),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RecursoNoEncontradoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json($this->serializarProyectoDetalle($proyecto), 201);
    }

    public function verProyecto(string $proyecto): JsonResponse
    {
        try {
            $detalle = $this->buscarProyectoUseCase->execute($proyecto);
        } catch (RecursoNoEncontradoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json($this->serializarProyectoDetalle($detalle));
    }

    public function actualizarProyecto(ActualizarProyectoRequest $request, string $proyecto): JsonResponse
    {
        try {
            $detalle = $this->actualizarProyectoUseCase->execute(
                $proyecto,
                $request->string('nombre')->toString(),
                $request->input('descripcion'),
                $request->input('modulos', []),
                $request->input('instalaciones', []),
                array_map('strval', $request->input('admin_ids', [])),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RecursoNoEncontradoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json($this->serializarProyectoDetalle($detalle));
    }

    public function misProyectos(): JsonResponse
    {
        try {
            $proyectos = $this->listarProyectosDelUsuarioUseCase->execute((string) auth()->id());
        } catch (RecursoNoEncontradoException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json([
            'proyectos' => array_map(
                fn (ProyectoDetalle $proyecto) => $this->serializarProyectoDetalle($proyecto),
                $proyectos,
            ),
        ]);
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

    private function serializarProyectoDetalle(ProyectoDetalle $proyecto): array
    {
        return [
            'id' => $proyecto->id(),
            'nombre' => $proyecto->nombre(),
            'descripcion' => $proyecto->descripcion(),
            'tipo_condominio' => $proyecto->tipoCondominio()->value,
            'modulos' => array_map(
                static fn (ModuloSistema $modulo) => $modulo->value,
                $proyecto->modulos(),
            ),
            'instalaciones_extra' => array_map(
                static fn (InstalacionExtra $instalacion) => [
                    'id' => $instalacion->id(),
                    'nombre' => $instalacion->nombre(),
                    'tipo' => $instalacion->tipo(),
                    'atributos' => $instalacion->atributos() === [] ? (object) [] : $instalacion->atributos(),
                ],
                $proyecto->instalacionesExtra(),
            ),
            'admin_ids' => $proyecto->administradoresIds(),
        ];
    }
}
