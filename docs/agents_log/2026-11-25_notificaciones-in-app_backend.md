# Notificaciones in-app — Fase 0 (base compartida, backend)

- **Fecha:** 2026-11-25
- **Branch:** `feature/martin/control-acceso-notificaciones/2026-11-24`
- **Repositorio:** `torre-manager-backend`

## Contexto

Se retoma el módulo de notificaciones in-app que había sido revertido, ahora como
Fase 0 de un plan por módulos: una tabla y una bandeja compartidas para la
campanita, mientras cada módulo emite sus avisos con listeners propios en fases
posteriores. En esta fase solo se implementa el almacenamiento y la lectura; el
envío por correo (Resend) queda para cuando exista la infraestructura.

## Cambios realizados

### Base de datos

- Migración `2026_11_25_000002_create_notificaciones_table`:
  `usuario_id` (FK usuarios, cascadeOnDelete), `proyecto_id` nullable (FK
  proyectos, nullOnDelete), `tipo`, `titulo`, `mensaje`, `datos` JSON nullable,
  `leida` (default false), `leida_at` nullable, timestamps e índice
  (`usuario_id`, `leida`).
- Modelo `App\Models\Notificacion` con casts de `datos`, `leida` y `leida_at`.

### Dominio

- Feature `Features\Notificaciones`: entidad `Notificacion`, excepción
  `NotificacionNoEncontradaException` y contrato
  `NotificacionesRepositoryInterface`.
- Casos de uso: crear, listar, contar no leídas, marcar una leída y marcar todas
  leídas.

### Datos e infraestructura

- `NotificacionDto` (modelo → entidad) y `NotificacionesRepository` con listado
  por usuario ordenado por fecha descendente y desempate por id.
- Controlador delgado `NotificacionesController` con cuatro acciones.
- Rutas bajo `auth:sanctum` + `puede:notificaciones.ver`:
  `GET /api/notificaciones`, `GET /api/notificaciones/no-leidas`,
  `PATCH /api/notificaciones/leer-todas` y `PATCH /api/notificaciones/{id}/leer`.
- Binding del repositorio en `AppServiceProvider`.
- Capacidad `notificaciones.ver` para administrador, conserje, propietario y
  residente.

### Pruebas

- `NotificacionesTest` con seis pruebas: listado propio y ordenado, contador de
  no leídas, marcar una, marcar todas, notificación ajena (404) y ausencia de
  proyecto activo (404).

## Decisiones de diseño

- Una tabla compartida permite la bandeja y el badge sin obligar a un módulo
  central de emisión: cada feature seguirá notificando desde su listener.
- Las notificaciones ajenas no se distinguen de inexistentes (404), evitando
  filtrar información entre usuarios.
- Marcar una notificación ya leída es idempotente y no reescribe `leida_at`.
- Las ediciones no generarán avisos; solo creación, cambio de estado y
  eliminación con impacto para el usuario.

## Verificación

- `php artisan test --filter=NotificacionesTest`: 6 pruebas en verde.
- Suite completa: 198 pruebas, 813 aserciones, todas en verde.
- `vendor/bin/pint --dirty`: sin cambios pendientes.

## Pendientes

- Listeners por módulo: paquetería (registro, retiro y recordatorio cada 7 días),
  marketplace, servicios, solicitudes de residente y acceso.
- Recordatorio de paquetería: tarea programada diaria que compare la fecha de la
  última notificación del paquete.
- Envío por correo vía Resend cuando la infraestructura esté disponible.
- Push/FCM para el badge en tiempo real (hoy el cliente usa polling).
