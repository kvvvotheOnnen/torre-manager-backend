# Bitácora — Edición de proyectos (Backend)

- **Fecha:** 2026-10-27
- **Branch:** `feature/koke/registropropiedad/21-10-2026`
- **Contexto:** El módulo "Gestión de Proyectos" (superadmin) solo permitía crear proyectos. Se agregó la capacidad de consultar el detalle y editar los datos del proyecto.

## Cambios realizados

- `GET /api/superadmin/proyectos/{id}` → `SuperadminController::verProyecto` (200 con detalle; 404 si no existe).
- `PUT /api/superadmin/proyectos/{id}` → `SuperadminController::actualizarProyecto` (200 con detalle; 404; 422).
- `ActualizarProyectoRequest`: valida `nombre`, `descripcion`, `modulos`, `instalaciones` (con `distinct` por nombre) y `admin_ids`. No acepta `tipo_condominio` ni `agrupaciones`.
- `ActualizarProyectoUseCase`: validaciones de dominio (nombre obligatorio, al menos un módulo válido, al menos un administrador, instalaciones con nombre).
- `BuscarProyectoUseCase`: obtención del detalle.
- `SuperadminRepositoryInterface` / `SuperadminRepository`: `buscarProyecto` y `actualizarProyecto`.

## Decisiones de diseño

- La actualización corre en una transacción: `update` de `nombre`, `descripcion` y `modulos`; sincronización de instalaciones extra y `sync` del pivote de administradores.
- Las instalaciones se reconcilian **por nombre**: si existe se actualiza su `tipo` (conservando `atributos` e id); si no existe se crea con sus atributos; las ausentes se eliminan. Evita violar el índice único `(proyecto_id, nombre)`.
- El `tipo_condominio` no es editable: cambiarlo invalidaría la estructura ya generada (torres/manzanas/unidades).
- La estructura del recinto queda fuera de esta tarea (se administra desde el módulo Infraestructura).
- Los administradores se validan contra el rol `administrador`; si alguno no cumple se responde 404 con mensaje de dominio.

## Verificación

- curl (dentro del entorno local):
  - `GET /api/superadmin/proyectos/{id}` → 200; id inexistente → 404.
  - `PUT` → 200 y cambios persistidos (nombre, descripción, módulos, instalaciones, administradores).
  - `PUT` sin módulos y sin administradores → 422.
  - `PUT` con token de administrador → 403.
  - Instalación nueva con atributos → se persisten; instalación existente → conserva atributos e id; eliminación de todas → lista vacía.
- `php artisan test`: 2 passed. `php -l` sin errores en los archivos nuevos/modificados.

## Pendientes

- Edición de la estructura del recinto (agrupaciones y unidades) posterior a la creación.
