# Ajuste v4 - Formulario del cliente visible para admin/asesor

## Objetivo
Mostrar en el detalle interno de la solicitud las respuestas que el cliente va guardando desde el portal `/mi-tramite/ver/{id}`.

## Archivos modificados

1. `app/controllers/ApplicationController.php`
   - En la consulta de detalle ahora se obtiene `forms.pages_json` y `forms.pagination_enabled`.
   - Se decodifica `applications.data_json` para leer el avance capturado por el cliente.
   - Se decodifica `forms.fields_json` para conocer las etiquetas del formulario.
   - Se manda a la vista:
     - `$clientFormData`
     - `$clientFormFields`
     - `$clientFormPages`

2. `app/views/applications/show.php`
   - Se agregó la sección **Formulario capturado por el cliente**.
   - Muestra porcentaje de avance.
   - Muestra cuántos campos respondió el cliente.
   - Muestra la última actualización del cliente.
   - Agrupa las respuestas por secciones/páginas del formulario.
   - Los campos vacíos aparecen como **Pendiente**.
   - Los campos tipo archivo indican que deben revisarse en **Documentos**.

## Base de datos
No requiere una migración nueva si ya aplicaste las migraciones anteriores del portal cliente.

El ajuste usa los campos ya existentes:

- `applications.data_json`
- `applications.client_last_update_at`
- `applications.client_last_update_comment`
- `applications.progress_percentage`
- `forms.fields_json`
- `forms.pages_json`
- `forms.pagination_enabled`

## Cómo probar

1. Entrar como cliente.
2. Abrir `/mi-tramite/ver/{id}`.
3. Llenar algunos campos del formulario.
4. Dar clic en **Guardar avance** o **Guardar y continuar**.
5. Entrar como admin o asesor.
6. Abrir `/solicitudes/ver/{id}`.
7. Debe aparecer la sección **Formulario capturado por el cliente** debajo del bloque **Formulario para el cliente**.
