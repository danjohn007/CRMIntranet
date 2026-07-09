# Listado de archivos modificados - Landing v9

## Archivos modificados

1. `app/views/landing/index.php`
   - Se corrigieron las rutas del header y footer.
   - Los enlaces internos ahora usan anclas relativas: `#tramites`, `#portal`, `#proceso`, `#ubicacion`, `#preguntas`.
   - Se agregó `id="inicio"` al hero para que el logo regrese arriba sin salir de la página.
   - Se dejó de depender de `landing_public_url` para navegación interna.

2. `LANDING_PAGE_INSTALACION.md`
   - Se actualizó la explicación del problema de rutas.
   - Se agregó nota de que `landing_public_url` ya no afecta el menú interno.

3. `LISTADO_ARCHIVOS_MODIFICADOS_LANDING.md`
   - Este archivo.

## Base de datos

No requiere migración.

## Motivo del cambio

Al usar `landing_public_url = https://tramitevisaamericanaqueretaro.com`, los botones mandaban al dominio raíz con hash:

```text
https://tramitevisaamericanaqueretaro.com/#tramites
```

Pero si el dominio raíz aún muestra una página temporal, el usuario sale de la landing del CRM. Por eso ahora los enlaces internos se quedan en la página actual.
