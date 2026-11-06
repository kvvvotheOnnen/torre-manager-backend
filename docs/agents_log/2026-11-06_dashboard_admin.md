# Dashboard de administrador - Catálogo backend de módulos

- **Fecha:** 2026-11-06
- **Branch:** `feature/koke/dashboard-admin/2026-11-06`
- **Contexto:** Task 9, dashboard de administrador con módulos generales y módulos configurables por proyecto.

## Cambios realizados

- Se agregó la feature `Dashboard` en `src/Features/Dashboard/` con capas Domain y Data.
- Se creó el catálogo backend de módulos globales en `ModuloGeneral`.
- Se extendió `ModuloSistema` con nombre y descripción para los módulos configurables.
- Se agregó el caso de uso `ObtenerModulosDashboardUseCase`.
- Se agregó el endpoint `GET /api/dashboard/modulos`, protegido para `administrador` y `superadmin`.
- El endpoint devuelve todos los módulos configurables con su estado `habilitado` según `proyectos.modulos`, y todos los módulos globales siempre habilitados.
- Se agregó el binding `DashboardRepositoryInterface` en `AppServiceProvider`.
- Se corrigió `listarProyectosDelUsuario` para incluir proyectos asociados por la tabla pivote y por el campo legado `usuarios.proyecto_id`, deduplicando resultados.
- Se agregaron pruebas de feature para catálogo, cambio de proyecto activo, acceso denegado y `mis-proyectos`.

## Decisiones de diseño

- Los módulos globales no se persisten en base de datos. Se definen en código backend y aplican a todos los proyectos existentes y futuros.
- `ModuloSistema` queda reservado exclusivamente para módulos configurables por proyecto.
- El frontend no hardcodea el catálogo: consume el endpoint canónico.
- Los módulos configurables ausentes del proyecto se informan como `habilitado=false` para que el dashboard los muestre bloqueados.
- Agregar un módulo global nuevo requiere un despliegue de backend y su entrada visual/ruta en frontend, pero no una migración.

## Verificación

- `php artisan test`: 37 tests aprobados, 178 aserciones.
- `vendor/bin/pint --dirty`: sin errores.

## Pendientes

- Agregar middleware de habilitación de módulo a futuros endpoints de módulos configurables. La visibilidad bloqueada del dashboard no reemplaza la autorización backend.
- Implementar las pantallas reales de los módulos generales (por ahora abren una vista de “Próximamente”).
