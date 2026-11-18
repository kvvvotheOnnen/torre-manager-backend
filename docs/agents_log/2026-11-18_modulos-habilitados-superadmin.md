# 2026-11-18 — Módulos deshabilitables por proyecto desde la edición del superadmin

## Datos de la sesión

- **Fecha:** 2026-11-18
- **Branch:** `feature/koke/correcion-modulos-habilitados/18-11-2026`
- **Base:** `merge-test-marketplace-nuevodiseño`
- **Alcance:** edición de proyecto del superadmin y composición del dashboard

## Contexto

La edición de proyecto solo listaba los módulos configurables (en la práctica,
`infraestructura`). El superadmin necesita ver todos los módulos de la
plataforma, incluidos los globales, y poder deshabilitarlos para un proyecto
concreto sin afectar a los demás.

Los módulos globales se definen en `ModuloGeneral` y, por diseño, no se
persisten como habilitados en `proyectos.modulos`. Para conservar esa
separación se agregó una lista explícita de deshabilitados.

## Cambios realizados

### 1. Persistencia

- Migración `2026_11_18_000001_add_modulos_deshabilitados_to_proyectos_table`:
  columna `modulos_deshabilitados` (json, nullable).
- `Proyecto` incluye la columna en `fillable` y la castea a array.

### 2. Dominio de Superadmin

- `ProyectoDetalle` incorpora `modulosDeshabilitados` (default `[]`).
- `ActualizarProyectoUseCase` valida cada ID contra `ModuloGeneral::tryFrom` y
  persiste la lista única.
- `SuperadminRepositoryInterface` y `SuperadminRepository` reciben y guardan la
  lista; `toProyectoDetalle` filtra IDs desconocidos al leer.
- `ActualizarProyectoRequest` valida `modulos_deshabilitados.*` con `Rule::in`
  sobre los casos de `ModuloGeneral`.
- `SuperadminController` acepta el campo y lo serializa en todas las respuestas
  de proyecto (`modulos_deshabilitados`).

### 3. Dashboard

- `ProyectoConModulos` transporta `modulosDeshabilitados`.
- `DashboardRepository` lo hidrata desde la base de datos.
- `CatalogoModulosDashboard` devuelve cada global como
  `habilitado = !in_array(..., deshabilitados)`.
- `ListarFuncionesDashboardUseCase` compone los módulos habilitados con los
  globales no deshabilitados y omite las funciones cuyo `requiere_modulo` no
  esté habilitado.
- `config/funciones.php` declara `requiere_modulo` para
  `servicios_recomendados` y `marketplace`.

## Decisiones de diseño

1. **Columna separada** en lugar de mezclar globales en `proyectos.modulos`:
   mantiene la semántica documentada (configurables habilitados vs. globales
   deshabilitados) y no requiere backfill de proyectos existentes.
2. **Validación contra el catálogo backend**: el cliente no puede inventar IDs
   de módulos globales.
3. **Enforcement limitado a esta rama**: el estado deshabilitado se refleja en
   el dashboard y en las funciones, pero los endpoints operativos de cada
   módulo global conservan su autorización por rol/capacidad. Se deja como
   pendiente explícito agregar validación de módulo habilitado en esos
   endpoints.

## Verificación

- `php artisan test`: 165 pruebas, 695 aserciones, todas en verde.
- Nuevas pruebas: `SuperadminModulosProyectoTest` (persistencia, lectura,
  validación y reflejo en dashboard) y caso de módulo global deshabilitado en
  `FuncionesDashboardTest`.

## Pendientes

- Validar el módulo habilitado en los endpoints de cada módulo global
  (paquetería, marketplace, servicios, etc.) para que deshabilitar no sea solo
  una decisión de presentación.
- Sincronizar el `AGENTS.md` maestro con ambos repositorios (realizado).
