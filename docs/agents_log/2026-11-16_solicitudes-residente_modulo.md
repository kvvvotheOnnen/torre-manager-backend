# Módulo de gestión de solicitudes de residentes

- **Fecha:** 2026-11-16
- **Branch:** `feature/koke/gestion-residentes/2026-11-16`
- **Módulo:** Solicitudes de residente / Acceso

## Contexto

Task 11 requiere que toda asociación de un nuevo residente a una unidad sea aprobada o
rechazada por un administrador o conserje. El módulo `solicitudes_residente` ya existía
en el catálogo global del dashboard, pero no tenía persistencia, endpoints ni flujo de
decisión.

## Cambios realizados

### Base de datos
- `usuarios.estado` (`pendiente`, `activo`, `rechazado`) con default `activo`.
- `solicitudes_residente` (unidad, tipo, origen, estado, motivo, datos extra, decisores).
- `solicitud_residente_personas` (datos personales, vínculo, persona principal).
- Índices por proyecto/estado y unicidad de email/RUT dentro de cada solicitud.

### Dominio
- Entidades `SolicitudResidente`, `PersonaSolicitud`, `InstalacionUnidad`.
- Enums de estado, origen y tipo de solicitud.
- Casos de uso: crear, listar pendientes, obtener detalle, aprobar, rechazar e historial.
- La creación registra cuentas en estado `pendiente`; la aprobación las activa y crea
  membresías y vínculos de unidad. Al aprobar un propietario/arrendatario se cierra el
  vínculo vigente anterior. Los acompañantes se registran como `familiar`.
- El historial devuelve las últimas 100 solicitudes decididas.

### API
- `POST /api/solicitudes-residentes` público con throttling para el futuro wizard de
  registro.
- Endpoints administrativos para listar, detallar, aprobar y rechazar, accesibles a
  administrador, conserje y superadmin, con validación del proyecto activo.
- Login bloqueado para cuentas pendientes o rechazadas con mensajes específicos.
- El detalle incluye las bodegas y estacionamientos de la unidad solicitada.

### Presentación
- Controlador delgado, FormRequests para creación y rechazo, bindings de repositorios.
- Serialización con personas e instalaciones.

### Datos demo y pruebas
- Factories de solicitud y personas; seeder con 3 solicitudes pendientes y 2 históricas.
- 12 pruebas Feature del módulo y actualización del test del seeder.

## Decisiones de diseño

- Se mantiene el modelo de usuarios único: no existe tabla `residentes`.
- La cuenta se crea en estado pendiente al recibir la solicitud y se activa al aprobar.
- La aprobación reutiliza los casos de uso de Acceso para membresías y vínculos, evitando
  duplicar reglas de ocupación.
- El rechazo solo cambia a `rechazado` las cuentas que seguían pendientes.

## Verificación

- Suite backend: 115 pruebas, 464 aserciones, todas pasan.
- `vendor/bin/pint` sobre los archivos modificados sin errores.
- `migrate:fresh --seed` local con 3 pendientes y 2 históricas.
- Prueba E2E: listar, aprobar como administrador, rechazar como conserje, verificar
  membresía y vínculo del aprobado, y confirmar el historial.
- Sin rebuild ni restart de Docker: no cambiaron Dockerfile ni dependencias.

## Pendientes

- Wizard de registro de residente (Task 12) que consuma el endpoint de creación.
- Flujo de contraseña/invitación para las cuentas activadas.
- Mascotas y datos adicionales de la solicitud.
