<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class ConfigController extends BaseController {
    
    public function index() {
        $this->requireRole([ROLE_ADMIN]);
        
        try {
            $stmt = $this->db->query("SELECT * FROM global_config ORDER BY config_key ASC");
            $configs = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
            
            // Convertir a array asociativo simple
            $configArray = [];
            foreach ($configs as $key => $value) {
                $configArray[$key] = $value[0];
            }
            
            $this->view('config/index', ['configs' => $configArray]);
            
        } catch (PDOException $e) {
            error_log("Error al cargar configuración: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar configuración';
            $this->view('config/index', ['configs' => []]);
        }
    }
    
    public function save() {
        $this->requireRole([ROLE_ADMIN]);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/configuracion');
        }
        
        try {
            $this->db->beginTransaction();
            
            // Actualizar cada configuración
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'config_') === 0) {
                    $configKey = str_replace('config_', '', $key);
                    $configValue = trim($value);
                    
                    $stmt = $this->db->prepare("
                        INSERT INTO global_config (config_key, config_value, config_type)
                        VALUES (?, ?, 'text')
                        ON DUPLICATE KEY UPDATE config_value = ?
                    ");
                    $stmt->execute([$configKey, $configValue, $configValue]);
                }
            }
            
            // Manejar archivo de logo si se subió
            if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['site_logo'];
                $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // Validar tipo de archivo
                if (!in_array($fileType, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
                    throw new Exception('Tipo de archivo no permitido para el logo');
                }
                
                // Crear directorio si no existe
                $uploadDir = ROOT_PATH . '/public/uploads/config';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generar nombre único
                $newFileName = 'logo_' . time() . '.' . $fileType;
                $filePath = $uploadDir . '/' . $newFileName;
                
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $relativePath = '/uploads/config/' . $newFileName;
                    
                    $stmt = $this->db->prepare("
                        INSERT INTO global_config (config_key, config_value, config_type)
                        VALUES ('site_logo', ?, 'file')
                        ON DUPLICATE KEY UPDATE config_value = ?
                    ");
                    $stmt->execute([$relativePath, $relativePath]);
                }
            }
            
            $this->db->commit();
            $_SESSION['success'] = 'Configuración guardada exitosamente';
            $this->redirect('/configuracion');
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al guardar configuración: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar configuración: ' . $e->getMessage();
            $this->redirect('/configuracion');
        }
    }

    public function testEmail() {
        $this->requireRole([ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }

        $toEmail = trim($_POST['to_email'] ?? '');
        if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Ingresa un correo destinatario válido']);
        }

        // Leer configuración SMTP desde global_config (siempre de BD)
        try {
            $stmt = $this->db->query("SELECT config_key, config_value FROM global_config WHERE config_key IN ('smtp_user','smtp_password','smtp_host','smtp_port','email_from','site_name')");
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            $this->json(['success' => false, 'message' => 'Error al leer configuración SMTP: ' . $e->getMessage()]);
        }

        $smtpHost     = $rows['smtp_host']     ?? '';
        $smtpUser     = $rows['smtp_user']     ?? '';
        $smtpPassword = $rows['smtp_password'] ?? '';
        $smtpPort     = (int)($rows['smtp_port'] ?? 465);
        $fromEmail    = $rows['email_from']    ?? $smtpUser;
        $fromName     = $rows['site_name']     ?? 'CRM';

        if (!$smtpHost || !$smtpUser || !$smtpPassword) {
            $this->json(['success' => false, 'message' => 'Configuración SMTP incompleta en global_config (smtp_host, smtp_user, smtp_password son requeridos)']);
        }

        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $this->json(['success' => false, 'message' => 'PHPMailer no está disponible. Verifica que vendor/autoload.php esté cargado.']);
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPassword;
            $mail->SMTPSecure = ($smtpPort === 465)
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtpPort;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail);
            $mail->Subject = 'Correo de prueba - ' . $fromName;
            $mail->isHTML(true);
            $mail->Body = '<p>Este es un <strong>correo de prueba</strong> enviado desde <em>' . htmlspecialchars($fromName) . '</em>.</p>'
                        . '<p>Configuración SMTP:<br>'
                        . 'Servidor: ' . htmlspecialchars($smtpHost) . '<br>'
                        . 'Puerto: ' . $smtpPort . '<br>'
                        . 'Usuario: ' . htmlspecialchars($smtpUser) . '</p>';
            $mail->AltBody = 'Correo de prueba desde ' . $fromName . '. Servidor: ' . $smtpHost . ', Puerto: ' . $smtpPort;

            $mail->send();
            $this->json(['success' => true, 'message' => 'Correo de prueba enviado correctamente a ' . htmlspecialchars($toEmail)]);
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al enviar correo: ' . $mail->ErrorInfo]);
        }
    }
}
