<?php

namespace Features\Dashboard\Domain\Entities;

/**
 * Catálogo de módulos globales.
 *
 * Estos módulos no se persisten en `proyectos.modulos` y están siempre
 * habilitados para cualquier proyecto existente o nuevo. Agregar un módulo
 * global requiere agregar un caso aquí y su entrada visual/ruta en frontend.
 */
enum ModuloGeneral: string
{
    case Paqueteria = 'paqueteria';
    case InstalacionesExtra = 'instalaciones_extra';
    case GastosComunes = 'gastos_comunes';
    case ControlAcceso = 'control_acceso';
    case RegistroConserjes = 'registro_conserjes';
    case CambioUnidadResidente = 'cambio_unidad_residente';
    case SolicitudesResidente = 'solicitudes_residente';
    case RegistroEmpleados = 'registro_empleados';
    case PersonasExternasOficios = 'personas_externas_oficios';
    case AlertasNoticias = 'alertas_noticias';
    case AsistenteIaAdmin = 'asistente_ia_admin';

    public function nombre(): string
    {
        return match ($this) {
            self::Paqueteria => 'Paquetería',
            self::InstalacionesExtra => 'Instalaciones extra',
            self::GastosComunes => 'Gastos comunes',
            self::ControlAcceso => 'Control de acceso',
            self::RegistroConserjes => 'Registro de conserjes',
            self::CambioUnidadResidente => 'Cambio de unidad de residente',
            self::SolicitudesResidente => 'Solicitudes de nuevo residente',
            self::RegistroEmpleados => 'Registro de otros empleados',
            self::PersonasExternasOficios => 'Personas externas para oficios',
            self::AlertasNoticias => 'Alertas y noticias',
            self::AsistenteIaAdmin => 'IA admin',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Paqueteria => 'Trazabilidad de encomiendas del recinto.',
            self::InstalacionesExtra => 'Reserva y administración de instalaciones extra.',
            self::GastosComunes => 'Pago y gestión de gastos comunes en línea.',
            self::ControlAcceso => 'Registro y control de accesos al recinto.',
            self::RegistroConserjes => 'Registra y administra los conserjes del recinto.',
            self::CambioUnidadResidente => 'Gestiona el cambio de unidad de un residente.',
            self::SolicitudesResidente => 'Acepta o rechaza solicitudes de nuevos residentes.',
            self::RegistroEmpleados => 'Registra al personal de aseo y otros empleados.',
            self::PersonasExternasOficios => 'Registra personas externas que realizan oficios.',
            self::AlertasNoticias => 'Crea alertas y noticias para la comunidad.',
            self::AsistenteIaAdmin => 'Carga el documento PDF que usará la IA para responder a los residentes.',
        };
    }
}
