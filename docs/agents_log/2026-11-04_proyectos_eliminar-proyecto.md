# Backend — Eliminación de proyectos

- **Fecha:** 2026-11-04
- **Branch:** `dev`
- **Contexto:** El módulo de proyectos del superadmin permitía crear y editar, pero no eliminar. Se agregó el borrado físico de un proyecto con toda su estructura asociada.

## Cambios realizados

- `routes/api.php`: nuevo endpoint `DELETE /api/superadmin/proyectos/{proyecto}` (solo superadmin, parámetro numérico).
- `SuperadminController`: inyección de `EliminarProyectoUseCase` y método `eliminarProyecto` (204 en éxito, 404 si no existe).
- `SuperadminRepositoryInterface`: contrato `eliminarProyecto(string $id): void`.
- Nuevo `EliminarProyectoUseCase` que delega en el repositorio.
- `SuperadminRepository`: `eliminarProyecto` busca el proyecto, lanza `RecursoNoEncontradoException` si no existe y ejecuta el borrado dentro de una transacción.
- Nuevo test Feature `SuperadminEliminarProyectoTest`.

## Decisiones de diseño

- Borrado físico (hard delete), sin soft deletes.
- La cascada se apoya en las foreign keys existentes: agrupaciones, unidades principales, unidades accesorias, instalaciones extra y pivote `administrador_proyecto` se eliminan con el proyecto.
- `usuarios.proyecto_id` usa `nullOnDelete`; las cuentas de administrador se conservan.
- No se registran datos personales ni credenciales en logs.

## Verificación

- `php artisan test`: 17 tests aprobados, 52 aserciones.
- `php artisan route:list --path=superadmin/proyectos`: confirma la ruta `DELETE`.
- El test nuevo valida el borrado en cascada, la eliminación del pivote, la conservación del usuario administrador y el 404 para un proyecto inexistente.

## Pendientes

- Verificación manual del flujo visual desde el cliente Flutter.
