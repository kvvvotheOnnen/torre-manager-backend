# 2026-11-09 — Residente: endpoint "Mi información" (backend)

- **Módulo:** Backend / Residente
- **Rama:** `test-martin`

## Contexto

El dashboard del residente tenía el botón "Mi información" como preventivo
(vista "Próximamente"). Se implementó el primer endpoint real para que el
residente pueda consultar los datos de su unidad asignada.

## Cambios realizados

- Migración `2026_11_09_042250_add_residente_id_to_unidades_principales_table`:
  agrega `residente_id` (nullable, unique, FK a `usuarios`, nullOnDelete) a
  `unidades_principales`.
- Modelo `UnidadPrincipal`: `residente_id` en fillable + relación `residente()`.
- Seeder: asigna la cuenta demo del residente a la unidad `Edificio-801`.
- Feature `Residente` (Domain + Data):
  - `MiInformacion` (entidad), `ResidenteRepositoryInterface`,
    `ObtenerMiInformacionUseCase`, `UnidadNoAsignadaException`.
  - `ResidenteRepository`: busca la unidad por `residente_id` con agrupación.
- `ResidenteController::miInformacion`: devuelve `{ unidad, agrupacion }` o 404.
- Ruta `GET /api/residente/mi-informacion` protegida por `role:residente`.
- Binding en `AppServiceProvider`.
- Tests: `ResidenteMiInformacionTest` (4 casos).

## Decisiones de diseño

- Un residente = una sola unidad (`residente_id` único en `unidades_principales`).
- El endpoint devuelve solo datos de la unidad (identificador + agrupación). No
  incluye instalaciones extra ni módulos habilitados (eso es solo para admin).
- No incluye el nombre del proyecto (decisión del usuario).

## Verificación

- `php artisan test`: 49 passed (233 assertions).
- `vendor/bin/pint --dirty`: PASS (12 archivos).
- Endpoint manual: `GET /api/residente/mi-informacion` →
  `{ "unidad": "Edificio-801", "agrupacion": "Edificio" }`.
- RBAC: admin/superadmin → 403.

## Pendientes

- Conectar el botón "Mi información" del frontend al nuevo endpoint.
- Implementar los demás módulos (Paquetería, Quincho, Gastos comunes,
  Control de acceso, Notificaciones) en backend.
