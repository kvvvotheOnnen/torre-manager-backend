# Asistente IA - Restauración del reglamento demo y túnel Ollama

- **Fecha:** 2026-11-17
- **Branch:** `feature/koke/gestion-residentes/2026-11-16`
- **Repositorio:** `torre-manager-backend`

## Contexto

El asistente de residentes respondía 404 con el mensaje "La administración aún no
ha cargado un documento para este proyecto". El archivo PDF de ejemplo seguía en
`storage/` pero la fila correspondiente en `documentos` había desaparecido tras
un `migrate:fresh --seed`. Además, el contenedor `ollama-tunnel` estaba detenido
con código 127 porque instalaba `openssh-client` en cada arranque, por lo que el
backend no podía alcanzar el modelo local.

## Cambios realizados

### Reglamento demo persistente

- Nuevo `database/seeders/DocumentoReglamentoSeeder.php`:
  - usa como fuente el PDF ya versionado `docs/ejemplos/vanguardia_center_infraestructura.pdf`;
  - resuelve el proyecto demo por nombre (sin ids hardcodeados);
  - copia el archivo a una ruta determinista en el disco `local`;
  - extrae páginas/texto con `PdfTextExtractor` y calcula tokens con la misma
    fórmula que `SubirDocumentoUseCase`;
  - crea o actualiza la fila con `updateOrCreate` (idempotente).
- `DatabaseSeeder` ejecuta el nuevo seeder, de modo que
  `php artisan migrate:fresh --seed` siempre reconstruye el documento.
- Restauración puntual ejecutada sobre la base existente, sin borrar datos.
- Se eliminó el archivo huérfano de nombre aleatorio, conservando solo la ruta
  determinista.

### Túnel a Ollama

- Nuevo `docker/ollama-tunnel.Dockerfile` con `openssh-client` preinstalado.
- Nuevo entrypoint que copia `config` y `known_hosts` a `/root/.ssh` con los
  permisos correctos antes de abrir el túnel.
- Servicio `ollama-tunnel` agregado a `docker-compose.yml`:
  - `restart: unless-stopped`;
  - usa `SSH_AUTH_SOCK` del host y las claves del agente SSH;
  - reenvía `11434` dentro de la red `torre-net`.
- `docker/nginx/default.conf`: se elevó `fastcgi_read_timeout` a 200s para cubrir
  la latencia del modelo en la primera consulta.

## Decisiones de diseño

- El PDF fuente queda versionado en el repositorio; así sobrevive a limpiezas de
  base de datos y de `storage/`.
- El seeder es idempotente y no depende del id del proyecto demo.
- El túnel se administra con Docker Compose en lugar de un `docker run` manual
  para que se levante con el resto del stack.
- No se modificó ningún endpoint ni contrato de la API.

## Verificación

- `php artisan test`: 118 pruebas en verde.
- `pint --test` sobre los archivos nuevos/modificados: sin problemas.
- `GET /api/admin/asistente/documento`: devuelve el reglamento de 2 páginas.
- Consulta real de residente sobre el Quincho: respuesta correcta con cita
  `[Página 1]`, en ~36 s (incluye carga del modelo).
- `docker compose restart ollama-tunnel` y nueva consulta desde el contenedor
  `app`: `200 OK`.
- El seeder ejecutado dos veces deja una sola fila en `documentos`.

## Pendientes

- Si el socket del agente SSH cambia de ruta tras reiniciar la sesión de
  escritorio, volver a ejecutar `docker compose up -d ollama-tunnel`.
- Al desplegar en el VPS, mover el servicio `ollama` a la misma red del backend
  y usar `OLLAMA_BASE_URL=http://ollama:11434`.
