# 2026-11-04 — Proyectos: asistente de creación por pasos

- **Branch:** `feature/koke/ajustes-creacion-infraestructura/2026-11-04`
- **Módulo:** Superadmin / Proyectos

## Contexto

La creación de proyectos se realizaba en un único formulario que enviaba toda la
información al final. Eso impedía asignar estacionamientos y bodegas a las
unidades durante la creación, porque la estructura todavía no existía en la base
de datos. La edición se mantiene sin cambios.

## Cambios realizados

- Migración `estado` en `proyectos` (`borrador` | `completo`, default `completo`).
- Endpoints de borrador bajo superadmin:
  - `POST /api/superadmin/proyectos/borrador`
  - `PATCH /api/superadmin/proyectos/{id}/borrador`
  - `DELETE /api/superadmin/proyectos/{id}/borrador`
- Casos de uso `IniciarProyectoBorradorUseCase`, `GuardarPasoProyectoUseCase` y
  `DescartarProyectoBorradorUseCase`.
- Servicios de dominio reutilizables para normalizar estructura e instalaciones.
- Los listados de proyectos (superadmin y mis-proyectos) filtran borradores.
- Se conserva la edición actual y el endpoint de alta completa.
- Tests de feature del flujo por pasos, descarte y visibilidad de borradores.

## Decisiones de diseño

- El proyecto se crea como borrador en el primer paso y se elimina al confirmar
  la salida del asistente.
- Un único endpoint de guardado por pasos recibe solo los campos del paso; el
  caso de uso aplica los presentes.
- Si cambia el tipo de condominio, la estructura previa se reinicia para evitar
  combinaciones inválidas.
- Los borradores nunca aparecen en los listados ni en la edición.

## Verificación

- `php artisan test` — 24 tests, 90 aserciones, todo en verde.
- `php artisan route:list --path=api/superadmin` — rutas registradas.

## Pendientes

- Limpieza programada de borradores huérfanos (por ejemplo, cierre abrupto del
  cliente) en una tarea posterior.

## Corrección posterior (2026-11-04)

- Se detectó que la migración `add_estado_to_proyectos_table` no estaba aplicada
  en la base local, lo que provocaba un 500 en el listado de proyectos.
- Se aplicó `php artisan migrate` en el entorno Docker; la migración quedó en
  estado `Ran`.
- No hubo cambios de código en esta corrección.
