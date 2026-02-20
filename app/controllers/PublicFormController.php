<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class PublicFormController extends BaseController {
    
    /**
     * Show public form by token (no authentication required)
     */
    public function show($token) {
        try {
            // Get form by public token
            $stmt = $this->db->prepare("
                SELECT f.*, u.full_name as creator_name, u.email as creator_email
                FROM forms f
                LEFT JOIN users u ON f.created_by = u.id
                WHERE f.public_token = ? AND f.is_published = 1 AND f.public_enabled = 1
            ");
            $stmt->execute([$token]);
            $form = $stmt->fetch();
            
            if (!$form) {
                http_response_code(404);
                echo "Formulario no encontrado o no disponible";
                return;
            }
            
            // Parse fields JSON
            $fields = json_decode($form['fields_json'], true);
            
            // Parse pages if pagination enabled
            $pages = null;
            if ($form['pagination_enabled'] && !empty($form['pages_json'])) {
                $pages = json_decode($form['pages_json'], true);
            }
            
            $this->viewPublic('public/form', [
                'form' => $form,
                'fields' => $fields,
                'pages' => $pages,
                'token' => $token
            ]);
            
        } catch (PDOException $e) {
            error_log("Error al cargar formulario público: " . $e->getMessage());
            http_response_code(500);
            echo "Error al cargar el formulario";
        }
    }
    
    /**
     * Submit public form data
     */
    public function submit($token) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }
        
        try {
            // Get form
            $stmt = $this->db->prepare("
                SELECT * FROM forms 
                WHERE public_token = ? AND is_published = 1 AND public_enabled = 1
            ");
            $stmt->execute([$token]);
            $form = $stmt->fetch();
            
            if (!$form) {
                http_response_code(404);
                echo json_encode(['error' => 'Formulario no encontrado']);
                return;
            }
            
            // Get submission data
            $submissionData = $_POST['formData'] ?? '{}';
            $currentPage = intval($_POST['currentPage'] ?? 1);
            $isCompleted = isset($_POST['isCompleted']) && $_POST['isCompleted'] === 'true';
            
            // Validate JSON
            $data = json_decode($submissionData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
                return;
            }
            
            // Calculate progress
            $fields = json_decode($form['fields_json'], true);
            $totalFields = count($fields['fields'] ?? []);
            $filledFields = 0;
            
            foreach ($fields['fields'] ?? [] as $field) {
                if (isset($data[$field['id']]) && !empty($data[$field['id']])) {
                    $filledFields++;
                }
            }
            
            $progressPercentage = $totalFields > 0 ? ($filledFields / $totalFields) * 100 : 0;
            
            // Get IP and User Agent
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Check if submission exists for this session
            $submissionId = $_POST['submissionId'] ?? null;
            
            if ($submissionId) {
                // Update existing submission
                $stmt = $this->db->prepare("
                    UPDATE public_form_submissions 
                    SET submission_data = ?, progress_percentage = ?, current_page = ?, 
                        is_completed = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND form_id = ?
                ");
                $stmt->execute([
                    $submissionData,
                    $progressPercentage,
                    $currentPage,
                    $isCompleted ? 1 : 0,
                    $submissionId,
                    $form['id']
                ]);
            } else {
                // Create new submission
                $stmt = $this->db->prepare("
                    INSERT INTO public_form_submissions 
                    (form_id, submission_data, progress_percentage, current_page, is_completed, ip_address, user_agent)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $form['id'],
                    $submissionData,
                    $progressPercentage,
                    $currentPage,
                    $isCompleted ? 1 : 0,
                    $ipAddress,
                    $userAgent
                ]);
                
                $submissionId = $this->db->lastInsertId();
            }
            
            // If completed, optionally create an application
            if ($isCompleted) {
                // Process file uploads
                $uploadedFiles = [];
                if (!empty($_FILES)) {
                    $fields = json_decode($form['fields_json'], true);
                    $fileFields = array_filter($fields['fields'] ?? [], function($field) {
                        return $field['type'] === 'file';
                    });
                    
                    foreach ($fileFields as $field) {
                        $fieldId = $field['id'];
                        if (isset($_FILES[$fieldId]) && $_FILES[$fieldId]['error'] === UPLOAD_ERR_OK) {
                            $file = $_FILES[$fieldId];
                            $fileName = $file['name'];
                            $fileSize = $file['size'];
                            $fileTmpName = $file['tmp_name'];
                            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            
                            // Validate file
                            if ($fileSize <= MAX_FILE_SIZE && in_array($fileType, ALLOWED_EXTENSIONS)) {
                                // Store file info for later
                                $uploadedFiles[$fieldId] = [
                                    'name' => $fileName,
                                    'tmp_name' => $fileTmpName,
                                    'size' => $fileSize,
                                    'type' => $fileType,
                                    'label' => $field['label']
                                ];
                                
                                // Update data JSON to store only filename
                                $data[$fieldId] = $fileName;
                            }
                        }
                    }
                    
                    // Update submission data with filenames
                    if (!empty($uploadedFiles)) {
                        $submissionData = json_encode($data, JSON_UNESCAPED_UNICODE);
                    }
                }
                
                // Generate folio
                $year = date('Y');
                $stmt = $this->db->prepare("
                    SELECT MAX(CAST(SUBSTRING(folio, -6) AS UNSIGNED)) as max_num 
                    FROM applications WHERE folio LIKE ?
                ");
                $stmt->execute(["VISA-$year-%"]);
                $result = $stmt->fetch();
                $nextNum = ($result['max_num'] ?? 0) + 1;
                $folio = sprintf('VISA-%s-%06d', $year, $nextNum);
                
                // Create application
                $stmt = $this->db->prepare("
                    INSERT INTO applications 
                    (folio, form_id, form_version, type, subtype, status, data_json, 
                     progress_percentage, is_draft, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?)
                ");
                $stmt->execute([
                    $folio,
                    $form['id'],
                    $form['version'],
                    $form['type'],
                    $form['subtype'],
                    STATUS_FORMULARIO_RECIBIDO,
                    $submissionData,
                    100,
                    $form['created_by']
                ]);
                
                $applicationId = $this->db->lastInsertId();
                
                // Save uploaded files as documents
                if (!empty($uploadedFiles)) {
                    $uploadDir = ROOT_PATH . '/public/uploads/applications/' . $applicationId;
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    foreach ($uploadedFiles as $fieldId => $fileInfo) {
                        $newFileName = bin2hex(random_bytes(16)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileInfo['name']);
                        $filePath = $uploadDir . '/' . $newFileName;
                        
                        if (move_uploaded_file($fileInfo['tmp_name'], $filePath)) {
                            // Save document record
                            $relativePath = '/uploads/applications/' . $applicationId . '/' . $newFileName;
                            $stmt = $this->db->prepare("
                                INSERT INTO documents (application_id, name, file_path, file_type, file_size, uploaded_by)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $applicationId,
                                $fileInfo['label'] . ' - ' . $fileInfo['name'],
                                $relativePath,
                                $fileInfo['type'],
                                $fileInfo['size'],
                                $form['created_by']
                            ]);
                        }
                    }
                }
                
                // Link submission to application
                $stmt = $this->db->prepare("
                    UPDATE public_form_submissions 
                    SET application_id = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$applicationId, $submissionId]);
                
                // Create initial status history
                $stmt = $this->db->prepare("
                    INSERT INTO status_history (application_id, new_status, comment, changed_by)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $applicationId,
                    STATUS_FORMULARIO_RECIBIDO,
                    'Solicitud creada desde formulario público',
                    $form['created_by']
                ]);
                
                // Create financial status
                $stmt = $this->db->prepare("
                    INSERT INTO financial_status (application_id, total_costs, total_paid, balance, status)
                    VALUES (?, 0, 0, 0, ?)
                ");
                $stmt->execute([$applicationId, FINANCIAL_PENDIENTE]);
                
                // Log customer journey
                $formName = htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8');
                logCustomerJourney(
                    $applicationId,
                    'form_submission',
                    'Formulario público completado',
                    "Formulario '$formName' completado vía enlace público",
                    'online'
                );
            }
            
            echo json_encode([
                'success' => true,
                'submissionId' => $submissionId,
                'progressPercentage' => round($progressPercentage, 2),
                'message' => $isCompleted ? 'Formulario enviado exitosamente' : 'Progreso guardado'
            ]);
            
        } catch (PDOException $e) {
            error_log("Error al guardar formulario público: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar el formulario']);
        }
    }
    
    /**
     * View for public forms (no main layout)
     */
    private function viewPublic($view, $data = []) {
        extract($data);
        $viewFile = ROOT_PATH . '/app/views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Vista no encontrada: $view";
        }
    }
}
