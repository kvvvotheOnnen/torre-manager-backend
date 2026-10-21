# Bitácora — Tarea 2: Capa de Datos e Infraestructura (Backend)

- **Fecha:** 2026-10-21
- **Branch:** `feature/koke/registropropiedad/21-10-2026`
- **Contexto:** HU-04, vertical de Infraestructura.

## Cambios realizados

- **Migraciones** (`database/migrations/`): `proyectos`, `agrupaciones` (unique `(proyecto_id, nombre)`), `unidades_principales` (unique `(agrupacion_id, identificador)`), `unidades_accesorias` (unique `(proyecto_id, tipo, identificador)`, FK `unidad_principal_id` nullable con `nullOnDelete`) y `proyecto_id` nullable en `usuarios` para scoping multi-tenant.
- **Modelos Eloquent** (`app/Models/`): `Proyecto`, `Agrupacion`, `UnidadPrincipal`, `UnidadAccesoria` (con `$table` explícito por nombres en español y `toEntity()`), y `User` actualizado con relación `proyecto`.
- **Repositorio** (`src/Features/Infraestructura/Data/Repositories/InfraestructuraRepository.php`): implementa la interfaz de dominio con transacción para la cascada, traduce `QueryException` a excepciones de dominio y retorna entidades puras. Único punto con `Log::error` (infraestructura).
- **Factories**: `ProyectoFactory`, `AgrupacionFactory`, `UnidadPrincipalFactory`, `UnidadAccesoriaFactory` (datos dummy).
- **Seeders**: `InfraestructuraSeeder` (Torre A 3×4 unidades, Manzana 1 con 6 casas, accesorias asignadas y de libre disposición) y `DatabaseSeeder` reescrito: usuarios admin/residente/conserje con credenciales vía `env()` (sin secrets en duro) y `proyecto_id` asignado.

## Decisiones de diseño

- La unicidad de agrupaciones y unidades se valida a nivel de negocio (repositorio) y de base de datos (índices únicos) — defensa en profundidad.
- Las unidades accesorias se crean a nivel de proyecto; la asignación opcional se maneja con FK nullable y `nullOnDelete` (si se elimina la unidad principal, la accesoria vuelve a libre disposición).

## Verificación

- `php artisan migrate:fresh --seed`: OK (1 proyecto, 2 agrupaciones, 18 unidades principales, 4 accesorias, 3 usuarios).
- `php -l` sin errores en los archivos nuevos/modificados.

## Pendientes

- Endpoints REST y middleware RBAC (Tarea 3).
