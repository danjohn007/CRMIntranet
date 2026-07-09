-- Migración corregida: Portal operativo para usuario Cliente
-- Corrección: evita choque de collations en INFORMATION_SCHEMA usando comparación BINARY
-- Compatible con MySQL 5.7.
-- Ejecutar después de respaldar la base de datos.

DELIMITER $$

DROP PROCEDURE IF EXISTS add_column_if_missing$$
CREATE PROCEDURE add_column_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_column_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE BINARY TABLE_SCHEMA = BINARY DATABASE()
          AND BINARY TABLE_NAME = BINARY p_table_name
          AND BINARY COLUMN_NAME = BINARY p_column_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD COLUMN ', p_column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS add_index_if_missing$$
CREATE PROCEDURE add_index_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_index_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE BINARY TABLE_SCHEMA = BINARY DATABASE()
          AND BINARY TABLE_NAME = BINARY p_table_name
          AND BINARY INDEX_NAME = BINARY p_index_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD ', p_index_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS add_fk_if_missing$$
CREATE PROCEDURE add_fk_if_missing(
    IN p_fk_name VARCHAR(64),
    IN p_sql TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE BINARY TABLE_SCHEMA = BINARY DATABASE()
          AND BINARY CONSTRAINT_NAME = BINARY p_fk_name
          AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ) THEN
        SET @sql = p_sql;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- 1) Nuevo rol Cliente
ALTER TABLE `users`
MODIFY COLUMN `role` ENUM('Administrador','Gerente','Asesor','Cliente') COLLATE utf8mb4_unicode_ci NOT NULL;

-- 2) Relación del trámite con el usuario cliente y banderas de revisión
CALL add_column_if_missing('applications', 'client_user_id', '`client_user_id` INT(11) NULL DEFAULT NULL COMMENT ''Usuario cliente vinculado al trámite'' AFTER `client_name`');
CALL add_column_if_missing('applications', 'client_update_pending', '`client_update_pending` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''El cliente envió información pendiente de revisión'' AFTER `client_user_id`');
CALL add_column_if_missing('applications', 'client_last_update_at', '`client_last_update_at` DATETIME NULL DEFAULT NULL COMMENT ''Última actualización enviada por cliente'' AFTER `client_update_pending`');
CALL add_column_if_missing('applications', 'client_last_update_comment', '`client_last_update_comment` TEXT COLLATE utf8mb4_unicode_ci NULL COMMENT ''Comentario de la última actualización del cliente'' AFTER `client_last_update_at`');
CALL add_column_if_missing('applications', 'client_form_current_page', '`client_form_current_page` INT(11) NOT NULL DEFAULT 1 COMMENT ''Última página editada por el cliente'' AFTER `client_last_update_comment`');

CALL add_index_if_missing('applications', 'idx_applications_client_user_id', 'INDEX `idx_applications_client_user_id` (`client_user_id`)');
CALL add_fk_if_missing('fk_applications_client_user', 'ALTER TABLE `applications` ADD CONSTRAINT `fk_applications_client_user` FOREIGN KEY (`client_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE');

-- 3) Revisión de documentos subidos por cliente
CALL add_column_if_missing('documents', 'uploaded_source', '`uploaded_source` ENUM(''equipo'',''cliente'') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''equipo'' COMMENT ''Origen de carga del documento'' AFTER `uploaded_by`');
CALL add_column_if_missing('documents', 'review_status', '`review_status` ENUM(''pendiente'',''en_revision'',''aceptado'',''rechazado'') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''en_revision'' COMMENT ''Estatus de revisión del documento'' AFTER `uploaded_source`');
CALL add_column_if_missing('documents', 'review_comment', '`review_comment` TEXT COLLATE utf8mb4_unicode_ci NULL COMMENT ''Comentario de revisión del documento'' AFTER `review_status`');
CALL add_column_if_missing('documents', 'reviewed_by', '`reviewed_by` INT(11) NULL DEFAULT NULL COMMENT ''Usuario que revisó el documento'' AFTER `review_comment`');
CALL add_column_if_missing('documents', 'reviewed_at', '`reviewed_at` DATETIME NULL DEFAULT NULL COMMENT ''Fecha de revisión del documento'' AFTER `reviewed_by`');

CALL add_index_if_missing('documents', 'idx_documents_review_status', 'INDEX `idx_documents_review_status` (`review_status`)');
CALL add_index_if_missing('documents', 'idx_documents_uploaded_source', 'INDEX `idx_documents_uploaded_source` (`uploaded_source`)');

-- 4) Observaciones visibles para cliente y con respuesta requerida
CALL add_column_if_missing('application_notes', 'visible_to_client', '`visible_to_client` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''Mostrar esta observación en el portal del cliente'' AFTER `is_important`');
CALL add_column_if_missing('application_notes', 'requires_client_response', '`requires_client_response` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''El cliente debe responder esta observación'' AFTER `visible_to_client`');

-- 5) Respuestas del cliente a observaciones
CREATE TABLE IF NOT EXISTS `application_note_responses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `application_id` INT(11) NOT NULL,
  `note_id` INT(11) NOT NULL,
  `client_user_id` INT(11) NOT NULL,
  `response_text` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_anr_application` (`application_id`),
  KEY `idx_anr_note` (`note_id`),
  KEY `idx_anr_client` (`client_user_id`),
  CONSTRAINT `fk_anr_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_anr_note` FOREIGN KEY (`note_id`) REFERENCES `application_notes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_anr_client` FOREIGN KEY (`client_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) Mensajes internos cliente/equipo
CREATE TABLE IF NOT EXISTS `client_messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `application_id` INT(11) NOT NULL,
  `sender_user_id` INT(11) NOT NULL,
  `sender_role` ENUM('Cliente','Equipo') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read_by_client` TINYINT(1) NOT NULL DEFAULT 0,
  `is_read_by_staff` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_client_messages_application` (`application_id`),
  KEY `idx_client_messages_sender` (`sender_user_id`),
  KEY `idx_client_messages_read_staff` (`is_read_by_staff`),
  CONSTRAINT `fk_client_messages_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_client_messages_sender` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limpieza de procedimientos temporales
DROP PROCEDURE IF EXISTS add_column_if_missing;
DROP PROCEDURE IF EXISTS add_index_if_missing;
DROP PROCEDURE IF EXISTS add_fk_if_missing;
