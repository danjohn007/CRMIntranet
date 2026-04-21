-- Corrección de generación de enlaces de formularios (compatibilidad de datos legacy)
-- Ejecutar en la base de datos actual: tramitev_crmvisas

START TRANSACTION;

-- 1) Mantener compatibilidad con solicitudes históricas:
--    Si ya tienen form_id pero no form_link_id, enlazar al mismo formulario.
UPDATE applications
SET form_link_id = form_id
WHERE form_link_id IS NULL
  AND form_id IS NOT NULL;

-- 2) Asegurar token público para formularios existentes sin token.
UPDATE forms
SET public_token = LOWER(HEX(RANDOM_BYTES(32)))
WHERE public_token IS NULL
   OR public_token = '';

-- 3) Mantener funcionalidad actual sin exponer formularios no utilizados:
--    habilitar acceso público únicamente para formularios ya vinculados y enviados/completados.
UPDATE forms f
INNER JOIN applications a ON a.form_link_id = f.id
SET f.public_enabled = 1
WHERE f.is_published = 1
  AND (f.public_enabled IS NULL OR f.public_enabled = 0)
  AND a.form_link_status IN ('enviado', 'completado');

-- Mantener esta segunda actualización separada para compatibilidad con registros legacy
-- y mejor uso de índices (evita OR en la condición de JOIN).
UPDATE forms f
INNER JOIN applications a ON a.form_id = f.id
SET f.public_enabled = 1
WHERE f.is_published = 1
  AND (f.public_enabled IS NULL OR f.public_enabled = 0)
  AND a.form_link_status IN ('enviado', 'completado');

COMMIT;
