# Módulos operativos pasan a ser generales

- **Fecha:** 2026-11-06
- **Branch:** `feature/koke/dashboard-admin/2026-11-06`
- **Contexto:** Paquetería, instalaciones extra, gastos comunes y control de acceso aparecían bloqueados en proyectos que no los tenían en `proyectos.modulos`.

## Cambios realizados

- Se agregaron a `ModuloGeneral`:
  - `paqueteria`
  - `instalaciones_extra` (reemplaza el nombre visible “Quincho”)
  - `gastos_comunes`
  - `control_acceso`
- `ModuloSistema` quedó únicamente con `infraestructura`, que sigue siendo exclusivo de superadmin.
- Los endpoints de infraestructura se mantienen restringidos a `role:superadmin`.
- Se actualizaron las validaciones de creación, edición y borrador para aceptar solo `infraestructura` en `proyectos.modulos`.
- Se hizo tolerante la lectura de `proyectos.modulos` para ignorar IDs antiguos desconocidos.
- Se agregó la migración `2026_11_06_000001_remove_general_modules_from_proyectos.php`, que elimina de proyectos existentes los módulos que ahora son generales.
- Se actualizaron factories, seeders afectados y pruebas.
- Se agregó prueba de la migración de limpieza de módulos.
- Se actualizó `AGENTS.md` para dejar explícito que los módulos operativos administrativos viven en `ModuloGeneral`.

## Resultado

- Un administrador recibe 10 módulos generales habilitados y nunca Infraestructura.
- Un superadmin recibe los 10 módulos generales más Infraestructura.
- Los proyectos existentes dejan de depender de `proyectos.modulos` para mostrar paquetería, instalaciones extra, gastos comunes y control de acceso.

## Verificación

- `php artisan test`: 42 tests aprobados, 220 aserciones.
- `vendor/bin/pint --dirty`: sin errores.
- Migración ejecutada en el entorno Docker local.

## Nota

Infraestructura sigue siendo visible e interactuable solo para superadmin, tanto en frontend como en backend.
