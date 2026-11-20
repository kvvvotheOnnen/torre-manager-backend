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

## Corrección posterior: nomenclatura de unidades del edificio

- Las unidades de una agrupación tipo `edificio` ya no se identifican como
  `Edificio-103`; usan `Unidad-103`. Torres y bloques conservan su nombre y un
  prefijo personalizado sigue teniendo prioridad.
- `NormalizadorEstructuraProyecto` calcula la base de identificación según el
  tipo y la propaga a las unidades simples de pisos con excepción.
  `RegistrarAgrupacionEnCascadaUseCase` aplica el mismo default.
- Seeders/demo actualizados de `Edificio-80X` a `Unidad-80X`.
- Tests nuevos en `tests/Unit/NormalizadorEstructuraProyectoTest.php` y
  expectativas actualizadas en los tests del borrador.
- Verificación: **181 tests OK**; `migrate:fresh --seed` ejecutado.

## Corrección: pisos con excepción vacíos

- Un piso con excepción puede quedar vacío: `unidades` ausente o vacío se
  interpreta como piso sin unidades ni instalaciones. Se elimina el rechazo
  "El piso X debe tener al menos una unidad"; el piso se excluye igual de la
  generación automática y salta su numeración.
- `sin_unidades: true` sigue aceptándose con el mismo efecto (compatibilidad).
- Tests: caso unitario del normalizador y caso feature del borrador con
  `unidades: []`; se retira el test que exigía el mínimo.
- Verificación: **182 tests OK**.

## Corrección: asignación de accesorias desde el wizard

- `EnsureModulo` ahora exime al superadmin global igual que
  `VerificadorModuloProyecto`: no le exige `X-Proyecto-Id` para operar en
  módulos transversales (infraestructura). Antes, el PATCH de asignación de
  estacionamientos/bodegas del wizard respondía 404 "Proyecto no encontrado."
  porque el wizard no tiene proyecto activo en el store del tenant.
- Endpoints no-superadmin conservan la exigencia de proyecto y la verificación
  fail-safe del módulo.
- Test de regresión: superadmin asigna una accesoria sin header ni
  `proyecto_id` (flujo real del wizard).
- Verificación: **183 tests OK**.
