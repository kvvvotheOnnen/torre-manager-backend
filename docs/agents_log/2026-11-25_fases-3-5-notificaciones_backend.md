# Notificaciones de Servicios, Solicitudes de residente y Acceso

- **Fecha:** 2026-11-25
- **Branch:** `feature/martin/control-acceso-notificaciones/2026-11-24`
- **Repositorio:** `torre-manager-backend`

## Contexto

Fases 3, 4 y 5 del sistema de notificaciones in-app, autorizadas de forma
conjunta para cerrar el plan completo: directorio de servicios, solicitudes de
residente y vínculos de unidad.

## Cambios realizados

### Fase 3 — Servicios

- Evento `TrabajadorPublicado` disparado por `CrearTrabajadorUseCase` después de
  crear el trabajador.
- Listener `NotificarTrabajadorPublicado`: avisa a residentes, propietarios y
  conserjes del proyecto, omitiendo al usuario que publicó.

### Fase 4 — Solicitudes de residente

- Eventos `SolicitudAprobada` y `SolicitudRechazada`, disparados por sus casos
  de uso tras confirmar la transacción y dirigidos a la persona principal.
- Listeners `NotificarSolicitudAprobada` y `NotificarSolicitudRechazada` (este
  último incluye el motivo del rechazo).
- `AprobarSolicitudUseCase` invoca `AsignarVinculoUnidadUseCase` con
  `notificar: false` para evitar un doble aviso (vínculo + solicitud).

### Fase 5 — Acceso

- Eventos `VinculoAsignado` y `VinculoCerrado` disparados por
  `AsignarVinculoUnidadUseCase` y `CerrarVinculoUnidadUseCase`.
- Listeners `NotificarVinculoAsignado` y `NotificarVinculoCerrado`, que avisan
  al usuario afectado indicando el tipo de vínculo.
- `AsignarVinculoUnidadUseCase` incorpora el parámetro `notificar` (por defecto
  verdadero) para permitir flujos que ya notifican por otra vía.

## Decisiones de diseño

- Los avisos se emiten después de la transacción, de modo que un fallo de
  notificación no revierte la operación de negocio.
- El ejecutor de la acción no recibe notificación.
- La notificación de "nueva solicitud pendiente" queda pendiente: aún no existe
  un caso de uso de creación de solicitudes en este repositorio; se conectará
  cuando se implemente ese flujo.
- No existe flujo propio de "quitar membresía": la asignación de vínculo asegura
  la membresía y su aviso ya cubre el alta.

## Verificación

- `ServiciosNotificacionesTest`: 1 prueba en verde.
- `SolicitudesNotificacionesTest`: 2 pruebas en verde.
- `AccesoNotificacionesTest`: 2 pruebas en verde.
- Suite completa del backend: 211 pruebas, 868 aserciones, en verde.
- `vendor/bin/pint --dirty`: limpio.

## Pendientes

- Notificación de nueva solicitud pendiente cuando exista el flujo de creación.
- Canal de correo (Resend) cuando la infraestructura esté disponible.
