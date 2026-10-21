# Bitácora — Fix: contrato de `atributos` en instalaciones extra

- **Fecha:** 2026-10-21
- **Branch:** `feature/koke/registropropiedad/21-10-2026`
- **Contexto:** Al crear un proyecto con instalaciones extra, el cliente mostraba "Ocurrió un error inesperado" aunque el backend devolvía 201.

## Diagnóstico

- El endpoint creaba el proyecto correctamente (HTTP 201), pero la respuesta serializaba `atributos` de una instalación sin datos como **array vacío** de PHP (`[]`), porque `json_encode` de `[]` produce `[]`.
- El DTO del cliente esperaba un objeto (`Map`), por lo que `[] as Map<String, dynamic>` lanzaba un `TypeError` no mapeado → `UnexpectedFailure`.
- Confirmado en logs: sin errores de servidor; el `POST` del usuario respondió 201 y el `GET` de refresco nunca se ejecutó (la excepción cortó el flujo).

## Cambios realizados

- `SuperadminController::serializarProyectoDetalle`: `atributos` vacío se serializa como objeto JSON (`(object) []` → `{}`), manteniendo el contrato "objeto" para atributos.

## Verificación

- curl: crear proyecto con instalación sin atributos → `"atributos":{}`.
- `php artisan test` (2 passed); `php -l` sin errores.
- Nota: los proyectos creados durante el bug sí quedaron en base de datos.

## Pendientes

- Ninguno.
