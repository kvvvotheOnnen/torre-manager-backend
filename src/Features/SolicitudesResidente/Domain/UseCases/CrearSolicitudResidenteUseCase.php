<?php

namespace Features\SolicitudesResidente\Domain\UseCases;

use Features\Auth\Domain\Entities\EstadoUsuario;
use Features\Auth\Domain\Entities\Rol;
use Features\Auth\Domain\Repositories\UsuarioRepositoryInterface;
use Features\Auth\Domain\ValueObjects\Rut;
use Features\SolicitudesResidente\Domain\Entities\OrigenSolicitud;
use Features\SolicitudesResidente\Domain\Entities\SolicitudResidente;
use Features\SolicitudesResidente\Domain\Entities\TipoSolicitud;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudDuplicadaException;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudInvalidaException;
use Features\SolicitudesResidente\Domain\Repositories\SolicitudResidenteRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class CrearSolicitudResidenteUseCase
{
    public function __construct(
        private readonly SolicitudResidenteRepositoryInterface $solicitudes,
        private readonly UsuarioRepositoryInterface $usuarios,
    ) {}

    /**
     * @param array<int, array{
     *     nombre: string,
     *     rut: string,
     *     email: string,
     *     telefono: string,
     *     telefono_emergencia: string,
     *     nombre_emergencia: string,
     *     es_principal?: bool
     * }> $personas
     * @param  array<string, mixed>|null  $datosExtra
     */
    public function execute(
        string $proyectoId,
        string $unidadId,
        TipoSolicitud $tipoSolicitud,
        OrigenSolicitud $origen,
        ?string $solicitadoPorId,
        array $personas,
        ?array $datosExtra = null,
    ): SolicitudResidente {
        if ($personas === [] || count($personas) > 6) {
            throw SolicitudInvalidaException::porPersonasInvalidas();
        }

        if (! $this->solicitudes->unidadPerteneceAProyecto($unidadId, $proyectoId)) {
            throw SolicitudInvalidaException::porque('La unidad no pertenece al proyecto indicado.');
        }

        $normalizadas = $this->normalizarPersonas($personas);
        $emails = array_column($normalizadas, 'email');
        $ruts = array_column($normalizadas, 'rut');

        if (count($emails) !== count(array_unique($emails))) {
            throw SolicitudInvalidaException::porEmailDuplicado();
        }

        if (count($ruts) !== count(array_unique($ruts))) {
            throw SolicitudInvalidaException::porRutDuplicado();
        }

        if ($this->solicitudes->existePendienteDuplicada($unidadId, $emails, $ruts)) {
            throw SolicitudDuplicadaException::porqueYaExisteUnaPendiente();
        }

        return DB::transaction(function () use (
            $proyectoId,
            $unidadId,
            $tipoSolicitud,
            $origen,
            $solicitadoPorId,
            $normalizadas,
            $datosExtra,
        ) {
            $personasConUsuario = [];

            foreach ($normalizadas as $persona) {
                $rol = $persona['es_principal']
                    ? ($tipoSolicitud === TipoSolicitud::Propietario ? Rol::Propietario : Rol::Residente)
                    : Rol::Residente;

                $usuario = $this->usuarios->buscarPorEmail($persona['email']);
                $usuarioPorRut = $this->usuarios->buscarPorRut($persona['rut']);

                if ($usuarioPorRut !== null && $usuarioPorRut->correo() !== $persona['email']) {
                    throw SolicitudInvalidaException::porRutAsociadoAOtroCorreo();
                }

                if ($usuario !== null && $usuario->rut() !== null && $usuario->rut() !== $persona['rut']) {
                    throw SolicitudInvalidaException::porEmailAsociadoAOtroRut();
                }

                if ($usuario === null) {
                    $usuario = $this->usuarios->crearPendiente(
                        $proyectoId,
                        $persona['nombre'],
                        $persona['email'],
                        $persona['rut'],
                        $persona['telefono'],
                        $persona['telefono_emergencia'],
                        $persona['nombre_emergencia'],
                        $rol,
                    );
                } elseif ($usuario->estado() !== EstadoUsuario::Activo) {
                    $this->usuarios->actualizarDatos(
                        $usuario->id(),
                        $persona['nombre'],
                        $persona['rut'],
                        $persona['telefono'],
                        $persona['telefono_emergencia'],
                        $persona['nombre_emergencia'],
                        $rol,
                        EstadoUsuario::Pendiente,
                    );
                }

                $personasConUsuario[] = [
                    'usuario_id' => $usuario->id(),
                    'nombre' => $persona['nombre'],
                    'rut' => $persona['rut'],
                    'email' => $persona['email'],
                    'telefono' => $persona['telefono'],
                    'telefono_emergencia' => $persona['telefono_emergencia'],
                    'nombre_emergencia' => $persona['nombre_emergencia'],
                    'tipo_vinculo' => $persona['es_principal'] ? $tipoSolicitud->value : 'familiar',
                    'es_principal' => $persona['es_principal'],
                ];
            }

            return $this->solicitudes->crear(
                $proyectoId,
                $unidadId,
                $tipoSolicitud,
                $origen,
                $solicitadoPorId,
                $personasConUsuario,
                $datosExtra,
            );
        });
    }

    /**
     * @param array<int, array{
     *     nombre: string,
     *     rut: string,
     *     email: string,
     *     telefono: string,
     *     telefono_emergencia: string,
     *     nombre_emergencia: string,
     *     es_principal?: bool
     * }> $personas
     * @return array<int, array{
     *     nombre: string,
     *     rut: string,
     *     email: string,
     *     telefono: string,
     *     telefono_emergencia: string,
     *     nombre_emergencia: string,
     *     es_principal: bool
     * }>
     */
    private function normalizarPersonas(array $personas): array
    {
        $principales = array_filter($personas, static fn (array $p) => (bool) ($p['es_principal'] ?? false));

        if (count($principales) > 1) {
            throw SolicitudInvalidaException::porque('Solo una persona puede ser la principal.');
        }

        $indicePrincipal = (string) (array_key_first($principales) ?? array_key_first($personas));

        return array_values(array_map(function (array $persona, int $indice) use ($indicePrincipal): array {
            return [
                'nombre' => trim($persona['nombre']),
                'rut' => Rut::desde($persona['rut'])->valor(),
                'email' => mb_strtolower(trim($persona['email'])),
                'telefono' => $this->normalizarTelefono($persona['telefono']),
                'telefono_emergencia' => $this->normalizarTelefono($persona['telefono_emergencia']),
                'nombre_emergencia' => trim($persona['nombre_emergencia']),
                'es_principal' => (string) $indice === $indicePrincipal,
            ];
        }, $personas, array_keys($personas)));
    }

    private function normalizarTelefono(string $telefono): string
    {
        return (string) preg_replace('/[\s\-().]/', '', $telefono);
    }
}
