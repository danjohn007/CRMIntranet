# Cambios: Portal operativo de Cliente

## Objetivo
Agregar el nuevo nivel de usuario **Cliente** para que el solicitante pueda consultar y actualizar su trámite desde la web. Esto deja una base más sencilla para conectar después el chatbot al mismo flujo.

## Funciones agregadas para Cliente
- Ver sus trámites asignados.
- Ver avance, estatus e historial del trámite.
- Editar información personal.
- Llenar o actualizar el formulario vinculado al trámite.
- Subir documentación desde la web.
- Responder observaciones visibles del equipo.
- Enviar mensajes internos al asesor/equipo.
- Marcar su trámite como listo para revisión.

## Reglas importantes
- El cliente no puede ver el dashboard administrativo.
- El cliente no puede cambiar el estatus oficial del trámite.
- El cliente solo ve trámites donde `applications.client_user_id` sea igual a su usuario.
- Cuando el cliente actualiza algo, el trámite se marca como `client_update_pending = 1` para que el equipo lo revise.

## Migración requerida
Ejecutar:

```sql
database/migrations/add_client_portal_module.sql
```

## Cómo probar
1. Ejecutar la migración.
2. Crear un usuario con rol `Cliente`.
3. Abrir una solicitud desde admin/asesor.
4. En el bloque **Acceso del cliente al portal**, vincular el usuario cliente.
5. Iniciar sesión como cliente.
6. Entrar a **Mi trámite**.
7. Probar actualizar formulario, subir documentos, responder observaciones y enviar mensajes.
