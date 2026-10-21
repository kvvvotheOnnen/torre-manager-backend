<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Features\Superadmin\Domain\Entities\ResumenProyecto;
use Features\Superadmin\Domain\UseCases\ListarProyectosConConteosUseCase;
use Illuminate\Http\JsonResponse;

final class SuperadminController extends Controller
{
    public function __construct(
        private readonly ListarProyectosConConteosUseCase $listarProyectosUseCase,
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
}
