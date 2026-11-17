# 2026-11-17 — Directorio de servicios recomendados y capa de funciones

## Datos de la sesión

- **Fecha:** 2026-11-17
- **Branch:** `feature/martin/modulos-dashboard-residente-conserje/2026-11-17`
- **Base:** commits de gestión de residentes (no se modificó `dev`)
- **Alcance:** solo módulos nuevos; los módulos existentes conservan su autorización actual

## Contexto

Se necesita un módulo donde la administración publique trabajadores recomendados del edificio
(electricista, gasfiter, maestro, cerrajero, técnico informático, otros) con nombre, empresa,
teléfono, descripción e imagen opcional. Residente y conserje solo consultan; el administrador
agrega, edita y elimina.

Para que el módulo no dependa de cómo se estructuren los usuarios ni las unidades hoy, se
trabajó en el contrato de funciones, capacidades y ámbito y, sobre esa base, se construyó el
primero de los módulos nuevos.

## Cambios realizados

### 1. Acciones resueltas en el catálogo de funciones

- `Funcion` acepta un mapa `acciones` (`nombre => capacidad`).
- `FuncionVisible` separa la definición de la función de las acciones ya resueltas para el usuario.
- `ListarFuncionesDashboardUseCase` devuelve, por función, qué acciones puede ejecutar quien consulta.
- `GET /api/dashboard/funciones` responde `acciones: { gestionar: true|false }`.
- El cliente muestra u oculta botones sin conocer roles.

### 2. Capa de almacenamiento de archivos

- Puerto `Features\Archivos\Domain\Contracts\AlmacenArchivos` (`guardar`, `reemplazar`, `eliminar`, `url`, `existe`) y propiedades en las rutas.
- `AlmacenEnDisco` sobre el disco `public`; `AlmacenS3Compatible` queda como esqueleto documentado para S3/Garage, sin registrar.
- `config/archivos.php` con `driver`, `disco`, `max_kb` (5 MB) y carpetas permitidas.
- `php artisan archivos:limpiar-huerfanos` con `--dry-run`, programado a las 03:00; nunca borra archivos referenciados.
- Paquetería migrada al puerto sin cambios de API, rutas ni comportamiento.

### 3. Módulo de servicios (backend)

- Migración `trabajadores_servicio`: `proyecto_id`, `nombre`, `nombre_empresa`, `telefono`, `tipo_servicio`, `descripcion`, `foto_path`, `creado_por`, timestamps e índice por proyecto y tipo.
- Feature `Features\Servicios`: entidad, enum `TipoServicio` (6 casos), repositorio con filtro por tipo y búsqueda, y cuatro casos de uso (listar, crear, actualizar, eliminar).
- Endpoints:
  - `GET /api/servicios` con `puede:servicios.ver` y filtros `?tipo=` y `?buscar=`.
  - `POST /api/servicios`, `PUT /api/servicios/{id}` y `DELETE /api/servicios/{id}` con `puede:servicios.gestionar`.
- Capacidades nuevas: `servicios.ver` para todos los roles y `servicios.gestionar` solo para administrador.
- Función `servicios_recomendados` en `config/funciones.php` (ámbito proyecto, ruta `/servicios`, acción `gestionar`) y caso correspondiente en `ModuloGeneral`.
- `TrabajadoresServicioSeeder` con seis trabajadores de ejemplo sin imagen.

## Decisiones de diseño

1. **Acciones en el catálogo** en lugar de banderas por endpoint: un solo lugar declara qué se puede hacer y el cliente no interpreta roles.
2. **Tipo de servicio como enum fijo** para poder filtrar con etiquetas y evitar datos inconsistentes; existe `otro` como salida.
3. **Almacén como puerto** desde el principio: migrar a S3/Garage después no debe tocar los módulos de negocio.
4. **Sin cuota por proyecto**, límite de 5 MB por imagen y sin redimensionado: el contenedor no tiene GD ni Imagick, así que el límite se valida en la petición.
5. **Borrado de archivos solo por huérfanos**: el comando compara contra las rutas en base de datos antes de eliminar.
6. **El módulo sigue el contrato**: autoriza por capacidad y resuelve acceso por ámbito, sin listas de roles.

## Verificación

- `php artisan test --filter="FuncionesDashboardTest|ResolutorAmbitoTest|CapacidadesTest"`: 11 pruebas en verde.
- `php artisan test --filter="ArchivosTest|PaqueteriaTest"`: 22 pruebas en verde.
- `php artisan test --filter=ServiciosTest`: 8 pruebas en verde.
- `php artisan test` completo: 140 pruebas (541 aserciones) en verde.
- Se ajustaron las expectativas de `DashboardModulosTest` de 11 a 12 módulos generales por el nuevo caso del catálogo.
- `vendor/bin/pint --dirty`: sin pendientes.
- Migración aplicada y `DatabaseSeeder` ejecutado sin errores.

## Pendientes

- Pantalla del módulo en el cliente y montaje de la sección de funciones en los dashboards.
- Edición de estructura posterior a la creación del proyecto sigue fuera de alcance.
- RAG del asistente y despliegue de Ollama en producción.
- Migración a S3/Garage cuando se defina el destino de archivos.

## Ajuste posterior: descripción de la función

- Se alineó la descripción de servicios_recomendados en config/funciones.php con el texto del
  módulo equivalente del dashboard de administración:
  "Directorio de trabajadores y servicios recomendados para el edificio.".
- Motivo: que la tarjeta se vea idéntica en administrador, residente y conserje.
- Verificación: php -l config/funciones.php sin errores. No cambian capacidades, ámbito ni rutas.
