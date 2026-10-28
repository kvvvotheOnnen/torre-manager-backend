# Backend - Instalaciones extra con capacidad y descripcion

- **Fecha:** 2026-10-28
- **Branch:** `feature/koke/instalaciones-extra-capacidad/28-10-2026`
- **Contexto:** Task 5 para gestionar instalaciones extra con capacidad obligatoria (1-500) y descripcion opcional (max 100), sin tipo visible.

## Cambios realizados

- Migracion `add_capacidad_descripcion_to_instalaciones_extra_table`.
- Modelo y entidad `InstalacionExtra` ahora exponen `capacidad` (int) y `descripcion` (?string).
- Factory y seeder ajustados para poblar capacidad y descripcion coherentes.
- Casos de uso de creacion y actualizacion de proyecto validan capacidad y descripcion invariants.
- Repositorio de Superadmin persiste los nuevos campos.
- Controller serializa capacidad y descripcion en `instalaciones_extra`.
- Requests de creacion y actualizacion validan capacidad `min:1|max:500` y descripcion `max:100`.
- Nuevo test feature `SuperadminInstalacionesExtraTest` con creacion, actualizacion y casos invalidos.

## Decisiones de diseno

- `tipo` se mantiene en la base de datos como campo historico pero deja de exponerse en el enfoque del dependiente nuevo.
- `atributos` se mantiene para compatibilidad.
- La capacidad se deja por defecto en BD con valor 0 y se normaliza a 1 para registros previos.

## Verificacion

- `php artisan migrate --force` aplicado en PostgreSQL local.
- `php artisan test`: 15 tests aprobados, 42 aserciones.

## Pendientes

- Validacion manual del flujo desde el frontend.
- Ajuste futuro del modulo de reserva para consumir `capacidad` como maximo posible.
