# Bitácora — Fase 2: Creación de Proyectos (Tenants) y Asignación (Backend)

- **Fecha:** 2026-10-21
- **Branch:** `feature/koke/registropropiedad/21-10-2026`
- **Contexto:** Fase 2 del modelo SaaS multi-tenant.

## Cambios realizados

- **Migraciones**: `tipo_condominio` y `modulos` (JSON) en `proyectos`; tabla `instalaciones_extra` (`proyecto_id`, `nombre`, `tipo`, `atributos` JSON, unique `(proyecto_id, nombre)`); pivote `administrador_proyecto` (`proyecto_id`, `usuario_id`, unique compuesto).
- **Dominio** (`src/Features/Superadmin/Domain`): enums `TipoCondominio` (torres|casas|mixto) y `ModuloSistema` (infraestructura|paqueteria|quincho|gastos_comunes|control_acceso); entidades `InstalacionExtra` y `ProyectoDetalle`; `CrearProyectoYAsignarAdministradoresUseCase` (valida nombre, tipo, al menos un módulo, al menos un admin y normaliza instalaciones) y `ListarProyectosDelUsuarioUseCase`.
- **Datos**: `SuperadminRepository::crearProyectoConAdministradores` (transacción: crea proyecto, instalaciones y `sync` del pivote; valida que cada `admin_id` exista y tenga rol administrador) y `listarProyectosDelUsuario`.
- **Modelos**: `Proyecto` (casts `modulos`, relaciones `instalacionesExtra` y `administradores`), `InstalacionExtra` (`toEntity`) y `User::proyectos()` (belongsToMany).
- **API**: `POST /api/superadmin/proyectos` (body: datos, `tipo_condominio`, `modulos[]`, `instalaciones[]`, `admin_ids[]`) + `CrearProyectoRequest`. Se agregó `GET /api/mis-proyectos` (usado en la Fase 3).
- **Seeds/Factories**: `ProyectoFactory` con tipo y módulos; `InstalacionExtraFactory`; seeder anexa al "Administrador Demo" al proyecto demo (pivote) y crea una instalación de ejemplo.

## Decisiones de diseño

- La asociación administrador–proyecto se registra **desde el proyecto** (`sync` en la pivote), tal como dicta el caso de uso.
- `usuarios.proyecto_id` se conserva para residente/conserje; los administradores pasan a usar la pivote (el seeder mantiene ambos para el demo).

## Verificación

- curl: crear proyecto admin+instalaciones → 201; tipo inválido → 422; módulo inválido → 422; `admin_ids` inexistente → 404; admin (no superadmin) → 403; `mis-proyectos` admin → lista sus proyectos con módulos.
- `migrate:fresh --seed` + `php artisan test` (2 passed). `php -l` sin errores.

## Pendientes

- Fase 3: validación de pertenencia por pivote en el proyecto activo y enrutamiento multi-tenant en Flutter.
