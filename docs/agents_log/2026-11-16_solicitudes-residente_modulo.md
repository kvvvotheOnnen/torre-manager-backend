# Módulo de gestión de solicitudes de residentes

- **Fecha:** 2026-11-16
- **Branch:** `feature/koke/gestion-residentes/2026-11-16`
- **Módulo:** Solicitudes de residente / Acceso

## Contexto

Task 11 requiere que la asociación de un residente a una unidad sea aprobada o rechazada
por un administrador o conserje. El módulo `solicitudes_residente` ya existía en el
catálogo global del dashboard, pero no tenía persistencia, endpoints ni flujo de decisión.

La creación de cuentas de usuario no pertenece a esta task: se gestionará en otra rama.
Task 11 solo administra solicitudes y, al aprobar, anexa a un usuario existente a la
unidad y le otorga acceso al dashboard.

## Cambios realizados

### Base de datos
- `solicitudes_residente` (unidad, tipo, origen, estado, motivo, datos extra, decisores).
- `solicitud_residente_personas` (datos personales, vínculo, persona principal).
- Índices por proyecto/estado y unicidad de email/RUT dentro de cada solicitud.
- No se agrega estado a `usuarios` ni se bloquea el login.

### Dominio
- Entidades `SolicitudResidente`, `PersonaSolicitud`, `InstalacionUnidad`.
- Enums de estado, origen y tipo de solicitud (`propietario`, `arrendatario`, `residente`).
- Casos de uso: listar pendientes, obtener detalle, aprobar, rechazar, historial y
  consultar las solicitudes propias.
- La aprobación exige que la persona principal tenga `usuario_id` y solo crea:
  - `proyecto_usuario` con rol `propietario` o `residente`.
  - `unidad_usuario` con tipo `propietario`, `arrendatario` o `residente`.
- Los acompañantes quedan solo como datos de la solicitud: no reciben cuenta, membresía
  ni vínculo de unidad.
- Propietario y arrendatario cierran el vínculo vigente anterior del mismo tipo.
  `residente` permite múltiples vínculos actuales en la misma unidad.
- El rechazo solo cambia el estado de la solicitud; no modifica cuentas ni vínculos.

### API
- Endpoints administrativos para listar, detallar, aprobar y rechazar, accesibles a
  administrador, conserje y superadmin, con validación del proyecto activo.
- `GET /api/mis-solicitudes-residentes` permite al usuario autenticado consultar el
  estado de su propia solicitud.
- No existe endpoint para crear usuarios ni para crear solicitudes desde la app.
- El detalle incluye las bodegas y estacionamientos de la unidad solicitada.

### Presentación
- Controlador delgado, FormRequest de rechazo y bindings de repositorios.
- Serialización con personas e instalaciones.

### Datos demo y pruebas
- Factories de solicitud y personas.
- Seeder con 3 solicitudes pendientes y 2 históricas.
- Las cuentas demo de las personas principales se crean activas, sin membresías ni
  vínculos, para poder probar la aprobación.
- No se crean vínculos `familiar` en los flujos nuevos.

## Decisiones de diseño

- El login es independiente de la aprobación: un usuario puede autenticarse aunque su
  solicitud esté pendiente.
- El acceso al dashboard depende exclusivamente de `proyecto_usuario` y
  `unidad_usuario`, no de `usuarios.proyecto_id` ni de un estado en `usuarios`.
- No se crean usuarios desde Task 11. La creación de cuentas queda para otra task.
- El valor `familiar` se mantiene en `TipoVinculo` por compatibilidad, pero no se usa
  en Task 11.

## Verificación

- Suite backend: 116 pruebas, 467 aserciones, todas pasan.
- `vendor/bin/pint` sobre los archivos modificados sin errores.
- `migrate:fresh --seed` local: 8 usuarios, 3 solicitudes pendientes, 0 vínculos
  `familiar` de unidad.
- E2E:
  - Usuario pendiente inicia sesión, `mis-proyectos` vacío y ve su solicitud pendiente.
  - Aprobar como admin no cambia el conteo de usuarios y crea membresía/vínculo solo de
    la persona principal.
  - Tras aprobar, `mis-proyectos` y `mis-unidades` muestran el proyecto y la unidad.
  - Rechazar no altera la cuenta y deja el motivo visible en la solicitud propia.

## Pendientes

- Creación de cuentas y flujo de registro en otra rama/task.
- Wizard de registro de residente.
- Limpieza futura del valor `familiar` si se decide eliminarlo del modelo.
