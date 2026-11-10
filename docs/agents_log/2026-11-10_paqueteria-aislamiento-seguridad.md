# 2026-11-10 — Paquetería: aislamiento y seguridad (backend)

- **Módulo:** Paquetería
- **Rama:** `test-martin`

## Contexto

Se revisó el aislamiento entre proyectos y perfiles, la asignación de paquetes a
unidades/residentes y la gestión de fotografías.

## Cambios realizados

- El registro deriva proyecto y residente desde la unidad seleccionada y rechaza
  proyectos o residentes que no coincidan; se valida el acceso del operador.
- Listados, unidades, edición y retiros quedan limitados a proyectos autorizados;
  ámbitos vacíos no devuelven paquetes de otros proyectos.
- La API de unidades incluye `proyecto_id` para que el cliente use la unidad
  elegida como fuente del proyecto.
- La edición impide mover un paquete a una unidad de otro proyecto y deriva el
  residente desde la unidad.
- La escritura de fotos ocurre después de autorizar la operación; ante fallos se
  limpia el archivo nuevo. La foto previa se elimina después de guardar con éxito.
- Se añadieron pruebas de denegación entre proyectos, inconsistencias de unidad/
  residente, lista sin asignaciones y ámbito de unidades.

## Decisiones de diseño

- Las operaciones sobre recursos ajenos responden como no encontrados para no
  revelar su existencia.
- El superadministrador conserva acceso global; otros perfiles se limitan a sus
  asociaciones por proyecto y al campo legado de proyecto.
- No se reinició la base ni se alteraron datos locales.

## Verificación

- `php artisan test`: 66 pruebas aprobadas (273 aserciones).
- Pint en controlador, repositorio y pruebas de Paquetería: aprobado.

## Pendientes

- Prueba manual de registro, edición, foto y retiro con los perfiles de
  conserjería y residente.

## Publicación local de fotos

- Se detectó que faltaba el enlace `public/storage`, por lo que nginx no podía
  servir los archivos aunque estuvieran guardados.
- Se creó con `php artisan storage:link`; una foto existente respondió HTTP 200
  con tipo `image/webp`. nginx agrega CORS para que Flutter Web pueda cargar las
  imágenes desde su origen local. No se modificaron ni eliminaron fotos o datos.
- Se comprobó que el listado devuelve la URL de la foto y que una carga desde el
  cliente web responde HTTP 200 con CORS habilitado.
