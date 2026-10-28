# Bitácora — Cierre de sesión: vertical Infraestructura y SaaS multi-tenant (Backend)

- **Fecha:** 2026-10-21
- **Branch:** `feature/koke/registropropiedad/21-10-2026`
- **Contexto:** índice y notas operativas de la sesión completa (backend).

## Hitos de la sesión (documentados en detalle)

1. `2026-10-21_infraestructura_dominio_backend.md` — Dominio de Infraestructura (entidades, generador de identificaciones, casos de uso).
2. `2026-10-21_infraestructura_datos_backend.md` — Migraciones jerárquicas, modelos Eloquent, repositorio, factories/seeders.
3. `2026-10-21_infraestructura_api_rbac.md` — API REST con `role:administrador`; incluye el fix del 401 en rutas `api/*` (redirección a `route('login')` inexistente).
4. `2026-10-21_superadmin_backend.md` — Rol de mayor privilegio, endpoint de proyectos y resolución de proyecto activo.
5. `2026-10-21_fase1_cuentas_backend.md` — Superadmin crea/listan cuentas administrador.
6. `2026-10-21_fase2_proyectos_backend.md` — Proyectos con tipo, módulos, instalaciones extra y asignación de administradores (pivote).
7. `2026-10-21_fase3_multitenant_backend.md` — `mis-proyectos` y validación de pertenencia por pivote con cabecera `X-Proyecto-Id`.
8. `2026-10-21_estructura_en_proyecto_backend.md` — Estructura del recinto integrada en la creación del proyecto y tipo `edificio`.
9. `2026-10-21_fix_atributos_instalaciones.md` — `atributos` vacío se serializa como objeto JSON (`{}`).

## Decisiones transversales

- Clean Architecture feature-first: Dominio puro (sin Eloquent/HTTP), Datos (Eloquent + repositorios), Presentación (controladores delgados). Los repositorios traducen excepciones de infraestructura a excepciones de dominio.
- RBAC sin spatie: campo `rol` en `usuarios` validado contra el enum de dominio; middleware `EnsureRole` (alias `role`).
- Multi-tenant: `usuarios.proyecto_id` para residente/conserje; pivote `administrador_proyecto` para administradores (asociación desde el proyecto).
- Nombres de tablas/variables en español; carpetas `Domain`/`Data` en inglés, según lo ya establecido.

## Notas operativas

- Entorno: `docker compose up -d`; consola en el contenedor `docker compose exec app php artisan ...`.
- Base de datos: PostgreSQL; sincronización local exclusivamente con `php artisan migrate:fresh --seed`.
- Seeder `DatabaseSeeder`: crea el superadmin y las cuentas demo; las credenciales se leen de variables de entorno (`SUPERADMIN_*`, `ADMIN_*`, `RESIDENTE_*`, `CONSERJE_*`) con valores por defecto de desarrollo (sin secretos versionados).
- Logs de runtime en `storage/logs/laravel.log`; nunca versionar logs.

## Verificación global

- `php artisan migrate:fresh --seed` aplica las 8 migraciones del proyecto y siembra datos.
- Matriz RBAC/tenancy validada por curl: superadmin 200/201, administrador 403 en rutas de plataforma, residente/conserje 403 en infraestructura, sin token 401.
- `php artisan test`: 2 passed.

## Pendientes

- Definir módulos y contexto de proyecto para residente y conserje.
- Edición de estructura del recinto posterior a la creación (no incluida en esta sesión).
