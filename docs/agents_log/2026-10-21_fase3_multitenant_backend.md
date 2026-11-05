# Bitácora — Fase 3: Enrutamiento Multi-Tenant (Backend)

- **Fecha:** 2026-10-21
- **Branch:** `feature/koke/registropropiedad/21-10-2026`
- **Contexto:** Fase 3, contexto de proyecto activo por administrador.

## Cambios realizados

- **`GET /api/mis-proyectos`** (`role:administrador,superadmin`): devuelve los proyectos asociados al usuario vía la pivote `administrador_proyecto`, con `tipo_condominio`, `modulos`, `instalaciones_extra` y `admin_ids` (superadmin ve todos).
- **Proyecto activo con pertenencia**: `InfraestructuraRepository::obtenerProyectoActivo` ahora valida:
  - superadmin: cualquier `proyecto_id` solicitado (o el primero).
  - administrador: solo puede operar sobre proyectos donde está anexado (pivote) o su `proyecto_id` legado; un proyecto ajeno devuelve 404.
- **Header de contexto**: el controlador de Infraestructura acepta el proyecto activo por cabecera `X-Proyecto-Id` (con fallback a `?proyecto_id=`), de forma consistente en `miProyecto`, `arbol`, `crearAgrupacion` y `crearUnidadAccesoria`.

## Decisiones de diseño

- Los administradores heredan los módulos del proyecto al que ingresan; el backend solo expone los proyectos permitidos y valida la pertenencia en cada operación de infraestructura.

## Verificación

- curl: admin `mis-proyectos` → su proyecto; admin con `X-Proyecto-Id` propio → 200; con proyecto ajeno → 404; superadmin con cualquier id → 200; residente → 403.
- `php artisan test` (2 passed). `php -l` sin errores.

## Pendientes

- Frontend: selector de proyecto y envío automático del header.
