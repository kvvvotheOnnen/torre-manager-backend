# Capa de funciones, capacidades y ámbito (Etapa 1)

- **Fecha:** 2026-11-17
- **Branch:** `feature/martin/modulos-dashboard-residente-conserje/2026-11-17`
- **Alcance:** A (solo lo nuevo nace con el contrato; los módulos existentes no se modifican)

## Contexto

Los dashboards de residente y conserje armaban sus tarjetas con listas fijas por rol, y cada endpoint repetía su propia combinación de `role.proyecto` y validaciones manuales de propiedad. Eso hacía que cualquier cambio de roles, pertenencia o jerarquía obligara a tocar dashboards, guards y controladores a la vez.

Se acordó una capa de contratos estables: **función** (qué se muestra), **capacidad** (qué puede hacer un actor) y **ámbito** (sobre qué recurso puede actuar). Esta etapa entrega solo esa capa, sin ningún módulo de negocio.

## Cambios realizados

### Dominio (`src/Features/Dashboard`)

- `Domain/Entities/AmbitoFuncion.php`: enum `proyecto`, `unidad`, `propio`.
- `Domain/Entities/Funcion.php`: entidad pura con `id`, `titulo`, `descripcion`, `icono`, `capacidades`, `ambito`, `ruta` y `requiereModulo` opcional.
- `Domain/Entities/ReferenciaRecurso.php`: par `tipo` + `id` para resolver acceso a un recurso concreto.
- `Domain/Contracts/AutorizadorCapacidades.php`: `puede(usuario, proyecto, capacidad)`.
- `Domain/Contracts/ResolutorAmbito.php`: `puedeAccederA(usuario, proyecto, ReferenciaRecurso)`.
- `Domain/Repositories/CatalogoFuncionesRepositoryInterface.php`: catálogo de funciones declaradas.
- `Domain/UseCases/ListarFuncionesDashboardUseCase.php`: obtiene el proyecto activo, descarta funciones cuyo `requiereModulo` no esté habilitado y exige **todas** las capacidades declaradas.

### Datos (`src/Features/Dashboard/Data`)

- `CatalogoFuncionesEnConfig.php`: lee `config('funciones.funciones')`.
- `AutorizadorCapacidadesPorRol.php`: usa `AccesoProyectoService::rolEn` para conocer el rol en el proyecto activo y consulta `config('capacidades.roles')`; soporta el comodín `*`.
- `ResolutorAmbitoUnidad.php`: valida acceso al proyecto y luego el recurso; `proyecto` siempre concede, `unidad` concede a administrador y conserje, y para el resto exige vínculo `unidad_usuario.es_actual` con la unidad dentro del proyecto activo.

### Infraestructura HTTP

- `app/Http/Middleware/EnsureCapacidad.php` + alias `puede:` en `bootstrap/app.php`.
- `app/Http/Controllers/Api/FuncionesDashboardController.php`: responde `{"funciones": [...]}` y traduce `ProyectoNoDisponibleException` a 404.
- `routes/api.php`: `GET dashboard/funciones` dentro del grupo `auth:sanctum`, deliberadamente fuera de `role.proyecto`.
- `app/Providers/AppServiceProvider.php`: binds de las tres implementaciones.

### Configuración

- `config/funciones.php`: catálogo, arranca vacío con un ejemplo comentado.
- `config/capacidades.php`: mapa rol → capacidades. Hoy cinco roles; es el único archivo a editar cuando cambien los roles.

## Decisiones de diseño

1. **El endpoint de funciones no lleva `role.proyecto`.** Su propósito es no depender de listas de roles: el filtrado ocurre por capacidad dentro del caso de uso.
2. **El autorizador usa el servicio de Acceso, no el repositorio de membresías.** Así hereda el atajo de superadmin y el fallback legado sin duplicar reglas.
3. **Una función exige todas sus capacidades** (Y lógico). Se prefirió esto a un OR implícito para que el catálogo no conceda de más.
4. **`requiereModulo` solo aplica a módulos configurables.** Los módulos globales no se persisten por proyecto y no deben bloquear funciones.
5. **El frontend consume el catálogo, pero aún no lo monta.** Mostrar una sección vacía en los dashboards habría sido ruido; el montaje corresponde a la etapa siguiente, con la primera función real.

## Verificación

- `php artisan test` completo: **126 tests, 485 aserciones**, en verde (60,46 s).
- Tests nuevos: `FuncionesDashboardTest` (3), `ResolutorAmbitoTest` (4), `CapacidadesTest` (4) → 11 tests, 21 aserciones.
- `vendor/bin/pint --dirty`: 20 archivos sin cambios de estilo.
- Sin migraciones ni cambios de datos.

## Pendientes

- Montar la sección de funciones en los dashboards de residente y conserje (Etapa 2).
- Middleware `puede:` aún sin uso en rutas reales; se estrena con el primer módulo nuevo.
- Los tests del resolvedor cubren unidades; falta cubrir agrupaciones si el modelo incorpora "departamento".
- Los módulos previos al contrato (paquetería, mi-información, gestión de residentes) mantienen su autorización actual por decisión de alcance.
