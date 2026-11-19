# 2026-11-19 — Refactor de Clean Architecture (backend)

## Contexto

- **Branch:** `feature/koke/refactor-clean-architecture/19-11-2026`
- Auditoría previa de ambos repos contra `AGENTS.md`. Este paso corrige las
  discrepancias de severidad alta y media del backend antes de seguir creando
  módulos.

## Cambios realizados

### Paquetería (fat controller → Clean Architecture)

- `PaqueteriaController` ya no consulta Eloquent ni resuelve vínculos. Delega en:
  - `ResolverDestinoPaqueteUseCase` (nuevo): valida proyecto/unidad/ocupante.
  - `ObtenerPaqueteUseCase` (nuevo): paquete accesible por proyecto.
  - `ListarContextosUnidadesUseCase` (nuevo, feature Acceso).
  - `AccesoProyectoService` para acceso y ámbito.
- Se eliminó el uso de `unidades_principales.residente_id` como fallback de
  ocupante; la residencia se resuelve desde `unidad_usuario` actual.
- Rutas de paquetería migradas de `role.proyecto` a `puede:paqueteria.ver` y
  `puede:paqueteria.gestionar`.
- Tests de paquetería actualizados para enviar `X-Proyecto-Id` y esperar 403
  al consultar un proyecto no autorizado.

### Acceso

- Nueva entidad `UnidadContexto` y métodos de contexto en
  `VinculoUnidadRepositoryInterface`/implementación.
- `AccesoProyectoService` y `VinculoUnidadService` ya no importan modelos
  Eloquent ni `DB`; toda consulta vive en repositorios de la capa Datos.
- `VinculoUnidadController` resuelve la unidad con `ObtenerContextoUnidadUseCase`
  y valida superadmin con `AccesoProyectoService`, sin consultas propias.

### Almacenamiento

- `AlmacenArchivos` expone `rutaFisica()` para procesos locales (extracción PDF).
- `AsistenteAdminController` y `DocumentoRepository` usan el puerto
  `AlmacenArchivos` en vez de `Storage`; el documento se guarda en el disco
  configurado (`config/archivos.php`, disco `public` por defecto).
- `Paquete::fotoUrl()` resuelve la URL vía el puerto.
- Se agregó `proyectos` a las carpetas administradas en `config/archivos.php`.

### Catálogo de módulos y funciones

- `ModuloSistema` se movió de `Features\Superadmin\Domain\Entities` a
  `Features\Dashboard\Domain\Entities`, eliminando la dependencia circular
  Dashboard ↔ Superadmin.
- `config/funciones.php` se amplió con las funciones de residente, conserje y
  administrador (mi información, asistente, paquetería, reservas, gastos,
  control de acceso, solicitudes, alertas, IA administrativa).
- `config/capacidades.php` incorpora `mi_informacion.ver`, `asistente.consultar`
  y `asistente.gestionar`.

### DTOs

- `Features\Paqueteria\Data\Models\PaqueteDto` es la única clase que conoce el
  modelo Eloquent; el repositorio construye la entidad desde el DTO.

## Verificación

- `php -l` sobre todos los archivos modificados.
- `vendor/bin/pint` sobre los archivos modificados.
- `docker compose exec -T app php vendor/bin/phpunit`: **165 tests OK**.

## Pendientes / deuda documentada

- DTOs en `Acceso` y `Marketplace` (la auditoría los agrupaba con Paquetería).
- `app/Models/UnidadPrincipal` conserva helpers `propietarioActual`/`ocupanteActual`
  y el `toEntity()` con reglas de vínculos; conviene delegarlos en Acceso.
- El comando `archivos:limpiar-huerfanos` sigue usando `Storage` directamente
  (capa de infraestructura, riesgo bajo).
- La migración de autorización a capacidades se aplicó a Paquetería; el resto de
  módulos legacy conserva `role.proyecto` hasta su migración explícita.
