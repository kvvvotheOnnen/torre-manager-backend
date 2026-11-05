# Seeder de Vanguardia Center

- **Fecha:** 2026-11-04
- **Branch:** `main`
- **Módulo:** Backend / Seeders

## Contexto

El seeder demo creaba `Condominio Demo`. Se reemplazó por el proyecto real de
pruebas `Vanguardia Center`, para que cualquier desarrollador pueda replicarlo
con `php artisan migrate --seed`.

## Cambios realizados

- Nuevo `ProyectoVanguardiaSeeder` con:
  - Proyecto `Vanguardia Center`, tipo edificio, descripción
    `Edifico de residentes`, módulo `infraestructura` y estado `completo`.
  - Agrupación `Edificio` con pisos 8 a 26 y 16 unidades por piso (304).
  - Instalación extra `Quincho`, capacidad 25.
  - 50 bodegas (`B-001`..`B-050`) y 100 estacionamientos
    (`E-001`..`E-100`).
  - Asignaciones iniciales de 3 bodegas y 4 estacionamientos.
- `DatabaseSeeder` ahora crea el proyecto Vanguardia y asigna a los usuarios
  demo administrador, residente y conserje a ese proyecto.
- Eliminado `InfraestructuraSeeder`.
- Nuevo test `SeederVanguardiaTest` que valida el seeder sobre SQLite en
  memoria.

## Decisiones de diseño

- Se mantiene el nombre exacto `Vanguardia Center` y la descripción original.
- Las credenciales siguen viniendo de variables de entorno, con los valores por
  defecto de desarrollo.
- El seeder no se ejecuta automáticamente sobre PostgreSQL al correr tests.

## Verificación

- `php artisan test` — 33 tests, 144 aserciones, todo en verde.
- `php artisan migrate:fresh --seed` en local:
  - 1 proyecto: `Vanguardia Center`
  - 304 unidades principales
  - 50 bodegas y 100 estacionamientos
  - 7 accesorias asignadas
  - 1 instalación extra
  - 4 usuarios demo
- Login de superadmin contra la API — HTTP 200.

## Pendientes

- Ninguno.
