<?php

/*
|--------------------------------------------------------------------------
| Enforcement de módulos por capacidad
|--------------------------------------------------------------------------
|
| Relaciona cada capacidad con el módulo que la contiene. El middleware
| `puede:<capacidad>` exige, además de la capacidad, que ese módulo esté
| habilitado para el proyecto activo.
|
| Un módulo nuevo queda protegido por API en cuanto declara aquí sus
| capacidades. Las capacidades sin entrada no dependen del estado de módulos
| (p. ej. `mi_informacion.ver` o `asistente.consultar`).
|
| El superadmin está exento de esta verificación (ver VerificadorModuloProyecto).
|
*/

return [
    'capacidades' => [
        'paqueteria' => [
            'paqueteria.ver',
            'paqueteria.gestionar',
        ],
        'servicios_recomendados' => [
            'servicios.ver',
            'servicios.gestionar',
        ],
        'marketplace' => [
            'marketplace.ver',
            'marketplace.publicar',
            'marketplace.gestionar',
            'marketplace.moderar',
            'marketplace.favoritos',
        ],
        'solicitudes_residente' => [
            'residentes.gestionar',
        ],
        'asistente_ia_admin' => [
            'asistente.gestionar',
        ],
        'instalaciones_extra' => [
            'reservas.solicitar',
            'reservas.gestionar',
        ],
        'gastos_comunes' => [
            'gastos.ver_propio',
            'gastos.emitir',
        ],
        'control_acceso' => [
            'control_acceso.bitacora',
            'control_acceso.registrar',
            'control_acceso.mis_visitas',
        ],
        'alertas_noticias' => [
            'alertas.leer',
            'alertas.publicar',
        ],
    ],
];
