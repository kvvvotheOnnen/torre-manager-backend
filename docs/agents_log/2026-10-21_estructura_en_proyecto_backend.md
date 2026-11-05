# Bitácora — Estructura integrada en la creación de proyecto (Backend)

- **Fecha:** 2026-10-21
- **Branch:** `feature/koke/registropropiedad/21-10-2026`
- **Contexto:** Infraestructura ya no es un módulo aparte; la estructura se crea junto al proyecto y se ajusta al tipo de condominio.

## Cambios realizados

- **`TipoCondominio`**: nuevo caso `Edificio` (torres | casas | edificio | mixto).
- **`TipoAgrupacion`** (Infraestructura): nuevo caso `Edificio` (edificio | torre | bloque | manzana).
- **`GeneradorIdentificacionesUnidades`**: nuevo método `generarSecuencial(prefijo, cantidad)` para casas (ej. "Casa 1".."Casa N").
- **`CrearProyectoYAsignarAdministradoresUseCase`**: recibe `agrupaciones[]` y genera la estructura en el dominio según el tipo:
  - `torres` → torres/bloques con pisos × unidades por piso.
  - `casas` → manzanas con cantidad de casas.
  - `edificio` → un edificio (sin torres) con pisos × unidades por piso.
  - `mixto` → torres y manzanas.
  Se valida que el tipo de agrupación sea coherente con el tipo de condominio (422 si no).
- **Repositorio**: `crearProyectoConAdministradores` crea en una sola transacción el proyecto, sus agrupaciones con unidades principales, las instalaciones y la asociación de administradores.
- **Request**: `agrupaciones` (nombre, tipo, prefijo, piso_desde/hasta, unidades_por_piso, cantidad) y `tipo_condominio` in `torres,casas,edificio,mixto`.

## Verificación

- curl: crear proyecto tipo torres/casas/edificio → 201 con estructura correcta (arbol verificado: "Torre A" → Torre A-101…Torre A-203).
- Incoherencias (torres + manzana, edificio sin pisos) → 422.
- `php artisan test` (2 passed), `migrate:fresh --seed` OK.

## Pendientes

- Edición de estructura posterior a la creación (fuera del alcance actual).
