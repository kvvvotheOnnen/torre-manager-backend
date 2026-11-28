# 2026-11-28 - Merge control-acceso-notificaciones (rama de integracion)

- **Branch:** `ultimo-merge-funciones-martins-koke`
- **Base:** `feature/koke/registro-residente/25-11-2026`
- **Merge de:** `origin/feature/martin/control-acceso-notificaciones/2026-11-24` (1 commit: avisos in-app por modulo y aprobacion de publicaciones)

## Cambios realizados

- Merge de la rama de notificaciones sobre la rama de registro-residente.
- Divergencia: 4 commits locales vs 1 commit entrante; merge-base compartido reciente.
- Conflictos detectados: 1 (`app/Providers/AppServiceProvider.php`), ambos lados agregaban imports en el mismo punto.
- Resolucion: union de imports (RegistroResidenteRepository + interface del lado local; Listeners y Events de solicitudes del lado entrante).
- Archivos solapados auto-fusionados sin conflicto: `routes/api.php`, `AprobarSolicitudUseCase.php`.
- Migraciones: colision de timestamps `2026_11_25_000001/000002` entre ambos lados, pero con nombres distintos y tablas independientes; Laravel las ordena alfabeticamente sin problema.

## Decisiones de diseno

- La union de imports es segura: ambos lados declaran bindings/listeners distintos y complementarios.
- No se altero logica de ningun lado; el merge es aditivo.

## Verificacion

- `php -l` sobre el archivo resuelto: sin errores de sintaxis.
- `php artisan test`: 223/224 pasando. El unico fallo (`AsistenteTest::test_admin_puede_subir_un_pdf_a_su_proyecto`) es preexistente: falla igual en la rama base antes del merge.
- Los 23 fallos iniciales de `storage/framework/testing/disks` eran de permisos (carpetas creadas por root en Docker el 20-11); se corrigio con chmod desde el contenedor. No eran del merge.
- Todos los tests nuevos del merge pasan: Notificaciones, PaqueteriaNotificaciones, SolicitudesNotificaciones, MarketplaceSolicitudes, AccesoNotificaciones, ServiciosNotificaciones.

## Pendientes

- Push manual de la rama por parte del usuario.
- Fallo preexistente de AsistenteTest a revisar en su rama de origen.
