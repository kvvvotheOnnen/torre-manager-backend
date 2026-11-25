# Aprobación de publicaciones del marketplace (solicitudes)

- **Fecha:** 2026-11-25
- **Branch:** `feature/martin/control-acceso-notificaciones/2026-11-24`
- **Repositorio:** `torre-manager-backend`

## Contexto

Las publicaciones de los residentes aparecían de inmediato en el catálogo. Se
requiere un flujo de revisión: la publicación nace pendiente y conserje/admin
la aprueba o rechaza con motivo obligatorio antes de ser visible para todos.

Decisiones confirmadas por el usuario:

- Las publicaciones del administrador se publican directo (es staff).
- Una publicación rechazada queda visible para su dueño con el motivo, para
  que corrija y publique una nueva.

## Cambios realizados

### Base de datos

- Migración `2026_11_25_000001_add_rechazo_to_publicaciones_marketplace_table`:
  columnas `motivo_rechazo` (string nullable) y `rechazado_en` (timestamp
  nullable) en `publicaciones_marketplace`.
- Modelo `PublicacionMarketplace`: fillable y casts de los campos nuevos.

### Dominio

- `EstadoPublicacion`: casos nuevos `pendiente` ('Pendiente de aprobación') y
  `rechazada` ('Rechazada'), con `esPendiente()`.
- Entidad `PublicacionMarketplace`: campos `motivoRechazo` y `rechazadoEn`.
- `CrearPublicacionMarketplaceUseCase`: resuelve el rol con
  `AccesoProyectoService`; administrador y superadmin publican directo
  (`disponible`), el resto nace `pendiente`.
- Use cases nuevos: `ListarSolicitudesMarketplaceUseCase` (pendientes del
  proyecto + conteo), `AprobarSolicitudMarketplaceUseCase` (`pendiente` →
  `disponible`) y `RechazarSolicitudMarketplaceUseCase` (`pendiente` →
  `rechazada` con motivo obligatorio).
- Excepción `MotivoRechazoRequeridoException` ('Debes indicar el motivo del rechazo.').
- Mis publicaciones ahora incluyen disponibles, pendientes y rechazadas; el
  historial mantiene vendida/retirada/eliminada (`listarDeUsuario` variádico
  por estados).
- Repositorio: `listarPendientes` (la más antigua primero, cola de revisión) y
  `contarPendientes`; la vista `todas` del admin excluye eliminadas,
  pendientes y rechazadas (cada una se consulta en su espacio propio).

### API

- `GET /api/marketplace/solicitudes` → `{pendientes, solicitudes}` con
  capacidad `marketplace.aprobar`.
- `POST /api/marketplace/publicaciones/{id}/aprobar`.
- `POST /api/marketplace/publicaciones/{id}/rechazar` con
  `RechazarSolicitudRequest` (motivo obligatorio, min 5, max 500).
- Capacidad `marketplace.aprobar` agregada a administrador y conserje.
- Serialización: `motivo_rechazo` y `rechazado_en` en cada publicación.

### Datos de prueba

- `MarketplaceSeeder`: publicación demo pendiente para probar la vista de
  solicitudes sin ejecutar el flujo completo.

## Decisiones de diseño

- La publicación pendiente no es editable ni retirable por su dueño: queda
  congelada mientras se revisa; si se rechaza, el dueño corrige creando una
  publicación nueva (decisión del usuario).
- Aprobar o rechazar fuera del estado `pendiente` responde 409 (conflicto de
  estado); un residente sin capacidad recibe 403.
- El conteo de pendientes viaja en la misma respuesta de solicitudes para
  alimentar el badge del cliente con una sola llamada.
- No se disparan eventos de dominio en aprobación/rechazo: quedan anotados
  para cuando el módulo de notificaciones retorne al alcance.

## Verificación

- `php artisan test`: 192 pruebas, 794 aserciones, todas en verde (185 previas
  + 7 nuevas de solicitudes; un ajuste de expectativa de estado en
  `MarketplaceTest`).
- `vendor/bin/pint` sobre los archivos tocados: sin issues.
- Migración aplicada en la BD local (docker compose exec -T app php artisan migrate).

## Pendientes

- ~~Pantalla de solicitudes y estados visibles en el cliente Flutter.~~ (hecha,
  ver bitácora frontend).
- Evaluar evento + notificación al dueño cuando se aprueba/rechaza (cuando las
  notificaciones in-app vuelvan al alcance).
- Evaluar en el futuro reenviar una rechazada corregida en lugar de crear una
  publicación nueva.

## Ajuste posterior: acción `aprobar` en el catálogo de funciones

- `config/funciones.php`: la función `marketplace` ahora declara la acción
  `aprobar` => `marketplace.aprobar` (junto a `moderar`), de modo que el cliente
  resuelva la vista "Solicitudes" con `_puede('aprobar')` sin conocer roles.
- `FuncionesDashboardTest`: 5/5 en verde; el caso de acciones usa una función
  propia de paquetería, por lo que no fue necesario ajustar aserciones.
