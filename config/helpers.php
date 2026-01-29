<?php
/**
 * Helper function to get global configuration value
 * @param string $key Configuration key
 * @param mixed $default Default value if not found
 * @return mixed Configuration value or default
 */
function getConfig($key, $default = null) {
    static $configCache = null;
    
    // Load all configs once
    if ($configCache === null) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT config_key, config_value FROM global_config");
            $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $configCache = $configs;
        } catch (PDOException $e) {
            error_log("Error loading config: " . $e->getMessage());
            $configCache = [];
        }
    }
    
    return $configCache[$key] ?? $default;
}

/**
 * Get site logo path
 * @return string|null Logo path or null if not configured
 */
function getSiteLogo() {
    return getConfig('site_logo', null);
}

/**
 * Get site name
 * @return string Site name
 */
function getSiteName() {
    return getConfig('site_name', SITE_NAME);
}
