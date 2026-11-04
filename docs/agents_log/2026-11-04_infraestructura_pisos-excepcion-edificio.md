# Pisos con excepción en estructura de edificio (backend)

- **Fecha:** 2026-11-04
- **Branch:** `feature/koke/pisos-excepcion-edificio/2026-11-04`
- **Módulo:** Superadmin / Infraestructura

## Contexto

La creación de un proyecto tipo edificio asumía un rango continuo de pisos con
la misma cantidad de unidades por piso. Se requería poder excluir pisos
completos (por ejemplo, un piso con un quincho) y definir manualmente sus
unidades, distinguiendo entre unidades simples e instalaciones extra.

## Cambios realizados

- `GeneradorIdentificacionesUnidades::generar()` acepta `pisosExcluidos`.
- `NormalizadorEstructuraProyecto` procesa `agrupaciones.*.pisos_excepcion`:
  - Valida piso dentro del rango y sin duplicados.
  - Exige al menos una unidad manual por piso excepcionado.
  - Solo las unidades de tipo `simple` se agregan como unidades principales.
  - Las de tipo `instalacion` no generan unidad; el cliente las envía luego en
    el paso de instalaciones extra.
  - Valida identificadores no vacíos y únicos dentro de la agrupación.
- Reglas de validación en `GuardarPasoProyectoBorradorRequest` y
  `CrearProyectoRequest`.
- `NormalizadorInstalacionesProyecto` rechaza nombres de instalación repetidos.
- Tests nuevos:
  - `tests/Unit/GeneradorIdentificacionesUnidadesTest.php`
  - Casos de pisos con excepción, rango inválido e identificadores duplicados en
    `SuperadminProyectoBorradorTest`.

## Decisiones de diseño

- Las instalaciones extra de pisos con excepción no se persisten en el guardado
  de estructura; se envían en el paso 5 del asistente, reutilizando el flujo de
  instalaciones existente y evitando duplicar el origen de los datos.
- El backend acepta pisos con excepción en cualquier agrupación basada en pisos
  (edificio, torre, bloque); la UI los habilita solo para edificio.
- No se requiere migración de base de datos.

## Verificación

- `php artisan test` — 30 tests, 107 aserciones, todo en verde.

## Pendientes

- Ninguno para esta tarea.
