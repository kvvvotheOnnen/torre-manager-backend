# Bitácora — Tarea 3: Capa de Presentación RESTful (Backend)

- **Fecha:** 2026-10-21
- **Branch:** `feature/koke/registropropiedad/21-10-2026`
- **Contexto:** HU-04, módulo de uso exclusivo del rol Administrador.

## Cambios realizados

- **Middleware RBAC** `app/Http/Middleware/EnsureRole.php`: valida el rol (enum de dominio `Rol`) del usuario autenticado vía Sanctum; 401 si no hay usuario, 403 si el rol no está permitido. Alias `role` registrado en `bootstrap/app.php`.
- **Controlador delgado** `app/Http/Controllers/Api/InfraestructuraController.php`: inyecta los casos de uso de dominio; mapea excepciones de dominio a códigos HTTP (422 validación de negocio, 409 duplicado, 404 no encontrado). Serialización de entidades a JSON vive aquí (presentación).
- **FormRequests** en `app/Http/Requests/Infraestructura/`: `CrearProyectoRequest`, `RegistrarAgrupacionRequest`, `CrearUnidadAccesoriaRequest`, `AsignarUnidadAccesoriaRequest`.
- **Rutas** (`routes/api.php`): grupo `auth:sanctum` + `role:administrador` bajo `/api/infraestructura`: `GET proyecto`, `POST proyectos`, `POST agrupaciones` (cascada), `POST unidades-accesorias`, `PATCH unidades-accesorias/{id}/asignacion`, `GET arbol`, `POST vista-previa-identificaciones`.
- **Corrección de bug preexistente:** sin cabecera `Accept: application/json`, las rutas API no autenticadas intentaban redirigir a `route('login')` (inexistente → 500). Se configuró `redirectGuestsTo` para retornar `null` en rutas `api/*`, dejando 401 JSON.

## Decisiones de diseño

- El scoping multi-tenant se resuelve server-side: el proyecto del usuario (`proyecto_id`) determina la agrupación/unidades a crear; el cliente no envía `proyecto_id` en agrupaciones/accesorias.
- `vista-previa-identificaciones` expone el generador puro de dominio para que la UI muestre la previsualización antes de registrar.

## Verificación (curl, manual)

- Admin: login 200; `POST agrupaciones` crea Torre C con 6 unidades en cascada; `GET arbol` refleja la jerarquía; `POST proyectos` 201.
- Residente: `GET arbol`/`GET proyecto` → 403; `POST agrupaciones` → 403.
- Conserje: `GET arbol` → 403. Sin token → 401 (con y sin `Accept`).
- `php artisan test`: 2 passed. DB reseteada con `migrate:fresh --seed`.

## Pendientes

- Consumo desde Flutter (Tareas 4 y 5).
