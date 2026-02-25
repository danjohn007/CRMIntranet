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
 * Get all unique email recipients for an application appointment notification.
 *
 * Collects emails from:
 *   1. Basic application data (data_json.email)
 *   2. Information sheet (information_sheets.client_email)
 *   3. Any 'email' type fields in the client form response (data_json)
 *   4. The asesor who created the application (users.email)
 *   5. All active Admins and Gerentes (users.email)
 *
 * Returned array is deduplicated and all values are validated email addresses.
 *
 * @param int    $applicationId
 * @param PDO    $db
 * @return array List of unique email strings
 */
function getApplicationEmailRecipients($applicationId, $db) {
    $emails = [];

    try {
        // 1 & 3: Application basic data + form fields
        $stmt = $db->prepare("
            SELECT a.data_json, a.form_id, a.created_by
            FROM applications a
            WHERE a.id = ?
        ");
        $stmt->execute([$applicationId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            return [];
        }

        $basicData = json_decode($app['data_json'], true) ?: [];

        // Email from basic registration fields
        if (!empty($basicData['email']) && filter_var(trim($basicData['email']), FILTER_VALIDATE_EMAIL)) {
            $emails[] = strtolower(trim($basicData['email']));
        }

        // Any email-type fields in the linked form schema
        if (!empty($app['form_id'])) {
            try {
                $stmtF = $db->prepare("SELECT fields_json FROM forms WHERE id = ?");
                $stmtF->execute([$app['form_id']]);
                $form = $stmtF->fetch(PDO::FETCH_ASSOC);
                if ($form) {
                    $fieldsData = json_decode($form['fields_json'], true) ?: [];
                    foreach ($fieldsData['fields'] ?? [] as $field) {
                        if (($field['type'] ?? '') === 'email' && !empty($basicData[$field['id'] ?? ''])) {
                            $val = trim($basicData[$field['id']]);
                            if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
                                $emails[] = strtolower($val);
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                // Ignore if form not found
            }
        }

        // 2: Information sheet client email
        try {
            $stmtS = $db->prepare("SELECT client_email FROM information_sheets WHERE application_id = ?");
            $stmtS->execute([$applicationId]);
            $sheet = $stmtS->fetch(PDO::FETCH_ASSOC);
            if ($sheet && !empty($sheet['client_email']) && filter_var(trim($sheet['client_email']), FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower(trim($sheet['client_email']));
            }
        } catch (PDOException $e) {
            // Table may not exist yet
        }

        // 4: Asesor email
        $stmtU = $db->prepare("SELECT email FROM users WHERE id = ? AND is_active = 1");
        $stmtU->execute([$app['created_by']]);
        $asesor = $stmtU->fetch(PDO::FETCH_ASSOC);
        if ($asesor && !empty($asesor['email']) && filter_var(trim($asesor['email']), FILTER_VALIDATE_EMAIL)) {
            $emails[] = strtolower(trim($asesor['email']));
        }

        // 5: All active Admins and Gerentes
        $stmtM = $db->prepare("SELECT email FROM users WHERE role IN ('Administrador', 'Gerente') AND is_active = 1");
        $stmtM->execute();
        foreach ($stmtM->fetchAll(PDO::FETCH_ASSOC) as $manager) {
            if (!empty($manager['email']) && filter_var(trim($manager['email']), FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower(trim($manager['email']));
            }
        }

    } catch (PDOException $e) {
        error_log("Error collecting email recipients for application #$applicationId: " . $e->getMessage());
    }

    return array_values(array_unique(array_filter($emails)));
}

/**
 * Send an appointment notification email (creation or 2-day reminder).
 *
 * @param int    $applicationId  Application ID
 * @param string $type           'consular' or 'biometric'
 * @param string $appointmentDate Date/datetime string of the appointment
 * @param bool   $isReminder     true = reminder email, false = creation email
 * @param PDO    $db
 * @return bool  true if all emails sent without error, false otherwise
 */
function sendAppointmentNotificationEmail($applicationId, $type, $appointmentDate, $isReminder, $db) {
    // Load PHPMailer
    require_once ROOT_PATH . '/vendor/autoload.php';

    // Read SMTP configuration
    try {
        $stmt = $db->query("SELECT config_key, config_value FROM global_config WHERE config_key IN ('smtp_user','smtp_password','smtp_host','smtp_port','site_name')");
        $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("Error reading SMTP config for appointment email: " . $e->getMessage());
        return false;
    }

    $smtpHost     = $config['smtp_host']     ?? '';
    $smtpUser     = $config['smtp_user']     ?? '';
    $smtpPassword = $config['smtp_password'] ?? '';
    $smtpPort     = (int)($config['smtp_port'] ?? 465);
    $siteName     = $config['site_name']     ?? (defined('SITE_NAME') ? SITE_NAME : 'CRM Visas');

    if (empty($smtpHost) || empty($smtpUser) || empty($smtpPassword)) {
        error_log("Incomplete SMTP config — skipping appointment email for application #$applicationId.");
        return false;
    }

    // Collect recipients
    $recipients = getApplicationEmailRecipients($applicationId, $db);
    if (empty($recipients)) {
        error_log("No email recipients found for application #$applicationId.");
        return false;
    }

    // Fetch application details
    try {
        $stmtA = $db->prepare("
            SELECT a.*, u.full_name AS creator_name, f.name AS form_name
            FROM applications a
            LEFT JOIN users u ON a.created_by = u.id
            LEFT JOIN forms f ON a.form_id = f.id
            WHERE a.id = ?
        ");
        $stmtA->execute([$applicationId]);
        $application = $stmtA->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching application #$applicationId for email: " . $e->getMessage());
        return false;
    }

    if (!$application) {
        return false;
    }

    // Fetch PDF documents to attach
    $pdfDocuments = [];
    try {
        $stmtD = $db->prepare("SELECT name, file_path FROM documents WHERE application_id = ?");
        $stmtD->execute([$applicationId]);
        foreach ($stmtD->fetchAll(PDO::FETCH_ASSOC) as $doc) {
            $ext      = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
            $fullPath = ROOT_PATH . '/public' . $doc['file_path'];
            if ($ext === 'pdf' && file_exists($fullPath)) {
                $pdfDocuments[] = ['path' => $fullPath, 'name' => basename($doc['name']) . '.pdf'];
            }
        }
    } catch (PDOException $e) {
        // Non-fatal: proceed without attachments
    }

    // Build email content
    $data       = json_decode($application['data_json'], true) ?: [];
    $clientName = trim(($application['client_name'] ?? '') ?: (($data['nombre'] ?? '') . ' ' . ($data['apellidos'] ?? '')));
    if (empty($clientName)) {
        $clientName = 'Cliente';
    }
    $folio       = $application['folio'];
    $isCanadian  = !empty($application['is_canadian_visa']);
    $advisorName = htmlspecialchars($application['creator_name'] ?? 'Asesor', ENT_QUOTES, 'UTF-8');

    $dateFormatted = date('d/m/Y H:i', strtotime($appointmentDate));

    if ($type === 'biometric') {
        $appointmentLabel = 'Cita Biométrica';
        $location         = $application['canadian_biometric_location'] ?? '';
    } else {
        $appointmentLabel = 'Cita Consular';
        $location         = '';
    }

    if ($isCanadian) {
        $tramiteLabel = htmlspecialchars(
            ($application['canadian_tipo'] ?? 'Visa Canadiense') .
            ($application['canadian_modalidad'] ? ' — ' . $application['canadian_modalidad'] : ''),
            ENT_QUOTES, 'UTF-8'
        );
    } else {
        $tramiteLabel = htmlspecialchars(
            $application['type'] . ($application['subtype'] ? ' — ' . $application['subtype'] : ''),
            ENT_QUOTES, 'UTF-8'
        );
    }

    if ($isReminder) {
        $subjectPrefix = '⏰ Recordatorio de cita';
        $headerText    = 'Recordatorio: ' . $appointmentLabel;
        $introText     = 'Le recordamos que la siguiente cita está programada próximamente (en los próximos 2 días).';
    } else {
        $subjectPrefix = '📅 Cita agendada';
        $headerText    = $appointmentLabel . ' Confirmada';
        $introText     = 'Se ha agendado exitosamente una cita para el siguiente trámite.';
    }

    $subject = "$subjectPrefix — $folio — $siteName";

    $locationRow = !empty($location)
        ? '<tr><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;width:40%;">Lugar</th>'
          . '<td style="padding:8px 12px;color:#111827;">' . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        : '';

    $body = '<div style="font-family:Arial,sans-serif;max-width:620px;margin:0 auto;padding:0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">'
        . '<div style="background-color:#3b82f6;padding:24px;text-align:center;">'
        . '<h1 style="color:#ffffff;margin:0;font-size:22px;">📅 ' . htmlspecialchars($headerText, ENT_QUOTES, 'UTF-8') . '</h1>'
        . '<p style="color:#bfdbfe;margin:8px 0 0;">' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '</p>'
        . '</div>'
        . '<div style="padding:24px;">'
        . '<p style="color:#374151;font-size:15px;">' . htmlspecialchars($introText, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">'
        . '<tr style="background:#f3f4f6;"><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;width:40%;">Folio</th><td style="padding:8px 12px;color:#111827;">' . htmlspecialchars($folio, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;">Cliente</th><td style="padding:8px 12px;color:#111827;">' . htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr style="background:#f3f4f6;"><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;">Trámite</th><td style="padding:8px 12px;color:#111827;">' . $tramiteLabel . '</td></tr>'
        . '<tr><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;">Tipo de Cita</th><td style="padding:8px 12px;color:#111827;">' . htmlspecialchars($appointmentLabel, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr style="background:#f3f4f6;"><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;">Fecha y Hora</th><td style="padding:8px 12px;color:#1d4ed8;font-weight:bold;">' . htmlspecialchars($dateFormatted, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . $locationRow
        . '<tr><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;">Asesor</th><td style="padding:8px 12px;color:#111827;">' . $advisorName . '</td></tr>'
        . '</table>'
        . '<div style="background-color:#fef9c3;border:2px solid #f59e0b;border-radius:8px;padding:16px;margin:20px 0;">'
        . '<p style="margin:0;font-size:15px;color:#92400e;font-weight:bold;">⚠️ Recordatorio importante</p>'
        . '<p style="margin:8px 0 0;font-size:14px;color:#78350f;">Recuerda verificar que tus citas sigan vigentes y listas para presentación enviando un WhatsApp al <strong>4424495675</strong>.</p>'
        . '</div>'
        . '<p style="color:#6b7280;font-size:12px;margin-top:24px;border-top:1px solid #e5e7eb;padding-top:16px;">'
        . 'Este correo fue generado automáticamente por ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '. Por favor no responda a este mensaje.'
        . '</p>'
        . '</div>'
        . '</div>';

    $altBody = "Folio: $folio | Cliente: $clientName | $appointmentLabel: $dateFormatted"
        . (!empty($location) ? " | Lugar: $location" : '')
        . " | Asesor: {$application['creator_name']}"
        . " | IMPORTANTE: Recuerda verificar que tus citas sigan vigentes enviando un WhatsApp al 4424495675.";

    // Send one email per unique recipient
    $sendErrors = [];
    foreach ($recipients as $recipient) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPassword;
            $mail->Port       = $smtpPort;
            $mail->SMTPSecure = ($smtpPort == 465)
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($smtpUser, $siteName);
            $mail->addAddress($recipient);

            // Attach PDF documents
            foreach ($pdfDocuments as $pdf) {
                $mail->addAttachment($pdf['path'], $pdf['name']);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody;
            $mail->send();

        } catch (Exception $e) {
            $sendErrors[] = $recipient . ': ' . (isset($mail) ? $mail->ErrorInfo : $e->getMessage());
        }
    }

    if (!empty($sendErrors)) {
        error_log("Appointment email errors for application #$applicationId: " . implode(' | ', $sendErrors));
    }

    return empty($sendErrors);
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
