# Merge de `origin/main` en `main` (paquetería + asistente IA)

- **Fecha:** 2026-11-10
- **Branch:** `main`
- **Contexto:** La rama `main` local había divergido de `origin/main`. La rama local contenía la integración de Asistente IA (commit `8e2a457`) y la rama remota contenía la implementación de Paquetería (commit `928b079`), ambas sobre la base `80ad079`. Ningún commit estaba pusheado desde el lado local.

## Cambios realizados

- Simulación de merge con `git merge-tree --write-tree`: sin conflictos textuales.
- Merge de `origin/main` en `main` con estrategia `ort`, generando el commit de merge `6dc8739`.
- `composer install` en el contenedor `app` (lockfile ya incluía `smalot/pdfparser`).
- `php artisan migrate --force`: se aplicaron las migraciones de Paquetería (`create_paquetes_table` y `add_retirado_por_nombre_to_paquetes_table`).
- `php artisan test`: 78 pruebas aprobadas (296 aserciones).

## Decisiones de diseño

- Se eligió **merge** en lugar de rebase para no reescribir el commit local y mantener la trazabilidad de ambas líneas de trabajo en una rama compartida.
- No se forzó resolución manual: el merge automático combinó correctamente `routes/api.php` (rutas de asistente y paquetería) y `app/Providers/AppServiceProvider.php` (bindings de ambos features).

## Verificación

- Árbol de trabajo limpio.
- `routes/api.php` incluye las rutas de Asistente (`admin/asistente`, `residente/asistente/consultas`) y de Paquetería (`paqueteria`, `paqueteria/unidades`, etc.).
- `AppServiceProvider` registra los repositorios de Asistente y Paquetería.
- Suite completa de tests en verde.
- Migraciones aplicadas sin errores.

## Pendientes

- El `git push` a `origin/main` queda a cargo del usuario (push manual).
