# Notificaciones del módulo Marketplace

- **Fecha:** 2026-11-25
- **Branch:** `feature/martin/control-acceso-notificaciones/2026-11-24`
- **Repositorio:** `torre-manager-backend`

## Contexto

Segunda fase del sistema de notificaciones in-app: avisar a los usuarios cuando
ocurren eventos relevantes del marketplace. Regla acordada: las acciones de
aprobación, rechazo y eliminación por moderación notifican **siempre al dueño**
de la publicación, sin importar si quien ejecuta es conserje o administrador.
Quien ejecuta la acción no recibe aviso.

## Cambios realizados

### Eventos de dominio

- `PublicacionEnviadaARevision` (nueva): publicación de un residente que queda
  pendiente de aprobación.
- `PublicacionAprobadaMarketplace` (nueva): solicitud aprobada.
- `PublicacionRechazadaMarketplace` (nueva): solicitud rechazada con motivo.
- `PublicacionEliminadaPorModeracion` (existente): se le agregó el título de la
  publicación para poder redactar el aviso sin consultar el modelo.

Los eventos se disparan después de la operación en
`CrearPublicacionMarketplaceUseCase` (solo cuando la publicación nace pendiente),
`AprobarSolicitudMarketplaceUseCase`, `RechazarSolicitudMarketplaceUseCase` y
`EliminarPublicacionMarketplaceUseCase`.

### Listeners

- `NotificarPublicacionEnRevision`: avisa a administradores y conserjes del
  proyecto.
- `NotificarPublicacionAprobada`: avisa al dueño.
- `NotificarPublicacionRechazada`: avisa al dueño indicando el motivo.
- `NotificarPublicacionModerada`: avisa al dueño indicando el motivo.

Todos usan el caso de uso `CrearNotificacionUseCase` del módulo compartido y
guardan el `publicacion_id` en `datos`.

### Acceso

- `MembresiaProyectoRepositoryInterface::usuariosIdsConRoles()` junto con su
  implementación, para resolver los destinatarios con rol administrador y
  conserje dentro del proyecto activo.

### Registro

- `AppServiceProvider::boot()` registra los cuatro listeners.

## Decisiones de diseño

- La notificación se crea en el listener (post-commit del evento), de modo que
  un fallo de notificación no revierte la operación de negocio.
- Solo se notifica la creación cuando la publicación queda pendiente; las
  publicaciones del staff no generan aviso de revisión.
- El ejecutor de la acción nunca recibe la notificación, aunque cumpla también
  el rol de destinatario.

## Verificación

- `MarketplaceNotificacionesTest`: 4 pruebas, 15 aserciones, en verde.
- Suite completa del backend: 206 pruebas, 843 aserciones, en verde.
- `vendor/bin/pint --dirty`: limpio.

## Pendientes

- Recordatorios por correo (Resend) cuando exista la infraestructura.
- Notificación de publicación vendida a usuarios con favoritos: descartada por
  ahora por ruido.
