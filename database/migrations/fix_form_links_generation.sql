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
SET public_token = LOWER(SHA2(CONCAT(id, '-', UUID(), '-', RAND()), 256))
WHERE public_token IS NULL
   OR public_token = '';

-- 3) Mantener funcionalidad actual de formularios publicados:
--    formularios publicados también deben estar habilitados para acceso público.
UPDATE forms
SET public_enabled = 1
WHERE is_published = 1
  AND (public_enabled IS NULL OR public_enabled = 0);

COMMIT;
