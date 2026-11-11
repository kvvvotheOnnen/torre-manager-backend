# 2026-11-09 — Paquetería: edición de paquete (backend)

- **Módulo:** Backend / Paquetería

## Cambios realizados

- `PaqueteriaController::actualizar`: PUT `/api/paqueteria/{id}` (conserje/admin/superadmin).
  Actualiza descripción, remitente, residente, unidad y foto (opcional).
  Reemplaza la foto anterior si se envía una nueva.
- `ActualizarPaqueteUseCase` + método `actualizar` en repository/interface.
- `retirar` ahora devuelve el paquete completo (no solo id/estado/fecha).
- Ruta `PUT /api/paqueteria/{id}` registrada.
- Seeder y tests sin cambios (7 tests siguen pasando).

## Verificación

- `php artisan test`: 56 passed (249 assertions).
- `pint --dirty`: PASS.
- Endpoint manual: PUT con descripción/remitente → OK.
- Retiro ahora devuelve paquete completo → OK.
