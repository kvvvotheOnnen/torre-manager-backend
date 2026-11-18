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
| 'requiere_modulo' => (opcional) módulo configurable que debe estar habilitado
| 'acciones'        => (opcional) nombre de acción => capacidad que la habilita.
|                      El endpoint devuelve cada una ya resuelta como boolean,
|                      para que el cliente muestre u oculte botones sin conocer roles.
|
*/

return [
    'funciones' => [
        [
            'id' => 'servicios_recomendados',
            'titulo' => 'Servicios recomendados',
            'descripcion' => 'Directorio de trabajadores y servicios recomendados para el edificio.',
            'icono' => 'handyman',
            'capacidades' => ['servicios.ver'],
            'acciones' => ['gestionar' => 'servicios.gestionar'],
            'ambito' => 'proyecto',
            'ruta' => '/servicios',
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
                'favoritos' => 'marketplace.favoritos',
            ],
            'ambito' => 'proyecto',
            'ruta' => '/marketplace',
        ],
    ],
];
