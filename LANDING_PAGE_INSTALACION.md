# Landing page CRM VISA - instalación v9

## Corrección de rutas del menú

En esta versión los botones del header ya no usan `landing_public_url` para las secciones internas.

Antes podían mandar a:

```text
https://tramitevisaamericanaqueretaro.com/#tramites
```

Si el dominio principal todavía muestra otra página temporal, eso rompía la navegación.

Ahora usan anclas relativas:

```text
#tramites
#portal
#proceso
#ubicacion
#preguntas
```

Así funcionan correctamente tanto si entras desde:

```text
https://tramitevisaamericanaqueretaro.com/intranet/crm/public/
```

como si después dejas la landing en:

```text
https://tramitevisaamericanaqueretaro.com/
```

## Ruta recomendada en dominio principal

Para que la landing cargue en el dominio raíz, coloca este contenido en `public_html/.htaccess`:

```apache
RewriteEngine On

# Landing principal del dominio
RewriteRule ^$ intranet/crm/public/ [L]

# Ruta alternativa opcional
RewriteRule ^inicio/?$ intranet/crm/public/inicio [L]
```

## Archivos principales

- `app/views/landing/index.php`
- `public/css/landing.css`
- `public/js/landing.js`
- `public/img/landing/flags/*.svg`
- `app/controllers/LandingController.php`
- `app/controllers/Router.php`

## Base de datos

No requiere migración obligatoria.

Puedes dejar `landing_public_url` configurado o borrarlo; en esta versión ya no afecta los botones del menú interno.

## Configuración opcional para mapa y redes

```sql
INSERT INTO global_config (config_key, config_value, config_type) VALUES
('landing_chatbot_url','https://wa.me/5214420000000?text=Hola,%20quiero%20iniciar%20mi%20trámite%20de%20visa','text'),
('landing_whatsapp_url','https://wa.me/5214420000000','text'),
('landing_facebook_url','https://facebook.com/tu-pagina','text'),
('landing_instagram_url','https://instagram.com/tu-cuenta','text'),
('landing_tiktok_url','https://tiktok.com/@tu-cuenta','text'),
('landing_office_address','Centro Histórico, Santiago de Querétaro, Querétaro','text'),
('landing_maps_url','https://maps.google.com/?q=Centro%20Histórico%2C%20Santiago%20de%20Querétaro%2C%20Querétaro','text'),
('landing_map_embed_url','https://www.google.com/maps?q=Centro%20Histórico%2C%20Santiago%20de%20Querétaro%2C%20Querétaro&output=embed','text')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);
```
