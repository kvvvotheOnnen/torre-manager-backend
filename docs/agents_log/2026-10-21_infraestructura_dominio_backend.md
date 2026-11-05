# Bitácora — Tarea 1: Dominio de Infraestructura (Backend)

- **Fecha:** 2026-10-21
- **Branch:** `feature/koke/registropropiedad/21-10-2026`
- **Contexto:** HU-04, vertical de Infraestructura. Uso exclusivo del rol Administrador.

## Cambios realizados

Entidades puras y casos de uso del módulo, en `src/Features/Infraestructura/`:

- `Domain/Entities/`: `Proyecto`, `Agrupacion`, `UnidadPrincipal`, `UnidadAccesoria`, enums `TipoAgrupacion` (torre|bloque|manzana) y `TipoUnidadAccesoria` (estacionamiento|bodega), y read-models `ArbolInfraestructura`, `ArbolAgrupacion`, `ArbolUnidadPrincipal` para el árbol jerárquico.
- `Domain/Exceptions/`: `RecursoDuplicadoException`, `RecursoNoEncontradoException`.
- `Domain/Repositories/`: `InfraestructuraRepositoryInterface` (contrato completo del CRUD jerárquico).
- `Domain/UseCases/`: `CrearProyectoUseCase`, `RegistrarAgrupacionEnCascadaUseCase`, `CrearUnidadAccesoriaUseCase`, `AsignarUnidadAccesoriaUseCase`, `ObtenerArbolInfraestructuraUseCase`, `ObtenerProyectoDelUsuarioUseCase` y el servicio puro `GeneradorIdentificacionesUnidades`.

## Decisiones de diseño

- Jerarquía estricta Proyecto → Agrupación → Unidad Principal; la Unidad Accesoria cuelga del Proyecto con FK **opcional** a Unidad Principal (libre disposición / comunitaria).
- La generación en cascada es un servicio de dominio puro (`GeneradorIdentificacionesUnidades`) con rango de pisos, unidades por piso y prefijo opcional; tope de 500 unidades por operación.
- La capa de Dominio no conoce Eloquent, HTTP ni logs (Regla Cero). Los repositorios retornan entidades puras.
- `obtenerProyectoDelUsuario` soporta el scoping multi-tenant por `proyecto_id` en `usuarios` (se migra en Tarea 2).

## Verificación

- `php -l` sobre todos los archivos de `src/Features/Infraestructura`: sin errores.

## Pendientes

- Implementación de repositorios, migraciones, modelos Eloquent y seeders (Tarea 2).
