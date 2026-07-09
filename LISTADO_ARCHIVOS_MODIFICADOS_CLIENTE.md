# Listado de archivos modificados/agregados

## Archivos modificados
- `config/config.php`
  - Se agregó la constante `ROLE_CLIENTE`.

- `app/controllers/AuthController.php`
  - Si el usuario inicia sesión como Cliente, se redirige a `/mi-tramite`.

- `app/controllers/BaseController.php`
  - Se agregaron métodos auxiliares para validar el rol Cliente.

- `app/controllers/Router.php`
  - Se agregaron rutas para el portal del cliente y acciones relacionadas.
  - Se agregaron rutas para vincular cliente, responder mensajes y marcar revisión desde solicitudes.

- `app/controllers/DashboardController.php`
  - El rol Cliente se redirige automáticamente a su portal.

- `app/controllers/UserController.php`
  - Se permite crear/editar usuarios con rol Cliente.
  - Se valida el nuevo rol en altas y actualizaciones.

- `app/controllers/ApplicationController.php`
  - Se impide que Cliente use el listado administrativo de solicitudes.
  - Se redirige al Cliente a su vista propia cuando intenta entrar a una solicitud.
  - Se agregaron funciones para vincular cliente a un trámite.
  - Se agregaron funciones para enviar mensajes al cliente.
  - Se agregó función para marcar como revisadas las actualizaciones del cliente.
  - Se permitió al Cliente visualizar documentos de su propio trámite.
  - Las indicaciones pueden marcarse como visibles para el Cliente y requerir respuesta.

- `app/views/layouts/main.php`
  - Se agregó menú especial para Cliente: **Mi trámite**.
  - Se ocultaron accesos administrativos al Cliente.

- `app/views/users/create.php`
  - Se agregó la opción de rol Cliente.

- `app/views/users/edit.php`
  - Se agregó la opción de rol Cliente.

- `app/views/users/index.php`
  - Se agregó estilo visual para el rol Cliente.

- `app/views/applications/show.php`
  - Se agregó bloque **Acceso del cliente al portal**.
  - Se agregó selector para vincular cliente.
  - Se agregó historial de mensajes con cliente.
  - Se muestran respuestas del cliente a observaciones.
  - El modal de indicaciones ahora permite marcar si será visible para cliente y si requiere respuesta.

## Archivos agregados
- `app/controllers/ClientPortalController.php`
  - Controlador completo del portal del cliente.

- `app/views/client-portal/index.php`
  - Pantalla principal del cliente con sus trámites y perfil.

- `app/views/client-portal/show.php`
  - Vista operativa del trámite para cliente.

- `database/migrations/add_client_portal_module.sql`
  - Migración para agregar rol Cliente, relación con solicitudes, mensajes, respuestas y campos de revisión.

- `CAMBIOS_PORTAL_CLIENTE.md`
  - Resumen funcional del avance.

- `LISTADO_ARCHIVOS_MODIFICADOS_CLIENTE.md`
  - Este listado.
