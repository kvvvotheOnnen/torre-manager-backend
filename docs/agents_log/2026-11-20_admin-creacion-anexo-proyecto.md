# 2026-11-20 — Crear administrador anexado a proyectos

## Contexto

- **Branch:** `feature/koke/cambios-en-creacion-de-administrador/20-11-2026`
- El superadmin podía crear cuentas de administrador, pero solo se anexaban a
  un proyecto desde la creación o edición del proyecto. Faltaba hacerlo al
  momento de crear la cuenta.

## Cambios realizados

- Entidad de dominio `ProyectoAdministrador` (id, nombre) y `Administrador` con
  `proyectos()`.
- `CrearAdministradorRequest` acepta `proyecto_ids` (`sometimes|array`,
  cada id debe existir).
- `CrearAdministradorUseCase` recibe `?array $proyectosIds`.
- `SuperadminRepository::crearAdministrador`:
  - valida que todos los proyectos existan (si falta alguno →
    `RecursoNoEncontradoException`, rollback total);
  - dentro de `DB::transaction` crea el usuario y anexa una membresía activa
    (`proyecto_usuario`, rol `administrador`) por cada proyecto;
  - sin `proyecto_ids` mantiene el comportamiento anterior (cuenta sin
    proyecto).
- `listarAdministradores` hace eager-load de las membresías activas y expone
  `proyectos` en `GET /api/superadmin/administradores`. La respuesta de
  creación también incluye `proyectos`.
- Controller: catch de `RecursoNoEncontradoException` → 404.

## Verificación

- `docker compose exec -T app php vendor/bin/phpunit`: **190 tests OK**.
- Nuevos tests `tests/Feature/SuperadminAdministradoresTest.php`: creación sin
  proyectos, creación multi-proyecto, proyecto inexistente rechazado sin crear
  usuario, correo duplicado y listado con proyectos.
- `vendor/bin/pint` sobre los archivos modificados.

## Pendientes

- Si más adelante se requiere anexar/desanexar administradores fuera de la
  edición de proyecto, exponer endpoints dedicados de membresía.
