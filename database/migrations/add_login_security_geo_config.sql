-- Migration: Add login security and geo login configuration
-- Date: 2026-07-09

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_lookup` (`username`, `ip_address`, `attempted_at`),
  KEY `idx_login_attempts_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `global_config` (`config_key`, `config_value`, `config_type`) VALUES
('geo_login_enabled', '0', 'boolean'),
('geo_login_latitude', '', 'number'),
('geo_login_longitude', '', 'number'),
('geo_login_radius_meters', '100', 'number'),
('login_max_attempts', '5', 'number'),
('login_lockout_minutes', '15', 'number'),
('session_idle_timeout_minutes', '30', 'number')
ON DUPLICATE KEY UPDATE config_type = VALUES(config_type);
