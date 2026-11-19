# 2026-11-19 — Enforcement de módulos a nivel API

## Contexto

- **Branch:** `feature/koke/refactor-clean-architecture/19-11-2026`
- Antes de este cambio, deshabilitar un módulo desde el superadmin solo ocultaba
  tarjetas y funciones; los endpoints seguían respondiendo (soft lock).
- Objetivo: todo endpoint de un módulo valida el estado del módulo para el
  proyecto activo, incluidos los módulos futuros.

## Cambios realizados

- Nuevo contrato `Features\Dashboard\Domain\Contracts\VerificadorModulo` y
  implementación `Features\Dashboard\Data\VerificadorModuloProyecto`:
  - módulos configurables: deben estar en `proyectos.modulos`;
  - módulos globales: habilitados salvo que figuren en
    `proyectos.modulos_deshabilitados`;
  - ID desconocido o proyecto inaccesible → denegado (fail-safe);
  - superadmin exento (se autoriza por capacidades).
- Nuevo `config/modulos.php` con el mapa capacidad → módulo.
- `EnsureCapacidad` (`puede:<capacidad>`) ahora exige también el estado del
  módulo asociado a la capacidad. Esto protege automáticamente los endpoints
  presentes y futuros.
- Nuevo middleware `modulo:<id>` (alias `modulo`) para rutas sin capacidad.
  Se aplicó a `/infraestructura/*`.
- Rutas legacy conservan su `role.proyecto` y suman `modulo:` explícito:
  - `admin/asistente` + `modulo:asistente_ia_admin`
  - `admin/solicitudes-residentes` + `modulo:solicitudes_residente`
- Respuesta de módulo deshabilitado: `403` con mensaje
  "El módulo no está habilitado para este proyecto.", más log de advertencia
  en infraestructura (sin PII).
- `AGENTS.md` actualizado en el maestro y copiado idéntico a ambos repos.

## Decisiones de diseño

- Superadmin exento de la verificación de estado de módulo (opera por
  capacidades y administra el estado).
- Automático vía `puede:` como mecanismo principal; `modulo:` explícito solo
  para rutas que no se autorizan por capacidad.
- `infraestructura` se mantiene siempre habilitada (el backend exige al menos
  un módulo configurable y es el único actual). El middleware queda listo para
  futuros módulos configurables.

## Verificación

- `docker compose exec -T app php vendor/bin/phpunit`: **172 tests OK**.
- Nuevos tests en `tests/Feature/ModulosEnforcementTest.php`:
  - paquetería, marketplace, servicios, asistente administrativo y solicitudes
    bloquean con `403` al deshabilitar su módulo;
  - paquetería habilitada responde `200`;
  - superadmin sigue operando con el módulo deshabilitado;
  - módulo desconocido se deniega.
- `vendor/bin/pint` sobre archivos modificados y nuevos.

## Pendientes

- Implementar los endpoints de `gastos_comunes`, `control_acceso`,
  `instalaciones_extra` y `alertas_noticias` con `puede:`; el mapa de
  `config/modulos.php` ya los cubre.
- Evaluar un módulo configurable nuevo para ejercitar `modulo:<id>` con
  usuarios no-superadmin.
- Documentar en el wizard/edición que `infraestructura` es siempre habilitada
  (cambio aplicado en frontend).
