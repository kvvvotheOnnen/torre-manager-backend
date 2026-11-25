<?php

/*
|--------------------------------------------------------------------------
| Catálogo de funciones del dashboard
|--------------------------------------------------------------------------
|
| Cada entrada describe una función que el frontend puede dibujar como
| tarjeta. No se declaran roles: solo las capacidades que exige.
|
| Forma de una entrada:
|
| 'id'              => identificador estable, único (string)
| 'titulo'          => texto visible
| 'descripcion'     => texto de apoyo
| 'icono'           => nombre de ícono interpretado por el cliente
| 'capacidades'     => capacidades requeridas; todas deben cumplirse
| 'ambito'          => proyecto | unidad | propio (ver AmbitoFuncion)
| 'ruta'            => ruta de destino en el cliente
| 'requiere_modulo' => (opcional) módulo que debe estar habilitado
| 'acciones'        => (opcional) nombre de acción => capacidad que la habilita.
|                      El endpoint devuelve cada una ya resuelta como boolean,
|                      para que el cliente muestre u oculte botones sin conocer roles.
|
*/

return [
    'funciones' => [
        [
            'id' => 'mi_informacion',
            'titulo' => 'Mi información',
            'descripcion' => 'Consulta tu información y las asignaciones de servicio de tu unidad.',
            'icono' => 'info',
            'capacidades' => ['mi_informacion.ver'],
            'ambito' => 'propio',
            'ruta' => '/residente/mi-informacion',
        ],
        [
            'id' => 'asistente_residente',
            'titulo' => 'Asistente IA',
            'descripcion' => 'Consultas sobre el reglamento y la infraestructura del edificio.',
            'icono' => 'star',
            'capacidades' => ['asistente.consultar'],
            'ambito' => 'proyecto',
            'ruta' => '/residente/ia',
        ],
        [
            'id' => 'paqueteria',
            'titulo' => 'Paquetería',
            'descripcion' => 'Avisos, registro y retiro de paquetes del recinto.',
            'icono' => 'package',
            'capacidades' => ['paqueteria.ver'],
            'acciones' => ['gestionar' => 'paqueteria.gestionar'],
            'ambito' => 'proyecto',
            'ruta' => '/paqueteria',
            'requiere_modulo' => 'paqueteria',
        ],
        [
            'id' => 'reservas_solicitar',
            'titulo' => 'Reserva de espacios comunes',
            'descripcion' => 'Reserva quincho, piscina y otras instalaciones del recinto.',
            'icono' => 'event',
            'capacidades' => ['reservas.solicitar'],
            'ambito' => 'proyecto',
            'ruta' => '/admin/modulos/instalaciones_extra',
            'requiere_modulo' => 'instalaciones_extra',
        ],
        [
            'id' => 'reservas_gestionar',
            'titulo' => 'Gestión de instalaciones extra',
            'descripcion' => 'Administra la reserva y el uso de espacios comunes.',
            'icono' => 'event',
            'capacidades' => ['reservas.gestionar'],
            'ambito' => 'proyecto',
            'ruta' => '/admin/modulos/instalaciones_extra',
            'requiere_modulo' => 'instalaciones_extra',
        ],
        [
            'id' => 'gastos_comunes',
            'titulo' => 'Gastos comunes',
            'descripcion' => 'Consulta y pago de los gastos comunes de tu unidad.',
            'icono' => 'payments',
            'capacidades' => ['gastos.ver_propio'],
            'ambito' => 'unidad',
            'ruta' => '/admin/modulos/gastos_comunes',
            'requiere_modulo' => 'gastos_comunes',
        ],
        [
            'id' => 'control_acceso_propio',
            'titulo' => 'Mis visitas',
            'descripcion' => 'Visitantes que entraron y accesos de personas de confianza.',
            'icono' => 'badge',
            'capacidades' => ['control_acceso.mis_visitas'],
            'ambito' => 'unidad',
            'ruta' => '/admin/modulos/control_acceso',
            'requiere_modulo' => 'control_acceso',
        ],
        [
            'id' => 'control_acceso_bitacora',
            'titulo' => 'Control de acceso',
            'descripcion' => 'Registro y control de accesos al recinto.',
            'icono' => 'badge',
            'capacidades' => ['control_acceso.bitacora'],
            'acciones' => ['registrar' => 'control_acceso.registrar'],
            'ambito' => 'proyecto',
            'ruta' => '/admin/modulos/control_acceso',
            'requiere_modulo' => 'control_acceso',
        ],
        [
            'id' => 'solicitudes_residente',
            'titulo' => 'Gestión de residentes',
            'descripcion' => 'Aprueba o rechaza solicitudes de nuevos residentes.',
            'icono' => 'domain',
            'capacidades' => ['residentes.gestionar'],
            'ambito' => 'proyecto',
            'ruta' => '/admin/modulos/solicitudes_residente',
            'requiere_modulo' => 'solicitudes_residente',
        ],
        [
            'id' => 'alertas_noticias',
            'titulo' => 'Alertas y noticias',
            'descripcion' => 'Comunicados y avisos para la comunidad.',
            'icono' => 'campaign',
            'capacidades' => ['alertas.leer'],
            'acciones' => ['publicar' => 'alertas.publicar'],
            'ambito' => 'proyecto',
            'ruta' => '/admin/modulos/alertas_noticias',
            'requiere_modulo' => 'alertas_noticias',
        ],
        [
            'id' => 'asistente_ia_admin',
            'titulo' => 'IA administrativa',
            'descripcion' => 'Carga el documento PDF que usará la IA para responder a los residentes.',
            'icono' => 'domain',
            'capacidades' => ['asistente.gestionar'],
            'ambito' => 'proyecto',
            'ruta' => '/admin/modulos/asistente_ia_admin',
            'requiere_modulo' => 'asistente_ia_admin',
        ],
        [
            'id' => 'servicios_recomendados',
            'titulo' => 'Servicios recomendados',
            'descripcion' => 'Directorio de trabajadores y servicios recomendados para el edificio.',
            'icono' => 'handyman',
            'capacidades' => ['servicios.ver'],
            'acciones' => ['gestionar' => 'servicios.gestionar'],
            'ambito' => 'proyecto',
            'ruta' => '/servicios',
            'requiere_modulo' => 'servicios_recomendados',
        ],
        [
            'id' => 'marketplace',
            'titulo' => 'Marketplace',
            'descripcion' => 'Compra y vende artículos entre vecinos del condominio.',
            'icono' => 'storefront',
            'capacidades' => ['marketplace.ver'],
            'acciones' => [
                'publicar' => 'marketplace.publicar',
                'gestionar' => 'marketplace.gestionar',
                'moderar' => 'marketplace.moderar',
                'aprobar' => 'marketplace.aprobar',
                'favoritos' => 'marketplace.favoritos',
            ],
            'ambito' => 'proyecto',
            'ruta' => '/marketplace',
            'requiere_modulo' => 'marketplace',
        ],
    ],
];
