# Membresías por proyecto y vínculos de unidad

- **Fecha:** 2026-11-15
- **Branch:** newInfra/koke/15-11-2026
- **Contexto:** separar propietario y arrendatario, permitir varias unidades por propietario y accesos multi-proyecto sin perder compatibilidad con el esquema anterior.

## Cambios realizados

- Nuevas tablas `proyecto_usuario` y `unidad_usuario`, con backfill desde `administrador_proyecto`, `usuarios.proyecto_id` y `unidades_principales.residente_id`.
- Nuevos modelos `ProyectoUsuario`, `ProyectoUsuarioPivot` y `UnidadUsuario`. El pivot hereda el rol global del usuario cuando se adjunta sin rol explícito.
- Nueva feature `src/Features/Acceso` con entidades, repositorios, servicios y casos de uso para membresías y vínculos.
- Endpoints:
  - `GET /api/mis-proyectos` para todos los roles autenticados, incluyendo el rol del proyecto.
  - `GET /api/mis-vinculos` y `GET /api/mis-unidades`.
  - `GET|POST /api/admin/unidades/{unidad}/vinculos` y `DELETE /api/admin/vinculos/{vinculo}`.
- Middleware `role.proyecto` para validar el rol contra el proyecto activo.
- Ajustes en dashboard, infraestructura, paquetería, residente, asistente y superadmin para leer membresías y vínculos.
- `mi-informacion` devuelve una lista de unidades ocupadas/poseídas y conserva los campos antiguos.
- El árbol de infraestructura incluye propietario y ocupante actual.
- Se mantienen `usuarios.proyecto_id`, `unidades_principales.residente_id` y `administrador_proyecto` durante la transición.

- Se agregó la sección 2.4 de `AGENTS.md` con las reglas de propiedad, ocupación y autorización multi-proyecto, sincronizada en los tres archivos.

## Verificación

- `php artisan migrate --force` aplicado sobre PostgreSQL local.
- `composer test`: 83 tests en verde (322 aserciones).
- `vendor/bin/pint` aplicado a los archivos modificados.
- Backfill comprobado: membresías de administrador, residente y conserje; vínculo de residente migrado.
- Diagrama de base de datos de la raíz actualizado: página 01 ampliada con las nuevas tablas y página 04 de detalle de membresías y vínculos.

## Pendientes

- Interfaz de administración para asignar y cerrar vínculos desde el frontend.
- Carga manual de propietarios históricos.
- En una fase posterior, eliminar columnas y tablas legacy cuando los datos estén validados.
