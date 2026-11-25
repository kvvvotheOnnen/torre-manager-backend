# Registro de residente (task 12) — backend

- **Fecha:** 2026-11-25
- **Rama:** feature/koke/registro-residente/25-11-2026

## Contexto

La task 12 incorpora el autoregistro de residentes/propietarios desde el login.
El módulo de aprobación/rechazo de solicitudes (task 11) ya existía y se
reutiliza como destino final del registro.

## Cambios realizados

- Nuevos endpoints públicos (con throttle): `GET /api/registro/proyectos`,
  `GET /api/registro/proyectos/{id}/unidades` y `POST /api/registro/solicitud`.
- Caso de uso `RegistrarSolicitudResidenteUseCase` con validaciones de negocio:
  unidad perteneciente al proyecto, 1..6 personas, principal propietario o
  arrendatario, RUT válido (reutiliza el value object `Rut`), correo/RUT único
  del principal.
- El registro crea la cuenta (`usuarios`) y la solicitud en estado `pendiente`
  en una sola transacción. No crea membresía ni vínculo: eso sigue ocurriendo
  únicamente al aprobar (task 11).
- Entidades de dominio: `DatosRegistroResidente`, `DatosPersonaRegistro`,
  `MascotaRegistro`, `EspecieMascota`, `ProyectoRegistro`, `UnidadRegistro`.
- Migraciones nuevas: `solicitud_residente_mascotas` y `mascotas`.
- La aprobación de una solicitud ahora transfiere las mascotas registradas a la
  unidad (`transferirMascotasAUnidad`).
- La serialización de solicitud incluye las mascotas para que el panel del
  administrador pueda revisarlas.
- Notificación best-effort por correo (Resend) al registrarse una solicitud; un
  fallo de envío no bloquea el registro y no se registra información sensible.

## Decisiones de diseño

- Solo la persona principal obtiene cuenta de usuario; el resto queda como
  personas de la solicitud, consistente con la aprobación existente.
- El listado público de proyectos expone únicamente id y nombre, y solo
  proyectos en estado `completo`.
- Los endpoints públicos usan `throttle` para mitigar abuso.

## Verificación

- `php artisan test --filter=RegistroResidenteTest`: 7 pruebas en verde.
- Suite completa: 174 pruebas en verde. Los 23 errores restantes corresponden a
  directorios de `storage/framework/testing` propiedad de otro usuario en el
  entorno local (preexistentes, ajenos a este cambio).

## Pendientes

- La foto de mascota queda sin funcionalidad (la task lo indica explícitamente).
- El envío real de la notificación depende del plan de Resend configurado.
