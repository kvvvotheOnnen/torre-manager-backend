# 2026-11-19 — Clasificación de unidades (task 13)

## Contexto

- **Branch:** `feature/koke/clasificacion-unidades/19-11-2026`
- Task 13: el superadmin define clasificaciones de unidades con nombre libre
  dentro del proyecto; cada unidad queda en una sola clasificación y las no
  clasificadas pasan a "Estándar". La clasificación se gestiona en el mismo
  paso de Estructura del wizard.

## Cambios realizados

- Migración `2026_11_19_000001_create_clasificaciones_unidad_table`:
  - tabla `clasificaciones_unidad` (`proyecto_id`, `nombre`, unique por proyecto);
  - `unidades_principales.clasificacion_id` nullable con `nullOnDelete`.
- Modelos `ClasificacionUnidad` y relaciones `Proyecto::clasificaciones()`,
  `UnidadPrincipal::clasificacion()`.
- `GuardarPasoProyectoBorradorRequest`: valida `clasificaciones[*].nombre` y
  sus `unidades[*]` (`agrupacion`, `identificador`).
- `GuardarPasoProyectoUseCase`: normaliza clasificaciones y rechaza nombres
  vacíos/duplicados (case-insensitive) y unidades repetidas en más de una
  clasificación.
- `SuperadminRepository::guardarPasoProyecto`: recibe `clasificaciones`,
  sincroniza las categorías del proyecto dentro de la misma transacción y
  asigna `clasificacion_id` por `(agrupacion, identificador)`. Una unidad sin
  clasificación queda en NULL, equivalente a "Estándar".
- Guardar la estructura sin clasificaciones reinicia todas las unidades a
  "Estándar" y elimina las categorías previas (el paso es la fuente de verdad).
- `AGENTS.md` sin cambios: no se modifican reglas de arquitectura.

## Decisiones de diseño

- "Estándar" es implícita (NULL), no una fila renombrable, para sobrevivir al
  recreate de estructura que hace el borrador.
- La referencia a unidades usa `(nombre de agrupación, identificador)` porque
  las unidades se recrean en la misma transacción del paso.
- Se mantiene el alcance literal de la task: solo wizard de creación; la
  edición de proyecto no incorpora clasificaciones.

## Verificación

- `docker compose exec -T app php vendor/bin/phpunit`: **177 tests OK**.
- Nuevos tests `tests/Feature/ClasificacionUnidadesTest.php`: creación y
  asignación, nombres duplicados, unidad en dos clasificaciones, unidad
  inexistente, y reinicio a Estándar al re-guardar sin clasificaciones.
- `vendor/bin/pint` sobre los archivos nuevos/modificados.

## Pendientes

- Consumir la clasificación en el futuro módulo de gastos comunes.
- Si más adelante se edita estructura fuera del wizard, definir la política de
  remapeo de clasificaciones (hoy el paso las reemplaza).
