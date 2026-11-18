# Módulo Marketplace

- **Fecha:** 2026-11-17
- **Branch:** `feature/martin/marketplace/2026-11-17`
- **Módulo:** Marketplace / Tienda de residentes

## Contexto

Los residentes necesitaban un espacio para publicar artículos nuevos, usados o
semi nuevos dentro del condominio, con búsqueda, categorías, favoritos,
historial y moderación. El administrador debía ver todas las publicaciones y el
conserje solo eliminarlas con un motivo obligatorio.

## Cambios realizados

### Base de datos

- Migración `2026_11_17_000002_create_publicaciones_marketplace_table.php`:
  proyecto, autor, título, descripción, precio CLP, categoría, condición,
  estado, imagen, fecha de venta, motivo y datos de eliminación.
- Migración `2026_11_17_000003_create_favoritos_marketplace_table.php`:
  favoritos por publicación y usuario, con unicidad por par.
- Índices por proyecto/estado, proyecto/categoría y usuario/estado.

### Dominio

- Entidades `PublicacionMarketplace`, `PublicacionVisible` y enums
  `CategoriaProducto`, `CondicionProducto`, `EstadoPublicacion`.
- Casos de uso para listar catálogo, detalle, publicaciones propias, historial,
  favoritos, crear, actualizar, retirar, marcar vendida, eliminar y alternar
  favorito.
- `VisibilidadMarketplaceService` calcula los flags de acción por publicación
  para que el cliente no evalúe roles.
- Evento `PublicacionEliminadaPorModeracion`, disparado después del commit, con
  dueño, moderador, rol y motivo. Queda preparado para conectar notificaciones.

### API

- `GET /api/marketplace/publicaciones` y detalle.
- `GET /api/marketplace/mis-publicaciones`, `/historial` y `/favoritos`.
- `POST /api/marketplace/publicaciones`, `PUT` y acciones `retirar`, `vender`.
- `DELETE /api/marketplace/publicaciones/{id}` con motivo obligatorio.
- `PUT /api/marketplace/publicaciones/{id}/favorito`.
- Capacidades `marketplace.ver`, `publicar`, `gestionar`, `moderar` y
  `favoritos`, con middleware `puede:`.
- El administrador puede consultar `?estado=todas`; el resto solo ve el catálogo
  disponible.

### Archivos y presentación

- Imágenes por el puerto `AlmacenArchivos`, carpeta `marketplace/{proyecto_id}`.
- Una sola imagen opcional por publicación.
- Se agregó la tabla al comando de limpieza de huérfanos.
- `Marketplace` se agregó como módulo global y función del dashboard.

### Datos demo y pruebas

- Factories de publicaciones y favoritos.
- Seeder con seis publicaciones demo, incluyendo vendida, retirada y eliminada.
- Pruebas Feature de catálogo, permisos, autoría, favoritos, ventana de 3 días,
  moderación, evento, administrador y archivos.

## Decisiones de diseño

- Nombre visible `Marketplace`; identificador interno `marketplace`.
- Precio entero en CLP y se permite `0` para artículos regalados.
- Descripción obligatoria, máximo 1000 caracteres.
- Una publicación vendida desaparece del catálogo y de las búsquedas, pero sigue
  visible en favoritos durante 3 días y en el historial del dueño.
- El retiro voluntario desaparece de inmediato del catálogo y de los favoritos.
- El conserje solo modera publicaciones disponibles y siempre con motivo.
- El administrador modera publicaciones ajenas con motivo y las propias sin
  motivo; las propias se retiran, no se moderan.
- No se implementó contacto real: el flag `contacto_disponible` queda en falso y
  el botón se muestra deshabilitado.
- No se agregó tabla de notificaciones; el evento y la auditoría de eliminación
  dejan el camino preparado.

## Verificación

- Suite backend: 154 pruebas, 634 aserciones, todas pasan.
- `vendor/bin/pint` sobre los archivos nuevos y modificados: sin cambios.
- `migrate:fresh --seed` en SQLite temporal: migraciones y seeders correctos.
- Prueba de permisos: residente, propietario, conserje, administrador y
  aislamiento entre proyectos.

## Ajuste posterior: migraciones y datos demo

- Se aplicaron en la base local las migraciones pendientes
  `2026_11_17_000002_create_publicaciones_marketplace_table` y
  `2026_11_17_000003_create_favoritos_marketplace_table`.
- Se ejecutó `MarketplaceSeeder`: 6 publicaciones demo, de las cuales 3 quedan
  disponibles en el catálogo.
- Verificación: `GET /api/marketplace/publicaciones` responde correctamente con
  el usuario residente.

## Ajuste posterior: pestaña de publicaciones eliminadas

- Se corrigió `MarketplaceRepository::listar()`: ahora el estado específico
  tiene prioridad sobre el filtro por defecto de disponibles. Antes
  `?estado=eliminada` devolvía disponibles porque `! $todas` se evaluaba primero.
- El administrador puede consultar `GET /api/marketplace/publicaciones?estado=eliminada`
  para obtener solo las publicaciones moderadas, con motivo y datos de
  eliminación. El resto de roles recibe 403.
- Se agregó la prueba `test_el_administrador_lista_solo_las_publicaciones_eliminadas`,
  que también valida `?estado=disponible`.
- Verificación: `MarketplaceTest` con 14 pruebas y 81 aserciones en verde.

## Ajuste posterior: publicaciones propias fuera de los listados generales

- `MarketplaceRepositoryInterface::listar()` recibe `?string $excluirUsuarioId`
  y el repositorio excluye las publicaciones de ese usuario.
- `ListarPublicacionesMarketplaceUseCase` pasa el usuario autenticado como
  `excluirUsuarioId`, por lo que el catálogo, Todas, Disponibles y Eliminadas
  dejan de mostrar las publicaciones propias.
- Las publicaciones propias siguen disponibles en `mis-publicaciones` e
  `historial`, que son los espacios personales de cada rol.
- Esto aplica también al administrador: sus publicaciones no se mezclan con la
  moderación general y se consultan en sus propios espacios.
- Se actualizó la prueba de creación para comprobar que la publicación recién
  creada no aparece en el catálogo y sí en Mis publicaciones.
- Se agregó la prueba del administrador con publicaciones ajenas y propias en
  distintos estados.
- Verificación: `MarketplaceTest` con 15 pruebas y 103 aserciones en verde;
  suite completa con 156 pruebas y 665 aserciones en verde.
- `vendor/bin/pint --test` sobre los archivos modificados: sin issues.

## Ajuste posterior: Todas sin publicaciones eliminadas

- La vista **Todas** del administrador ahora excluye las publicaciones con
  estado `eliminada`; estas solo se consultan en la pestaña **Eliminadas**.
- El repositorio mantiene el filtro explícito por estado cuando se solicita
  `?estado=eliminada`, y el modo `?estado=todas` devuelve disponibles, vendidas
  y retiradas, pero no eliminadas.
- Se amplió la prueba del administrador con una publicación eliminada ajena:
  **Todas** sigue devolviendo solo las no eliminadas.
- Verificación: `MarketplaceTest` con 15 pruebas y 103 aserciones en verde.

## Pendientes

- Conectar listeners de notificación al dueño y a los administradores cuando
  corresponda.
- Implementar el contacto real entre comprador y vendedor.
- Probar el selector de imagen en web.
- Evaluar paginación si el catálogo crece.
- El módulo queda sin commit, según lo solicitado.
