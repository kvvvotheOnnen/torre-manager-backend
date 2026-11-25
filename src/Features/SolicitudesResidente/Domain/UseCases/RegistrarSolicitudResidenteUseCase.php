<?php

namespace Features\SolicitudesResidente\Domain\UseCases;

use Features\Acceso\Domain\Entities\TipoVinculo;
use Features\Auth\Domain\Exceptions\RutInvalidoException;
use Features\Auth\Domain\ValueObjects\Rut;
use Features\Correo\Domain\Entities\Correo;
use Features\Correo\Domain\Repositories\CorreoRepositoryInterface;
use Features\SolicitudesResidente\Domain\Entities\DatosRegistroResidente;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudDuplicadaException;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudInvalidaException;
use Features\SolicitudesResidente\Domain\Repositories\RegistroResidenteRepositoryInterface;
use Throwable;

final class RegistrarSolicitudResidenteUseCase
{
    private const MAX_PERSONAS = 6;

    public function __construct(
        private readonly RegistroResidenteRepositoryInterface $registro,
        private readonly CorreoRepositoryInterface $correo,
    ) {}

    public function execute(DatosRegistroResidente $datos): string
    {
        $this->validar($datos);

        $solicitudId = $this->registro->crearCuentaYSolicitud($datos);

        $this->notificarNuevaSolicitud($datos);

        return $solicitudId;
    }

    private function validar(DatosRegistroResidente $datos): void
    {
        if (! $this->registro->proyectoDisponible($datos->proyectoId())) {
            throw SolicitudInvalidaException::porque('El proyecto seleccionado no está disponible.');
        }

        if (! $this->registro->unidadPerteneceAProyecto($datos->unidadPrincipalId(), $datos->proyectoId())) {
            throw SolicitudInvalidaException::porque('La unidad seleccionada no pertenece al proyecto.');
        }

        $personas = $datos->personas();
        $total = count($personas);

        if ($total < 1 || $total > self::MAX_PERSONAS) {
            throw SolicitudInvalidaException::porPersonasInvalidas();
        }

        $principal = $datos->principal();

        if ($principal === null) {
            throw SolicitudInvalidaException::porque('Debes indicar un residente principal.');
        }

        if (! in_array($principal->tipoVinculo(), [TipoVinculo::Propietario, TipoVinculo::Arrendatario], true)) {
            throw SolicitudInvalidaException::porque('El residente principal debe ser propietario o arrendatario.');
        }

        $ruts = [];
        $emails = [];

        foreach ($personas as $persona) {
            $rut = $this->normalizarRut($persona->rut());
            $email = mb_strtolower(trim($persona->email()));

            if (isset($emails[$email])) {
                throw SolicitudInvalidaException::porEmailDuplicado();
            }

            if (isset($ruts[$rut])) {
                throw SolicitudInvalidaException::porRutDuplicado();
            }

            $emails[$email] = true;
            $ruts[$rut] = true;
        }

        $rutPrincipal = $this->normalizarRut($principal->rut());
        $emailPrincipal = mb_strtolower(trim($principal->email()));

        if ($this->registro->existeEmail($emailPrincipal)) {
            throw new SolicitudDuplicadaException('El correo ya está registrado en la plataforma.');
        }

        if ($this->registro->existeRut($rutPrincipal)) {
            throw SolicitudInvalidaException::porRutAsociadoAOtroCorreo();
        }
    }

    private function normalizarRut(string $rut): string
    {
        try {
            return Rut::desde($rut)->valor();
        } catch (RutInvalidoException $exception) {
            throw SolicitudInvalidaException::porque($exception->getMessage());
        }
    }

    private function notificarNuevaSolicitud(DatosRegistroResidente $datos): void
    {
        $destinatario = (string) config('services.resend.from', '');

        if ($destinatario === '') {
            return;
        }

        $principal = $datos->principal();

        try {
            $this->correo->enviar(new Correo(
                destinatario: $destinatario,
                asunto: 'Nueva solicitud de ingreso de residente',
                html: '<p>Se registró una nueva solicitud de ingreso pendiente de aprobación para la unidad '
                    .e($datos->unidadPrincipalId()).'.</p>'
                    .'<p>Solicitante principal: '.e($principal?->nombre() ?? '').'</p>',
            ));
        } catch (Throwable) {
            // La notificación es best-effort: no debe bloquear el registro.
        }
    }
}
