# Notificaciones de paquetería (Fase 1)

- **Fecha:** 2026-11-25
- **Branch:** `feature/martin/control-acceso-notificaciones/2026-11-24`
- **Repositorio:** `torre-manager-backend`

## Contexto

Con la base in-app de notificaciones ya operativa (campanita, bandeja y contador),
se implementa el primer módulo emisor: Paquetería. Se notifica el registro y el
retiro de un paquete a **todos los usuarios con vínculo actual en la unidad**, y
se agrega un recordatorio periódico para paquetes sin retirar.

## Cambios realizados

### Eventos de dominio

- `Features\Paqueteria\Domain\Events\PaqueteRegistrado`: paquete, proyecto,
  unidad, descripción, remitente y residente legacy.
- `Features\Paqueteria\Domain\Events\PaqueteRetirado`: mismos datos más el
  nombre de quien retira.
- `RegistrarPaqueteUseCase` y `MarcarPaqueteRetiradoUseCase` disparan el evento
  correspondiente después de persistir, usando la entidad devuelta por el
  repositorio.

### Destinatarios

- `VinculoUnidadRepositoryInterface::usuariosActualesDeUnidad()` obtiene los
  usuarios con vínculo `es_actual` de la unidad.
- Los listeners usan esa lista y, si está vacía, caen al `residente_id` legado
  del paquete. La lista se deduplica.

### Listeners

- `NotificarPaqueteRegistrado`: título «Paquete recibido».
- `NotificarPaqueteRetirado`: título «Paquete retirado».
- Ambos crean una notificación por destinatario con tipo `paqueteria` y
  `datos.paquete_id`.
- Registrados en `AppServiceProvider::boot()` con `Event::listen`.

### Recordatorio de paquetes sin retirar

- Comando `paquetes:recordar`: busca paquetes pendientes con más de 7 días desde
  la recepción y notifica con tipo `paqueteria_recordatorio`, título
  «Paquete sin retirar».
- Evita duplicados: si ya existe un recordatorio del mismo paquete en los
  últimos 7 días, lo omite.
- Programado en `routes/console.php` todos los días a las 08:00 con
  `withoutOverlapping()`.

## Decisiones de diseño

- **Todos los vínculos actuales**, no solo el ocupante: propietario,
  arrendatario y residentes reciben el aviso.
- **Fallback legacy** al `residente_id` del paquete para datos antiguos sin
  vínculos en `unidad_usuario`.
- El recordatorio reutiliza la misma lista de destinatarios y el mismo criterio
  de deduplicación por paquete.
- Sin correo electrónico: el canal sigue siendo únicamente in-app.

## Verificación

- `php artisan test --filter=PaqueteriaNotificacionesTest`: 4 pruebas, 15
  aserciones, en verde.
- Suite completa: 202 pruebas, 828 aserciones, en verde.
- `vendor/bin/pint --dirty`: sin pendientes (47 archivos).

## Ajuste posterior: capacidad para propietario y carga en el cliente

- `notificaciones.ver` se agregó al rol `propietario` en `config/capacidades.php`;
  faltaba y provocaba 403 al abrir la bandeja con ese rol.
- En el cliente, la carga de notificaciones se dispara al resolverse el tenant
  (`TenantBloc` en estado `listo`), no en `AuthSuccess`: así el interceptor ya
  envía `X-Proyecto-Id` y se evita el 404 por carrera.
- Verificación: `CapacidadesTest` y suite completa 202/202; la bandeja del
  residente y del propietario responde 200 con datos reales.

## Pendientes

- El recordatorio solo se ejecuta si el scheduler está activo
  (`php artisan schedule:run` o el worker correspondiente).
- Validación manual en la app: registrar un paquete como conserje y comprobar
  la campanita de cada vinculado; retirarlo y repetir.
- Correo electrónico vía Resend cuando exista la infraestructura.
- Push/FCM para el contador en tiempo real (hoy se refresca con polling).
