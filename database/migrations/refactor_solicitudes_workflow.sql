-- ============================================================
-- Migración: Refactorización del flujo de solicitudes
-- Nuevos estados de color, hoja de información, tipo de documento
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Ampliar el ENUM de status en applications para incluir nuevos estados de color
ALTER TABLE `applications`
  MODIFY COLUMN `status` VARCHAR(60) NOT NULL DEFAULT 'Nuevo'
  COMMENT 'Estados: Nuevo(gris), Listo para solicitud(rojo), En espera de pago consular(amarillo), Cita programada(azul), En espera de resultado(morado), Trámite cerrado(verde)';

-- Actualizar solicitudes existentes en estado "Formulario recibido" al nuevo estado "Nuevo"
UPDATE `applications` SET `status` = 'Nuevo' WHERE `status` = 'Formulario recibido';

-- 2. Agregar columna doc_type a la tabla documents
ALTER TABLE `documents`
  ADD COLUMN IF NOT EXISTS `doc_type` VARCHAR(50) DEFAULT 'adicional'
  COMMENT 'pasaporte_vigente, visa_anterior, ficha_pago_consular, adicional'
  AFTER `name`;

-- 3. Agregar columnas de flujo de trabajo a applications
ALTER TABLE `applications`
  ADD COLUMN IF NOT EXISTS `form_link_id` INT(11) DEFAULT NULL COMMENT 'ID del formulario enviado al cliente' AFTER `data_json`,
  ADD COLUMN IF NOT EXISTS `form_link_status` VARCHAR(20) DEFAULT NULL COMMENT 'pendiente, enviado, completado' AFTER `form_link_id`,
  ADD COLUMN IF NOT EXISTS `form_link_sent_at` TIMESTAMP NULL DEFAULT NULL AFTER `form_link_status`,
  ADD COLUMN IF NOT EXISTS `official_application_done` TINYINT(1) DEFAULT 0 AFTER `form_link_sent_at`,
  ADD COLUMN IF NOT EXISTS `consular_fee_sent` TINYINT(1) DEFAULT 0 AFTER `official_application_done`,
  ADD COLUMN IF NOT EXISTS `consular_payment_confirmed` TINYINT(1) DEFAULT 0 AFTER `consular_fee_sent`,
  ADD COLUMN IF NOT EXISTS `appointment_date` DATETIME DEFAULT NULL AFTER `consular_payment_confirmed`,
  ADD COLUMN IF NOT EXISTS `appointment_confirmation_file` VARCHAR(500) DEFAULT NULL AFTER `appointment_date`,
  ADD COLUMN IF NOT EXISTS `client_attended` TINYINT(1) DEFAULT 0 AFTER `appointment_confirmation_file`,
  ADD COLUMN IF NOT EXISTS `client_attended_date` DATE DEFAULT NULL AFTER `client_attended`,
  ADD COLUMN IF NOT EXISTS `appointment_confirmed_day_before` TINYINT(1) DEFAULT 0 AFTER `client_attended_date`,
  ADD COLUMN IF NOT EXISTS `dhl_tracking` VARCHAR(100) DEFAULT NULL AFTER `appointment_confirmed_day_before`,
  ADD COLUMN IF NOT EXISTS `delivery_date` DATE DEFAULT NULL AFTER `dhl_tracking`;

-- 4. Crear tabla de hoja de información
CREATE TABLE IF NOT EXISTS `information_sheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `entry_date` date NOT NULL COMMENT 'Fecha de ingreso',
  `residence_place` varchar(200) DEFAULT NULL COMMENT 'Ciudad, Estado, País',
  `address` text COMMENT 'Domicilio del solicitante',
  `client_email` varchar(150) DEFAULT NULL COMMENT 'Email del solicitante',
  `embassy_email` varchar(150) DEFAULT NULL COMMENT 'Email de la embajada',
  `amount_paid` decimal(10,2) DEFAULT NULL COMMENT 'Monto que pagó el cliente (honorarios)',
  `observations` text COMMENT 'Observaciones',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_id` (`application_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `information_sheets_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `information_sheets_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
