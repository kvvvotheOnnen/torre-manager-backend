# Seeder de membresías por proyecto

- **Fecha:** 2026-11-16
- **Branch:** `newInfra/koke/15-11-2026`
- **Módulo:** Infraestructura / Acceso (propietarios y arrendatarios)

## Contexto

El bloqueo de propietarios/arrendatarios introduce `proyecto_usuario` como fuente de
verdad de pertenencia y rol por proyecto. La migración de backfill (`2026_11_15_000003`)
solo cubre filas existentes al momento de migrar. En un `migrate:fresh --seed`, el
backfill corre sobre tablas vacías y después el seeder creaba únicamente la membresía
del administrador, dejando a residente y conserje sin fila en `proyecto_usuario`. La
autorización seguía funcionando por el fallback legacy (`usuarios.proyecto_id`), pero se
estaba ejercitando un camino distinto al que exige la arquitectura.

## Cambios realizados

- `database/seeders/DatabaseSeeder.php`:
  - Se agregó el helper privado `asegurarMembresia(User, proyectoId, Rol)` basado en
    `firstOrCreate` sobre `proyecto_usuario` con `estado = activo`.
  - Se crea membresía explícita para administrador, residente y conserje demo.
  - El `sync` del administrador pasó a `syncWithoutDetaching` para no eliminar la
    membresía recién asegurada.

## Decisiones de diseño

- Se usa el modelo de infraestructura `ProyectoUsuario` directamente porque los seeders
  viven en la capa de datos/infraestructura; no se reutiliza el caso de uso de asignación
  de vínculos para no mezclar datos demo con reglas de negocio.
- `firstOrCreate` mantiene el seeder idempotente respecto de la membresía, sin depender
  de una base recién creada.

## Verificación

- `php -l` sobre el seeder: sin errores de sintaxis.
- `migrate:fresh --seed` contra una base SQLite temporal aislada: se generan las tres
  membresías (administrador, residente, conserje) y los vínculos de unidad esperados
  (propietario y ocupante en la unidad 801, propietario en la unidad 802).
- `php artisan test --filter=VinculosUnidadTest`: 5 pruebas, 26 aserciones, todas pasan.
- `vendor/bin/pint --test` sobre el seeder: sin cambios requeridos.

## Ampliación: promoción de rol y propietario demo

Se detectó que `MembresiaProyectoRepository::asegurar()` no actualizaba el rol de una
membresía existente. Asignar un vínculo de propietario a alguien que ya tenía membresía
`residente` dejaba el rol de proyecto en `residente`, afectando el dashboard.

### Cambios

- `src/Features/Acceso/Data/Repositories/MembresiaProyectoRepository.php`:
  `asegurar()` ahora reactiva la membresía si estaba inactiva y promueve
  `residente` → `propietario`. No degrada `administrador`, `conserje` ni `propietario`
  cuando se asigna un vínculo de ocupación.
- `database/seeders/DatabaseSeeder.php`:
  - Nuevo usuario demo `propietario` con membresía explícita.
  - Edificio-801 queda con propietario demo como dueño y residente demo como
    arrendatario (coexistencia). Edificio-802 queda a nombre del propietario demo.
- `.env.example`: claves `PROPIETARIO_EMAIL` y `PROPIETARIO_PASSWORD` para el seeder.
- `tests/Feature/VinculosUnidadTest.php`: dos pruebas nuevas (promoción de membresía y
  no degradación de roles superiores).

### Verificación adicional

- Suite completa del backend: 85 pruebas, 339 aserciones, todas en verde.
- `pint --test` sobre los archivos modificados: sin cambios requeridos.
- `migrate:fresh --seed` en PostgreSQL local: 5 usuarios, 4 membresías y 3 vínculos
  (propietario y arrendatario en la unidad 801, propietario en la unidad 802).
- Prueba E2E por API (35 comprobaciones): asignación de propietario y arrendatario,
  coexistencia, reasignación con cierre del propietario anterior, promoción de membresía,
  no degradación de roles superiores, permisos por proyecto, cierre con historial,
  resolución del ocupante actual en paquetería y datos de propietario/ocupante en el
  árbol de infraestructura.

## Pendientes

- La base local en PostgreSQL se recreó durante la validación; el estado queda con los
  datos demo del seeder.
- Evaluar si conviene un test de seeder que valide las membresías generadas.
