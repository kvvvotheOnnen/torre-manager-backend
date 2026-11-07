# Restricción de infraestructura a superadmin

- **Fecha:** 2026-11-06
- **Branch:** `feature/koke/dashboard-admin/2026-11-06`
- **Contexto:** Corrección de seguridad: el dashboard de administrador exponía el módulo de infraestructura.

## Cambios realizados

- Los endpoints de `infraestructura` pasaron de `role:administrador,superadmin` a `role:superadmin`.
- Se agregó `ModuloSistema::esExclusivoSuperadmin()` para marcar Infraestructura como capacidad exclusiva de superadmin.
- `CatalogoModulosDashboard` omite Infraestructura para usuarios que no son superadmin.
- `ObtenerModulosDashboardUseCase` recibe el flag de superadmin.
- `DashboardController` pasa el rol del usuario autenticado al caso de uso.
- Se actualizaron las pruebas del catálogo:
  - administrador recibe 10 módulos y nunca Infraestructura;
  - superadmin recibe 11 módulos e Infraestructura habilitada.
- Se agregó `InfraestructuraAutorizacionTest` con casos 403 para administrador y acceso correcto para superadmin.
- Se actualizó `AGENTS.md` con la regla de exclusividad de superadmin.

## Decisiones de diseño

- `infraestructura` se mantiene en `ModuloSistema` y en `proyectos.modulos` para no migrar datos ni romper el wizard de proyectos.
- El catálogo del dashboard filtra la capacidad según rol.
- La autorización real queda en backend; el frontend no es el control de seguridad.

## Verificación

- `php artisan test`: 41 tests aprobados, 185 aserciones.
- `vendor/bin/pint --dirty`: sin errores.

## Pendientes

- Limpieza futura del wizard si se decide retirar `infraestructura` de los módulos configurables.
