# Ajustes Portal Cliente V3

## Objetivo
Mejorar la experiencia del cliente en `/mi-tramite/ver/{id}` para que el formulario no se vea amontonado, pueda capturarse por secciones y el avance pueda guardarse aunque el cliente no termine todo el mismo día.

## Cambios aplicados

1. **Formulario paginado para cliente**
   - El portal usa `forms.pages_json` cuando el formulario tiene paginación activa.
   - Si el formulario no tiene páginas definidas, divide los campos en secciones automáticas.
   - Se agregaron botones:
     - Guardar avance.
     - Guardar y continuar.
     - Guardar y volver.

2. **Guardado parcial de avance**
   - Cada página guarda únicamente los campos visibles de esa sección.
   - No borra información capturada en otras páginas.
   - El progreso se recalcula en `applications.progress_percentage`.
   - Se guarda la última página editada en `applications.client_form_current_page`.

3. **Diseño más limpio**
   - Se redujo la saturación visual del portal.
   - La sección de documentación ahora es más ordenada.
   - Los documentos se muestran en tarjetas compactas con estado de revisión.
   - El formulario ya no aparece como una lista enorme con scroll interno.

4. **Mensajes internos visibles**
   - El cliente ve el último mensaje desde su perfil principal.
   - Los mensajes enviados por el cliente se muestran también en seguimiento/customer journey.
   - Al abrir el trámite desde el panel interno, los mensajes del cliente se marcan como leídos para el equipo.

## Migración requerida

Ejecutar:

```sql
crm/database/migrations/add_client_form_pagination_progress.sql
```

Solo agrega:

```sql
applications.client_form_current_page
```

Si no la ejecutas, el guardado del formulario sigue funcionando, pero no se recordará con precisión la última página donde se quedó el cliente.
