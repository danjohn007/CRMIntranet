# Archivos modificados/agregados - Portal Cliente V3

## Modificados

- `app/controllers/ClientPortalController.php`
  - Se agregó lectura de mensajes recientes en el portal principal.
  - Se agregó soporte para página actual del formulario.
  - Se ajustó `updateForm()` para guardar avances parciales por sección.
  - Se mantiene el avance previo sin borrar páginas no editadas.
  - Se mejoró el registro de mensajes del cliente en el seguimiento.

- `app/views/client-portal/show.php`
  - Se rediseñó la vista del trámite del cliente.
  - Se agregó paginación del formulario.
  - Se agregaron botones de guardar avance, continuar y volver.
  - Se eliminó la vista amontonada del formulario completo.
  - Se mejoró la sección de documentación.
  - Se mejoró la visualización de mensajes internos.

- `app/views/client-portal/index.php`
  - Se agregó aviso de mensajes nuevos.
  - Se muestra el último mensaje por trámite.

- `app/controllers/ApplicationController.php`
  - Se marcan como leídos los mensajes del cliente cuando el equipo abre el trámite.
  - Se mejora el registro de mensajes enviados al cliente en seguimiento.

- `app/controllers/CustomerJourneyController.php`
  - Se integran los mensajes internos del portal al seguimiento aunque no se haya generado touchpoint.

- `app/views/customer-journey/show.php`
  - Se agregaron íconos y colores para mensajes y actividad del portal cliente.

- `database/migrations/add_client_portal_module.sql`
  - Se agregó `applications.client_form_current_page` para nuevas instalaciones.

## Agregados

- `database/migrations/add_client_form_pagination_progress.sql`
  - Migración incremental para instalaciones donde ya se aplicó el módulo del cliente.

- `AJUSTES_PORTAL_CLIENTE_V3.md`
  - Resumen de cambios.

- `LISTADO_ARCHIVOS_MODIFICADOS_CLIENTE_V3.md`
  - Listado de archivos modificados y descripción.
