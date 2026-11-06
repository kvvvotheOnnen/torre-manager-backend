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
    case RegistroConserjes = 'registro_conserjes';
    case CambioUnidadResidente = 'cambio_unidad_residente';
    case SolicitudesResidente = 'solicitudes_residente';
    case RegistroEmpleados = 'registro_empleados';
    case PersonasExternasOficios = 'personas_externas_oficios';
    case AlertasNoticias = 'alertas_noticias';

    public function nombre(): string
    {
        return match ($this) {
            self::RegistroConserjes => 'Registro de conserjes',
            self::CambioUnidadResidente => 'Cambio de unidad de residente',
            self::SolicitudesResidente => 'Solicitudes de nuevo residente',
            self::RegistroEmpleados => 'Registro de otros empleados',
            self::PersonasExternasOficios => 'Personas externas para oficios',
            self::AlertasNoticias => 'Alertas y noticias',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::RegistroConserjes => 'Registra y administra los conserjes del recinto.',
            self::CambioUnidadResidente => 'Gestiona el cambio de unidad de un residente.',
            self::SolicitudesResidente => 'Acepta o rechaza solicitudes de nuevos residentes.',
            self::RegistroEmpleados => 'Registra al personal de aseo y otros empleados.',
            self::PersonasExternasOficios => 'Registra personas externas que realizan oficios.',
            self::AlertasNoticias => 'Crea alertas y noticias para la comunidad.',
        };
    }
}
