# Incidencia: tests apuntando a PostgreSQL de desarrollo

- **Fecha:** 2026-11-04
- **Branch:** `feature/koke/pisos-excepcion-edificio/2026-11-04`
- **Módulo:** Backend / Testing

## Contexto

Al ejecutar la suite de tests se vaciaron los datos locales de PostgreSQL
(usuarios y proyectos). El login dejó de funcionar porque la base quedó sin
usuarios.

## Causa

- `docker-compose.yml` define `DB_CONNECTION=pgsql` en el entorno del contenedor.
- `phpunit.xml` definía `DB_CONNECTION=sqlite` sin `force="true"`, por lo que la
  variable del contenedor tenía prioridad.
- Adicionalmente, aunque PHPUnit aplicara las variables, Laravel resolvía la
  configuración desde `.env`, por lo que `RefreshDatabase` terminó ejecutando
  `migrate:fresh` sobre la base de desarrollo.

## Cambios realizados

- `phpunit.xml`: variables de base de datos con `force="true"`.
- `tests/TestCase.php`: se sobreescribe `createApplication()` para forzar
  `database.default = sqlite` y `database.connections.sqlite.database =
  :memory:` antes de que `RefreshDatabase` se ejecute.
- Restauración de datos locales con `php artisan db:seed` (Condominio Demo y
  usuarios demo).

## Verificación

- `php artisan test` — 30 tests, 107 aserciones, todo en verde.
- Antes y después de ejecutar la suite: `users = 4`, `proyectos = 1`.
- Login de superadmin contra la API — HTTP 200 con token.
- Los cuatro usuarios demo conservan su contraseña de desarrollo.

## Decisiones de diseño

- Los tests siempre deben usar SQLite en memoria. La configuración se fuerza en
  código para que no dependa del entorno del contenedor ni de `.env`.
- No se versionan datos ni credenciales reales; el seeder usa las variables de
  entorno con valores por defecto de desarrollo.

## Pendientes

- Ninguno. Los datos personalizados que existieran antes del `migrate:fresh` no
  son recuperables; solo se restauró el set demo del seeder.
