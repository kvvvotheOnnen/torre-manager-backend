<?php

/*
|--------------------------------------------------------------------------
| Mapa de capacidades por rol
|--------------------------------------------------------------------------
|
| Único lugar donde se relaciona un rol con lo que puede hacer. Los módulos
| y el middleware preguntan por capacidad, nunca por nombre de rol.
|
| Si mañana cambian los roles, solo se edita este archivo.
| El comodín '*' concede todas las capacidades.
|
*/

return [
    'roles' => [
        'superadmin' => ['*'],

        'administrador' => [
            'control_acceso.bitacora',
            'control_acceso.registrar',
            'control_acceso.mis_visitas',
            'alertas.leer',
            'alertas.publicar',
            'reservas.solicitar',
            'reservas.gestionar',
            'gastos.ver_propio',
            'gastos.emitir',
            'residentes.gestionar',
            'asistente.gestionar',
            'paqueteria.ver',
            'paqueteria.gestionar',
            'servicios.ver',
            'servicios.gestionar',
            'marketplace.ver',
            'marketplace.publicar',
            'marketplace.gestionar',
            'marketplace.moderar',
            'marketplace.aprobar',
            'notificaciones.ver',
        ],

        'conserje' => [
            'control_acceso.bitacora',
            'control_acceso.registrar',
            'alertas.leer',
            'reservas.gestionar',
            'residentes.gestionar',
            'paqueteria.ver',
            'paqueteria.gestionar',
            'servicios.ver',
            'marketplace.ver',
            'marketplace.moderar',
            'marketplace.aprobar',
            'notificaciones.ver',
        ],

        'propietario' => [
            'control_acceso.mis_visitas',
            'alertas.leer',
            'reservas.solicitar',
            'gastos.ver_propio',
            'mi_informacion.ver',
            'asistente.consultar',
            'paqueteria.ver',
            'servicios.ver',
            'marketplace.ver',
            'marketplace.publicar',
            'marketplace.favoritos',
            'notificaciones.ver',
        ],

        'residente' => [
            'control_acceso.mis_visitas',
            'alertas.leer',
            'reservas.solicitar',
            'gastos.ver_propio',
            'mi_informacion.ver',
            'asistente.consultar',
            'paqueteria.ver',
            'servicios.ver',
            'marketplace.ver',
            'marketplace.publicar',
            'marketplace.favoritos',
            'notificaciones.ver',
        ],
    ],
];
