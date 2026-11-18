<?php

namespace Features\Servicios\Data\Repositories;

use App\Models\TrabajadorServicio as TrabajadorServicioModel;
use Features\Servicios\Domain\Entities\TipoServicio;
use Features\Servicios\Domain\Entities\TrabajadorServicio;
use Features\Servicios\Domain\Exceptions\TrabajadorNoEncontradoException;
use Features\Servicios\Domain\Repositories\ServiciosRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class ServiciosRepository implements ServiciosRepositoryInterface
{
    public function listar(string $proyectoId, ?TipoServicio $tipo = null, ?string $buscar = null): array
    {
        $consulta = TrabajadorServicioModel::query()->where('proyecto_id', $proyectoId);

        if ($tipo !== null) {
            $consulta->where('tipo_servicio', $tipo->value);
        }

        $termino = trim((string) $buscar);

        if ($termino !== '') {
            $patron = '%'.Str::lower(Str::ascii(str_replace(['%', '_'], '', $termino))).'%';

            $consulta->where(function (Builder $subconsulta) use ($patron, $termino): void {
                $subconsulta->whereRaw($this->sqlSinAcentos('nombre').' LIKE ?', [$patron])
                    ->orWhereRaw($this->sqlSinAcentos('nombre_empresa').' LIKE ?', [$patron])
                    ->orWhereRaw($this->sqlSinAcentos('descripcion').' LIKE ?', [$patron]);

                $tipos = array_values(array_filter(
                    TipoServicio::cases(),
                    fn (TipoServicio $tipo): bool => $this->coincideTipo($termino, $tipo),
                ));

                if ($tipos !== []) {
                    $subconsulta->orWhereIn(
                        'tipo_servicio',
                        array_map(fn (TipoServicio $tipo): string => $tipo->value, $tipos),
                    );
                }
            });
        }

        return $consulta
            ->orderBy('nombre')
            ->get()
            ->map(fn (TrabajadorServicioModel $modelo) => $this->aEntidad($modelo))
            ->all();
    }

    public function obtener(string $id, string $proyectoId): TrabajadorServicio
    {
        return $this->aEntidad($this->buscarModelo($id, $proyectoId));
    }

    public function crear(array $datos): TrabajadorServicio
    {
        return $this->aEntidad(TrabajadorServicioModel::query()->create($datos));
    }

    public function actualizar(string $id, string $proyectoId, array $datos): TrabajadorServicio
    {
        $modelo = $this->buscarModelo($id, $proyectoId);
        $modelo->fill($datos);
        $modelo->save();

        return $this->aEntidad($modelo);
    }

    public function eliminar(string $id, string $proyectoId): void
    {
        $this->buscarModelo($id, $proyectoId)->delete();
    }

    private function coincideTipo(string $termino, TipoServicio $tipo): bool
    {
        $buscado = Str::lower(Str::ascii($termino));

        if ($buscado === '') {
            return false;
        }

        foreach ([$tipo->value, $tipo->etiqueta()] as $candidato) {
            $texto = Str::lower(Str::ascii($candidato));

            if (str_contains($texto, $buscado) || str_contains($buscado, $texto)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Expresión SQL portable que compara texto sin mayúsculas ni tildes.
     *
     * Se resuelve con REPLACE para no depender de la extensión unaccent,
     * que no está disponible en SQLite ni en todas las instalaciones.
     */
    private function sqlSinAcentos(string $columna): string
    {
        $expresion = "COALESCE({$columna}, '')";

        $reemplazos = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ];

        foreach ($reemplazos as $acento => $plano) {
            $expresion = "REPLACE({$expresion}, '{$acento}', '{$plano}')";
        }

        return "LOWER({$expresion})";
    }

    private function buscarModelo(string $id, string $proyectoId): TrabajadorServicioModel
    {
        $modelo = TrabajadorServicioModel::query()
            ->whereKey($id)
            ->where('proyecto_id', $proyectoId)
            ->first();

        if ($modelo === null) {
            throw TrabajadorNoEncontradoException::becauseDoesNotExist();
        }

        return $modelo;
    }

    private function aEntidad(TrabajadorServicioModel $modelo): TrabajadorServicio
    {
        return new TrabajadorServicio(
            id: (string) $modelo->getKey(),
            proyectoId: (string) $modelo->proyecto_id,
            nombre: (string) $modelo->nombre,
            nombreEmpresa: $modelo->nombre_empresa === null ? null : (string) $modelo->nombre_empresa,
            telefono: (string) $modelo->telefono,
            tipo: TipoServicio::tryFrom((string) $modelo->tipo_servicio) ?? TipoServicio::Otro,
            descripcion: $modelo->descripcion === null ? null : (string) $modelo->descripcion,
            fotoPath: $modelo->foto_path === null ? null : (string) $modelo->foto_path,
            creadoPor: $modelo->creado_por === null ? null : (string) $modelo->creado_por,
        );
    }
}
