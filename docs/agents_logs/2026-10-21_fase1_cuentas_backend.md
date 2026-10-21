# Bitácora — Fase 1: Gestión de Cuentas (Superadmin crea Administradores)

- **Fecha:** 2026-10-21
- **Branch:** `feature/koke/registropropiedad/21-10-2026` (backend)
- **Contexto:** Fase 1 del modelo SaaS multi-tenant.

## Cambios realizados

- **Dominio** (`src/Features/Superadmin/Domain`): entidad `Administrador` (read-model de Usuario), `EmailDuplicadoException`, métodos `listarAdministradores()` y `crearAdministrador()` en `SuperadminRepositoryInterface`, y casos de uso `CrearAdministradorUseCase` (valida nombre, email y contraseña mínima 8) y `ListarAdministradoresUseCase`.
- **Datos**: `SuperadminRepository` implementa listado y creación (password hasheado, rol forzado a `administrador`, `proyecto_id` null; `QueryException` → `EmailDuplicadoException`).
- **API**: `POST /api/superadmin/administradores` y `GET /api/superadmin/administradores` bajo `role:superadmin`, más `CrearAdministradorRequest`.
- **Seeder**: `DatabaseSeeder` ya genera el superadmin por defecto (verificado). El Superadmin no es una entidad separada: es un `Usuario` con rol `superadmin` (agnóstico del tenant).

## Decisiones de diseño

- Se mantiene el modelo de rol como enum de dominio (no se introduce spatie). El superadmin es un caso del mismo enum.
- Las cuentas administrador se crean **sin proyecto** en la Fase 1; la asignación a proyectos ocurre desde el proyecto (Fase 2), cumpliendo la regla "la asociación se hace desde el proyecto".

## Verificación

- curl: crear admin → 201; email duplicado → 422 (validación) / 409 (carrera); password corta → 422; admin intenta crear → 403; listar → 200.
- `php artisan migrate:fresh --seed`: OK. `php artisan test`: 2 passed.

## Pendientes

- Fase 2 (proyectos, módulos, instalaciones, asignación de administradores).
