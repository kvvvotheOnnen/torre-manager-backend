# Bitácora — Superadmin y proyecto activo (Backend)

- **Fecha:** 2026-10-21
- **Branch:** `feature/koke/registropropiedad/21-10-2026`
- **Contexto:** nuevo rol de mayor privilegio y preparación del dashboard unificado.

## Cambios realizados

- **Rol `superadmin`**: agregado al enum de dominio `Rol` (primer caso, mayor jerarquía). Sin migración (el campo `rol` es string).
- **Seeder/Fábrica**: usuario superadmin demo con credenciales vía `env('SUPERADMIN_EMAIL'|'SUPERADMIN_PASSWORD')` y **sin `proyecto_id`** (rol de plataforma, agnóstico del tenant). Estado `superadmin()` en `UserFactory`.
- **Vertical `Superadmin`** (`src/Features/Superadmin/`): entidad `ResumenProyecto`, interfaz `SuperadminRepositoryInterface`, `ListarProyectosConConteosUseCase` e implementación con `withCount`. Endpoint `GET /api/superadmin/proyectos` bajo `role:superadmin`.
- **Proyecto activo** (`Infraestructura`): nueva relación `Proyecto::unidadesPrincipales()` (`hasManyThrough`), método de repositorio `obtenerProyectoActivo(usuarioId, proyectoIdSolicitado)` y `ResolverProyectoActivoUseCase`. El controlador ahora resuelve el proyecto activo en `miProyecto`, `crearAgrupacion`, `crearUnidadAccesoria` y `arbol`, aceptando `?proyecto_id=` (para superadmin) y cayendo al primer proyecto si el usuario no tiene tenant.
- **Herencia de módulos**: las rutas de `/api/infraestructura/*` pasan a `role:administrador,superadmin`.

## Decisiones de diseño

- El superadmin no pertenece a un proyecto; para operar sobre un recinto usa el mismo módulo de Infraestructura indicando `proyecto_id`, cumpliendo la herencia de módulos de administrador acordada.
- El alcance de la vertical Superadmin es mínimo (listado con conteos); el CRUD de proyectos/admins queda para una vertical posterior.

## Verificación

- `php artisan migrate:fresh --seed`: OK.
- curl: `GET /api/superadmin/proyectos` superadmin → 200 con conteos (2 agrupaciones, 18 unidades principales, 4 accesorias); admin → 403; superadmin en `/api/infraestructura/arbol` → 200 (herencia).
- `php artisan test`: 2 passed. `php -l` sin errores.

## Pendientes

- CRUD de proyectos y asignación de administradores (vertical futura).
