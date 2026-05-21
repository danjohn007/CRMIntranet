-- ============================================================
-- Migración: folios para ingresos de asesor
-- Compatible con MySQL 5.7
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `advisor_income_records`
  ADD COLUMN `folio` varchar(50) DEFAULT NULL AFTER `id`;

UPDATE `advisor_income_records`
SET `folio` = CONCAT(
  'ING-',
  DATE_FORMAT(COALESCE(`income_datetime`, `created_at`), '%Y'),
  '-',
  LPAD(`id`, 6, '0')
)
WHERE `folio` IS NULL OR `folio` = '';

ALTER TABLE `advisor_income_records`
  MODIFY COLUMN `folio` varchar(50) NOT NULL,
  ADD UNIQUE KEY `uniq_advisor_income_records_folio` (`folio`);

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Migration add_folio_to_advisor_income_records completed!' AS status;
