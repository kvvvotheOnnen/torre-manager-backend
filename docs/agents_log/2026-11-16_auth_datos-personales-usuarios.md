# Datos personales en usuarios: RUT y teléfonos

- **Fecha:** 2026-11-16
- **Branch:** `feature/koke/rut-usuarios/2026-11-16`
- **Módulo:** Auth / Usuarios

## Contexto

La tabla `usuarios` no contaba con RUT, teléfono ni contacto de emergencia. Antes de
implementar la gestión de solicitudes de residentes (Task 11) se agregaron estos datos al
perfil de usuario. Todos los usuarios comparten la misma entidad y el rol define su
actuación, por lo que no se creó una tabla `residentes`.

## Cambios realizados

- Migración `2026_11_16_000001_add_datos_personales_to_usuarios_table.php`:
  - `rut` nullable y único.
  - `telefono`, `telefono_emergencia` y `nombre_emergencia` nullable.
- Dominio `Features\Auth`:
  - Value object `Rut` con normalización y validación de dígito verificador (módulo 11).
  - Excepción `RutInvalidoException`.
- Reglas de validación `RutChileno` y `TelefonoValido` preparadas para las altas futuras.
- `App\Models\User` y la entidad `Usuario` incluyen los nuevos campos.
- `POST /api/login` devuelve RUT y teléfonos; `GET /api/user` los expone al propio usuario.
- `UserFactory` genera RUT válidos y únicos; el seeder asigna datos demo válidos a los
  cinco usuarios.
- Tests unitarios de RUT y reglas, tests de feature para login y `/api/user`, y
  actualización del test del seeder.

## Decisiones de diseño

- RUT canónico almacenado como `12345678-9` (sin puntos, con guion, `K` mayúscula).
- Columnas nullable para no romper usuarios existentes; la obligatoriedad se aplicará en
  los flujos de alta de Task 11/12.
- El RUT y los teléfonos se guardan directamente en `usuarios`; no se crea tabla
  `residentes`.

## Verificación

- `php artisan migrate` aplicado en la base local.
- `php artisan migrate:fresh --seed` ejecutado para reflejar los datos demo.
- Suite backend: 103 pruebas, 396 aserciones, todas pasan.
- `vendor/bin/pint` sobre los archivos modificados: sin errores.
- Prueba manual: el login devuelve RUT, teléfono y contacto de emergencia.
- No fue necesario reconstruir ni reiniciar contenedores: no cambiaron Dockerfile ni
  dependencias; el código está montado por volumen.

## Pendientes

- Exigir RUT y teléfonos en las altas de Task 11/12 usando las reglas ya creadas.
- Mostrar los datos personales en pantallas de administrador/conserje cuando exista un
  perfil de usuario.
