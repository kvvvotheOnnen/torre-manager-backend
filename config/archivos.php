<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Almacenamiento de archivos subidos
    |--------------------------------------------------------------------------
    |
    | `driver` selecciona la implementación del puerto AlmacenArchivos.
    | `disco` es el disco de Laravel donde se guardan los archivos.
    | Hoy solo existe `local` (disco `public`). Para S3/Garage ver
    | Features\Archivos\Data\AlmacenS3Compatible.
    |
    */

    'driver' => env('ARCHIVOS_DRIVER', 'local'),

    'disco' => env('ARCHIVOS_DISCO', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Límite por archivo
    |--------------------------------------------------------------------------
    |
    | Se valida en cada controlador con `max:archivos.max_kb`. El contenedor
    | no incluye GD ni Imagick, por lo que no se redimensiona ni comprime.
    |
    */

    'max_kb' => (int) env('ARCHIVOS_MAX_KB', 5120),

    /*
    |--------------------------------------------------------------------------
    | Carpetas administradas
    |--------------------------------------------------------------------------
    |
    | El comando `archivos:limpiar-huerfanos` solo revisa estas carpetas y
    | nunca borra un archivo que esté referenciado por un registro.
    |
    */

    'carpetas' => [
        'paquetes',
        'servicios',
    ],

];
