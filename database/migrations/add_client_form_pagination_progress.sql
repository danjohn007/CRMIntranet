-- Ajuste incremental: paginación del formulario del cliente y guardado de última sección
-- Ejecutar solo si ya aplicaste add_client_portal_module.sql.
-- Compatible con MySQL 5.7.

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

DELIMITER ;

CALL add_column_if_missing(
    'applications',
    'client_form_current_page',
    '`client_form_current_page` INT(11) NOT NULL DEFAULT 1 COMMENT ''Última página editada por el cliente'' AFTER `client_last_update_comment`'
);

DROP PROCEDURE IF EXISTS add_column_if_missing;
