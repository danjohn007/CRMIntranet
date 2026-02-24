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
    $logo = getConfig('site_logo', null);
    // Validate logo path is relative and doesn't contain protocols
    if ($logo && (strpos($logo, '://') !== false || strpos($logo, 'javascript:') === 0)) {
        error_log("Invalid logo path detected: $logo");
        return null;
    }
    return $logo;
}

/**
 * Get site name
 * @return string Site name
 */
function getSiteName() {
    return getConfig('site_name', SITE_NAME);
}

/**
 * Log audit trail event
 * @param string $action Action performed (login, logout, create, update, delete, etc)
 * @param string $module Module name (usuarios, solicitudes, formularios, etc)
 * @param string $description Detailed description of the action
 * @param array $metadata Additional metadata (optional)
 * @return bool Success status
 */
function logAudit($action, $module, $description, $metadata = []) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get current user info
        $userId = $_SESSION['user_id'] ?? null;
        $userName = $_SESSION['user_name'] ?? null;
        $userEmail = $_SESSION['user_email'] ?? null;
        
        // Get IP and User Agent
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Prepare statement
        $stmt = $db->prepare("
            INSERT INTO audit_trail 
            (user_id, user_name, user_email, action, module, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $userName,
            $userEmail,
            $action,
            $module,
            $description,
            $ipAddress,
            $userAgent
        ]);
        
        return true;
    } catch (PDOException $e) {
        // Don't throw exception, just log error
        error_log("Error logging audit: " . $e->getMessage());
        return false;
    }
}

/**
 * Log customer journey touchpoint
 * @param int $applicationId Application ID
 * @param string $touchpointType Type of touchpoint (email, call, meeting, status_change, etc)
 * @param string $title Short title of the touchpoint
 * @param string $description Detailed description
 * @param string|null $contactMethod How contact was made (email, phone, in-person, online)
 * @param array $metadata Additional metadata in array format
 * @return bool Success status
 */
function logCustomerJourney($applicationId, $touchpointType, $title, $description = '', $contactMethod = null, $metadata = []) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $userId = $_SESSION['user_id'] ?? null;
        $metadataJson = !empty($metadata) ? json_encode($metadata) : null;
        
        $stmt = $db->prepare("
            INSERT INTO customer_journey 
            (application_id, touchpoint_type, touchpoint_title, touchpoint_description, contact_method, user_id, metadata_json)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $applicationId,
            $touchpointType,
            $title,
            $description,
            $contactMethod,
            $userId,
            $metadataJson
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error logging customer journey: " . $e->getMessage());
        return false;
    }
}

/**
 * Get upcoming appointment notifications for the current user.
 * Returns appointments within the next 2 days that have not yet been attended.
 * Advisors only see their own clients; Admin/Gerente see all.
 *
 * @return array List of notification items with keys:
 *               application_id, folio, client_name, notification_type,
 *               appointment_date, location, is_read
 */
function getUpcomingNotifications() {
    $userId   = $_SESSION['user_id']   ?? null;
    $userRole = $_SESSION['user_role'] ?? null;

    if (!$userId || !$userRole) {
        return [];
    }

    try {
        $db = Database::getInstance()->getConnection();

        $today    = date('Y-m-d');
        $deadline = date('Y-m-d', strtotime('+2 days'));

        // Generic (non-Canadian) upcoming appointments
        $sqlGeneric = "
            SELECT
                a.id            AS application_id,
                a.folio,
                a.created_by,
                JSON_UNQUOTE(JSON_EXTRACT(a.data_json, '$.nombre')) AS client_name,
                'appointment'   AS notification_type,
                a.appointment_date AS appointment_date,
                NULL            AS location,
                CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END AS is_read
            FROM applications a
            LEFT JOIN notification_reads nr
                   ON nr.application_id = a.id
                  AND nr.notification_type = 'appointment'
                  AND nr.user_id = ?
            WHERE a.appointment_date IS NOT NULL
              AND DATE(a.appointment_date) >= ?
              AND DATE(a.appointment_date) <= ?
              AND (a.client_attended IS NULL OR a.client_attended = 0)
        ";

        // Canadian visa biometric upcoming appointments
        $sqlBiometric = "
            SELECT
                a.id            AS application_id,
                a.folio,
                a.created_by,
                JSON_UNQUOTE(JSON_EXTRACT(a.data_json, '$.nombre')) AS client_name,
                'biometric'     AS notification_type,
                a.canadian_biometric_date AS appointment_date,
                a.canadian_biometric_location AS location,
                CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END AS is_read
            FROM applications a
            LEFT JOIN notification_reads nr
                   ON nr.application_id = a.id
                  AND nr.notification_type = 'biometric'
                  AND nr.user_id = ?
            WHERE a.canadian_biometric_date IS NOT NULL
              AND DATE(a.canadian_biometric_date) >= ?
              AND DATE(a.canadian_biometric_date) <= ?
              AND (a.canadian_client_attended_biometrics IS NULL OR a.canadian_client_attended_biometrics = 0)
        ";

        $advisorFilter = '';
        if ($userRole === 'Asesor') {
            $advisorFilter = ' AND a.created_by = ?';
        }

        $sql = "($sqlGeneric $advisorFilter) UNION ALL ($sqlBiometric $advisorFilter) ORDER BY appointment_date ASC";

        $params = [$userId, $today, $deadline];
        if ($userRole === 'Asesor') {
            $params[] = $userId;
        }
        $params[] = $userId;
        $params[] = $today;
        $params[] = $deadline;
        if ($userRole === 'Asesor') {
            $params[] = $userId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
        return [];
    }
}
