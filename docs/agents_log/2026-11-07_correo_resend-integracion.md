# Log de Agentes — Torre Manager Backend

Bitácora de los cambios realizados por el agente de IA en este repositorio.

> **Privacidad:** este repositorio es público. En todos los logs queda
> estrictamente prohibido incluir datos sensibles: credenciales, tokens,
> API keys, correos o nombres reales, cadenas de conexión, rutas absolutas
> del entorno local o contenido de `.env`.

---

## 2026-11-07 — Módulo Correo: integración de Resend

**Branch:** `feature/koke/correo-resend/2026-11-07`

### Contexto

El proyecto necesita enviar correos automáticos (por ejemplo, credenciales
temporales de conserjes y notificaciones a residentes). Se optó por un
proveedor transaccional (Resend) en lugar de operar un servidor SMTP propio,
por entregabilidad y menor costo operativo. Aún no hay dominio verificado, por
lo que el remitente por defecto es el sandbox del proveedor y los envíos solo
pueden dirigirse al correo dueño de la cuenta.

### Cambios realizados

1. **Dependencia.** Se agregó `resend/resend-php` mediante Composer dentro del
   contenedor de la aplicación. `composer.json` y `composer.lock` quedaron
   versionados; `vendor/` permanece ignorado por Git.

2. **Configuración.** Se extendió el bloque `resend` de `config/services.php`
   con la clave `from` (variable `RESEND_FROM_ADDRESS`, por defecto el
   remitente sandbox). `.env.example` documenta `RESEND_API_KEY` y
   `RESEND_FROM_ADDRESS` sin valores sensibles. La variable de entorno local se
   estandarizó a `RESEND_API_KEY`.

3. **Capa de Dominio** — `src/Features/Correo/`
   - `Domain/Entities/Correo.php`: value object inmutable con destinatario,
     asunto y contenido HTML, sin dependencias de red ni del framework.
   - `Domain/Repositories/CorreoRepositoryInterface.php`: contrato
     `enviar(Correo $correo): void`.
   - `Domain/Exceptions/EnvioCorreoException.php`: excepción con constructores
     nombrados para key faltante y fallo del proveedor.

4. **Capa de Datos**
   - `Data/Repositories/CorreoResendRepository.php`: implementación que toma la
     API key desde `config('services.resend.key')`, arma el payload y delega el
     envío al SDK. Envuelve los errores del SDK en `EnvioCorreoException` y no
     registra credenciales ni datos personales. El método de despacho es
     sobreescribible para pruebas sin red.

5. **Inyección de dependencias.** Se agregó el binding
   `CorreoRepositoryInterface → CorreoResendRepository` en
   `app/Providers/AppServiceProvider.php`.

6. **Limpieza.** Se eliminó el archivo de ejemplo del proveedor que estaba en la
   raíz del monorepo, tras migrar su contenido a la implementación.

### Decisiones de diseño

- Se usó un wrapper propio con interfaz de dominio en lugar del mailer nativo
  de Laravel, para que los casos de uso no dependan del SDK ni de `Mail`.
- El envío es síncrono por ahora. La interfaz permite encolarlo más adelante
  sin cambiar a los consumidores.
- El remitente se resuelve por configuración; al verificar un dominio solo se
  actualiza la variable de entorno.

### Verificación

- `composer dump-autoload`: autoload actualizado.
- Tests unitarios: 3 pruebas aprobadas (payload esperado, key ausente y
  envoltura de errores del proveedor).
- `vendor/bin/pint` sobre los archivos modificados: sin cambios pendientes.

### Pendientes

- Verificar un dominio en el proveedor y actualizar `RESEND_FROM_ADDRESS` para
  habilitar envíos a destinatarios distintos del dueño de la cuenta.
- Integrar el repositorio en los casos de uso de conserjes y residentes cuando
  esos flujos se implementen.
- Evaluar el encolado de correos cuando aumente el volumen.

---

## 2026-11-07 — Fusión con la feature de dashboard admin

**Branch:** `feature/koke/dashboard-admin/2026-11-06`

### Contexto

La rama de correo se había creado desde `main`, por lo que no incluía el
dashboard de administrador y la ruta `api/dashboard/modulos` respondía 404.
Se integró la feature de correo dentro de la rama de dashboard.

### Cambios realizados

- Merge de `feature/koke/correo-resend/2026-11-07` sobre
  `feature/koke/dashboard-admin/2026-11-06`.
- Único conflicto: `app/Providers/AppServiceProvider.php`, porque ambas ramas
  agregaban imports y bindings en el mismo bloque. Se conservaron los bindings
  de `Dashboard` y de `Correo`.

### Verificación

- `composer dump-autoload` y `php artisan optimize:clear`: correctos.
- `php artisan migrate`: sin migraciones pendientes.
- `php artisan route:list`: la ruta `api/dashboard/modulos` está registrada.
- `php artisan test`: 45 pruebas aprobadas.
