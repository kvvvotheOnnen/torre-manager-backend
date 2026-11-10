# 2026-11-09 — Paquetería: backend (registro, listado, retiro)

- **Módulo:** Backend / Paquetería
- **Rama:** `test-martin`

## Contexto

Primer módulo preventivo del residente/conserje que pasa a ser funcional.
Solo el conserje/admin registra paquetes cuando llegan físicamente; el residente
solo consulta los suyos.

## Cambios realizados

- Migración `2026_11_09_051728_create_paquetes_table`: tabla `paquetes`
  (proyecto, unidad, residente, descripción, remitente, foto_path, fechas,
  estado, registrado_por).
- Modelo `Paquete` + factory `PaqueteFactory`.
- Feature `Paqueteria` (Domain + Data): entidad, repository interface/impl,
  3 use cases (Registrar, Listar, MarcarRetirado), excepción.
- `PaqueteriaController`: registrar (con foto opcional), listar, retirar.
- Rutas:
  - `GET /api/paqueteria` (residente, conserje, admin, superadmin)
  - `POST /api/paqueteria` (conserje, admin, superadmin)
  - `PATCH /api/paqueteria/{id}/retirar` (conserje, admin, superadmin)
- Binding en `AppServiceProvider`.
- Seeder: 2 paquetes demo (1 pendiente, 1 retirado) para el residente.
- Tests: `PaqueteriaTest` (7 casos).

## Decisiones de diseño

- La foto es opcional (validación `nullable|image|max:5120`).
- Storage en disco `public` (`storage/app/public/paquetes`).
- El residente ve solo sus paquetes; conserje/admin ven los del proyecto.
- Sin notificaciones push (Opción A); se agregará como feature transversal.

## Verificación

- `php artisan test`: 56 passed (249 assertions).
- `vendor/bin/pint --dirty`: PASS.
- Endpoint manual: registro con/sin foto, listado, retiro → OK.
- RBAC: residente no puede registrar → 403.

## Pendientes

- Frontend: pantalla "Mis paquetes" (residente) + "Registrar paquete" (conserje).
- Conectar botón "Paquetería" del dashboard al nuevo flujo.
- Notificaciones push (futuro).
