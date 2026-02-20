<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class ApplicationController extends BaseController {
    
    public function index() {
        $this->requireLogin();
        
        $role = $this->getUserRole();
        $userId = $_SESSION['user_id'];
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        // Filtros
        $status = $_GET['status'] ?? '';
        $type = $_GET['type'] ?? '';
        
        try {
            // Construir query según rol
            $where = [];
            $params = [];
            
            if ($role === ROLE_ASESOR) {
                // REGLA CRÍTICA: Asesor solo puede ver SUS PROPIAS solicitudes y no las cerradas
                $where[] = "a.created_by = ?";
                $params[] = $userId;
                $where[] = "a.status != ?";
                $params[] = STATUS_TRAMITE_CERRADO;
            }
            
            if (!empty($status)) {
                $where[] = "a.status = ?";
                $params[] = $status;
            }
            
            if (!empty($type)) {
                $where[] = "a.type = ?";
                $params[] = $type;
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Contar total
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM applications a $whereClause");
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];
            
            // Obtener solicitudes
            $stmt = $this->db->prepare("
                SELECT a.*, u.full_name as creator_name,
                       f.name as form_name,
                       fs.status as financial_status
                FROM applications a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN forms f ON a.form_id = f.id
                LEFT JOIN financial_status fs ON a.id = fs.application_id
                $whereClause
                ORDER BY a.created_at DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $applications = $stmt->fetchAll();
            
            $totalPages = ceil($total / $limit);
            
            $this->view('applications/index', [
                'applications' => $applications,
                'page' => $page,
                'totalPages' => $totalPages,
                'total' => $total,
                'status' => $status,
                'type' => $type
            ]);
            
        } catch (PDOException $e) {
            error_log("Error en listado de solicitudes: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar solicitudes';
            $this->view('applications/index', ['applications' => []]);
        }
    }
    
    public function create() {
        $this->requireLogin();
        
        try {
            // Obtener formularios publicados
            $stmt = $this->db->query("
                SELECT * FROM forms 
                WHERE is_published = 1 
                ORDER BY type, name
            ");
            $forms = $stmt->fetchAll();
            
            $this->view('applications/create', ['forms' => $forms]);
            
        } catch (PDOException $e) {
            error_log("Error al cargar formularios: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar formularios';
            $this->redirect('/solicitudes');
        }
    }
    
    public function store() {
        $this->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/crear');
        }
        
        $formId = intval($_POST['form_id'] ?? 0);
        $formData = $_POST['form_data'] ?? [];
        
        if ($formId <= 0) {
            $_SESSION['error'] = 'Debe seleccionar un formulario';
            $this->redirect('/solicitudes/crear');
        }
        
        try {
            // Obtener información del formulario
            $stmt = $this->db->prepare("SELECT * FROM forms WHERE id = ? AND is_published = 1");
            $stmt->execute([$formId]);
            $form = $stmt->fetch();
            
            if (!$form) {
                $_SESSION['error'] = 'Formulario no encontrado';
                $this->redirect('/solicitudes/crear');
            }
            
            // Generar folio único
            $year = date('Y');
            $stmt = $this->db->prepare("
                SELECT MAX(CAST(SUBSTRING(folio, -6) AS UNSIGNED)) as last_number
                FROM applications
                WHERE folio LIKE ?
            ");
            $stmt->execute(["VISA-$year-%"]);
            $result = $stmt->fetch();
            $nextNumber = ($result['last_number'] ?? 0) + 1;
            $folio = sprintf("VISA-%s-%06d", $year, $nextNumber);
            
            // Crear solicitud
            $stmt = $this->db->prepare("
                INSERT INTO applications (folio, form_id, form_version, type, subtype, data_json, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $folio,
                $formId,
                $form['version'],
                $form['type'],
                $form['subtype'],
                json_encode($formData, JSON_UNESCAPED_UNICODE),
                $_SESSION['user_id']
            ]);
            
            $applicationId = $this->db->lastInsertId();
            
            // Crear registro de historial
            $stmt = $this->db->prepare("
                INSERT INTO status_history (application_id, new_status, comment, changed_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $applicationId,
                STATUS_NUEVO,
                'Solicitud creada',
                $_SESSION['user_id']
            ]);
            
            // Crear estado financiero inicial
            $stmt = $this->db->prepare("
                INSERT INTO financial_status (application_id, total_costs, total_paid, balance, status)
                VALUES (?, 0, 0, 0, ?)
            ");
            $stmt->execute([$applicationId, FINANCIAL_PENDIENTE]);
            
            $_SESSION['success'] = "Solicitud creada exitosamente: $folio";
            $this->redirect('/solicitudes/ver/' . $applicationId);
            
        } catch (PDOException $e) {
            error_log("Error al crear solicitud: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear solicitud';
            $this->redirect('/solicitudes/crear');
        }
    }
    
    public function show($id) {
        $this->requireLogin();
        
        $role = $this->getUserRole();
        $userId = $_SESSION['user_id'];
        
        try {
            // Obtener solicitud
            $stmt = $this->db->prepare("
                SELECT a.*, u.full_name as creator_name,
                       f.name as form_name, f.fields_json,
                       fs.total_costs, fs.total_paid, fs.balance, fs.status as financial_status
                FROM applications a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN forms f ON a.form_id = f.id
                LEFT JOIN financial_status fs ON a.id = fs.application_id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $application = $stmt->fetch();
            
            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }
            
            // REGLA CRÍTICA: Asesor solo puede ver SUS PROPIAS solicitudes y no las cerradas
            if ($role === ROLE_ASESOR) {
                if ($application['status'] === STATUS_TRAMITE_CERRADO || $application['status'] === STATUS_FINALIZADO) {
                    $_SESSION['error'] = 'No tiene permisos para ver esta solicitud';
                    $this->redirect('/solicitudes');
                }
                if (intval($application['created_by']) !== intval($userId)) {
                    $_SESSION['error'] = 'No tiene permisos para ver esta solicitud';
                    $this->redirect('/solicitudes');
                }
            }
            
            // Obtener historial de estatus
            $stmt = $this->db->prepare("
                SELECT sh.*, u.full_name as changed_by_name
                FROM status_history sh
                LEFT JOIN users u ON sh.changed_by = u.id
                WHERE sh.application_id = ?
                ORDER BY sh.created_at ASC
            ");
            $stmt->execute([$id]);
            $history = $stmt->fetchAll();
            
            // Obtener documentos
            $stmt = $this->db->prepare("
                SELECT d.*, u.full_name as uploaded_by_name
                FROM documents d
                LEFT JOIN users u ON d.uploaded_by = u.id
                WHERE d.application_id = ?
                ORDER BY d.created_at DESC
            ");
            $stmt->execute([$id]);
            $documents = $stmt->fetchAll();
            
            // Obtener indicaciones/notas
            $stmt = $this->db->prepare("
                SELECT n.*, u.full_name as created_by_name, u.role as created_by_role
                FROM application_notes n
                LEFT JOIN users u ON n.created_by = u.id
                WHERE n.application_id = ?
                ORDER BY n.is_important DESC, n.created_at DESC
            ");
            $stmt->execute([$id]);
            $notes = $stmt->fetchAll();
            
            // Obtener costos (solo Admin y Gerente)
            $costs = [];
            $payments = [];
            if ($this->canAccessFinancial()) {
                $stmt = $this->db->prepare("
                    SELECT fc.*, u.full_name as created_by_name
                    FROM financial_costs fc
                    LEFT JOIN users u ON fc.created_by = u.id
                    WHERE fc.application_id = ?
                    ORDER BY fc.created_at DESC
                ");
                $stmt->execute([$id]);
                $costs = $stmt->fetchAll();
                
                $stmt = $this->db->prepare("
                    SELECT p.*, u.full_name as registered_by_name
                    FROM payments p
                    LEFT JOIN users u ON p.registered_by = u.id
                    WHERE p.application_id = ?
                    ORDER BY p.payment_date DESC
                ");
                $stmt->execute([$id]);
                $payments = $stmt->fetchAll();
            }

            // Obtener hoja de información si existe
            $infoSheet = null;
            try {
                $stmt = $this->db->prepare("SELECT * FROM information_sheets WHERE application_id = ?");
                $stmt->execute([$id]);
                $infoSheet = $stmt->fetch() ?: null;
            } catch (PDOException $e) {
                // Tabla puede no existir aún
            }

            // Obtener formularios publicados (para dropdown de envío a cliente)
            $publishedForms = [];
            try {
                $stmt = $this->db->query("SELECT id, name, type, subtype FROM forms WHERE is_published = 1 ORDER BY type, name");
                $publishedForms = $stmt->fetchAll();
            } catch (PDOException $e) {}

            // Obtener token público del formulario vinculado (para generar enlace de cliente)
            $formLinkToken = null;
            if (!empty($application['form_link_id'])) {
                try {
                    $stmt = $this->db->prepare("SELECT public_token FROM forms WHERE id = ?");
                    $stmt->execute([$application['form_link_id']]);
                    $linkedFormRow = $stmt->fetch();
                    $formLinkToken = $linkedFormRow['public_token'] ?? null;
                } catch (PDOException $e) {}
            }
            
            $this->view('applications/show', [
                'application' => $application,
                'history' => $history,
                'documents' => $documents,
                'notes' => $notes,
                'costs' => $costs,
                'payments' => $payments,
                'infoSheet' => $infoSheet,
                'publishedForms' => $publishedForms,
                'formLinkToken' => $formLinkToken,
            ]);
            
        } catch (PDOException $e) {
            error_log("Error al ver solicitud: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar solicitud';
            $this->redirect('/solicitudes');
        }
    }
    
    public function changeStatus($id) {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }
        
        $newStatus = $_POST['status'] ?? '';
        $comment = trim($_POST['comment'] ?? '');
        
        if (empty($newStatus)) {
            $_SESSION['error'] = 'Debe seleccionar un estatus';
            $this->redirect('/solicitudes/ver/' . $id);
        }
        
        // Validar comentario obligatorio en rechazo
        if ($newStatus === STATUS_RECHAZADO && empty($comment)) {
            $_SESSION['error'] = 'El comentario es obligatorio para rechazar una solicitud';
            $this->redirect('/solicitudes/ver/' . $id);
        }
        
        try {
            // Obtener estatus actual
            $stmt = $this->db->prepare("SELECT status FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();
            
            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }
            
            $previousStatus = $application['status'];
            
            // Actualizar estatus con campos adicionales según el nuevo estado
            $extraSql = '';
            $extraParams = [];
            if ($newStatus === STATUS_EN_ESPERA_PAGO) {
                $officialDone = isset($_POST['official_application_done']) ? 1 : 0;
                $feeSent      = isset($_POST['consular_fee_sent']) ? 1 : 0;
                $extraSql = ', official_application_done = ?, consular_fee_sent = ?';
                $extraParams = [$officialDone, $feeSent];
            } elseif ($newStatus === STATUS_TRAMITE_CERRADO) {
                $dhlTracking  = trim($_POST['dhl_tracking'] ?? '');
                $deliveryDate = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
                $extraSql = ', dhl_tracking = ?, delivery_date = ?';
                $extraParams = [$dhlTracking ?: null, $deliveryDate];
            }
            $stmt = $this->db->prepare("UPDATE applications SET status = ? $extraSql WHERE id = ?");
            $stmt->execute(array_merge([$newStatus], $extraParams, [$id]));
            
            // Registrar en historial
            $stmt = $this->db->prepare("
                INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $previousStatus,
                $newStatus,
                $comment,
                $_SESSION['user_id']
            ]);
            
            // Log audit trail
            logAudit('update', 'solicitudes', 
                "Cambio de estatus de solicitud #$id: $previousStatus → $newStatus");
            
            // Log customer journey
            logCustomerJourney(
                $id,
                'status_change',
                "Cambio de estatus: $newStatus",
                $comment,
                'online'
            );
            
            $_SESSION['success'] = 'Estatus actualizado correctamente';
            $this->redirect('/solicitudes/ver/' . $id);
            
        } catch (PDOException $e) {
            error_log("Error al cambiar estatus: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar estatus';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }
    
    public function uploadDocument($id) {
        $this->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }
        
        // Verificar que la solicitud existe y el usuario tiene acceso
        $role = $this->getUserRole();
        try {
            $stmt = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();
            
            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }
            
            // REGLA: Asesor solo puede acceder a sus propias solicitudes y no las cerradas
            if ($role === ROLE_ASESOR) {
                if ($application['status'] === STATUS_TRAMITE_CERRADO || $application['status'] === STATUS_FINALIZADO) {
                    $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                    $this->redirect('/solicitudes');
                }
                if (intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                    $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                    $this->redirect('/solicitudes');
                }
            }
            
            // Procesar archivo
            if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'Error al subir el archivo';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            $file = $_FILES['document'];
            $fileName = $file['name'];
            $fileSize = $file['size'];
            $fileTmpName = $file['tmp_name'];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Tipo de documento
            $docType = trim($_POST['doc_type'] ?? 'adicional');
            $allowedDocTypes = ['pasaporte_vigente', 'visa_anterior', 'ficha_pago_consular', 'adicional'];
            if (!in_array($docType, $allowedDocTypes)) {
                $docType = 'adicional';
            }
            
            // Validaciones
            if ($fileSize > MAX_FILE_SIZE) {
                $_SESSION['error'] = 'El archivo excede el tamaño máximo permitido (2MB)';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            if (!in_array($fileType, ALLOWED_EXTENSIONS)) {
                $_SESSION['error'] = 'Tipo de archivo no permitido';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            // Solo puede haber una ficha de pago consular
            if ($docType === 'ficha_pago_consular') {
                $stmtCheck = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'ficha_pago_consular'");
                $stmtCheck->execute([$id]);
                if ($stmtCheck->fetch()) {
                    $_SESSION['error'] = 'Ya existe una ficha de pago consular para esta solicitud';
                    $this->redirect('/solicitudes/ver/' . $id);
                }
            }
            
            // Crear directorio si no existe
            $uploadDir = ROOT_PATH . '/public/uploads/applications/' . $id;
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generar nombre único
            $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
            $filePath = $uploadDir . '/' . $newFileName;
            
            if (!move_uploaded_file($fileTmpName, $filePath)) {
                $_SESSION['error'] = 'Error al guardar el archivo';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            // Guardar en base de datos (con doc_type si la columna existe)
            $relativePath = '/uploads/applications/' . $id . '/' . $newFileName;
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO documents (application_id, name, doc_type, file_path, file_type, file_size, uploaded_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $id,
                    $fileName,
                    $docType,
                    $relativePath,
                    $fileType,
                    $fileSize,
                    $_SESSION['user_id']
                ]);
            } catch (PDOException $e) {
                // Fallback si la columna doc_type aún no existe
                error_log('doc_type column missing, using fallback: ' . $e->getMessage());
                $stmt = $this->db->prepare("
                    INSERT INTO documents (application_id, name, file_path, file_type, file_size, uploaded_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $id,
                    $fileName,
                    $relativePath,
                    $fileType,
                    $fileSize,
                    $_SESSION['user_id']
                ]);
            }
            
            $_SESSION['success'] = 'Documento subido correctamente';
            $this->redirect('/solicitudes/ver/' . $id);
            
        } catch (PDOException $e) {
            error_log("Error al subir documento: " . $e->getMessage());
            $_SESSION['error'] = 'Error al subir documento';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }
    
    public function addNote($id) {
        // Solo Admin y Gerente pueden agregar indicaciones
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }
        
        $noteText = trim($_POST['note_text'] ?? '');
        $isImportant = isset($_POST['is_important']) ? 1 : 0;
        
        if (empty($noteText)) {
            $_SESSION['error'] = 'La indicación no puede estar vacía';
            $this->redirect('/solicitudes/ver/' . $id);
        }
        
        try {
            // Verificar que la solicitud existe
            $stmt = $this->db->prepare("SELECT id FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }
            
            // Insertar indicación
            $stmt = $this->db->prepare("
                INSERT INTO application_notes (application_id, note_text, is_important, created_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $noteText,
                $isImportant,
                $_SESSION['user_id']
            ]);
            
            // Log audit trail
            logAudit('create', 'solicitudes', 
                "Indicación agregada a solicitud #$id" . ($isImportant ? ' (Importante)' : ''));
            
            $_SESSION['success'] = 'Indicación agregada correctamente';
            $this->redirect('/solicitudes/ver/' . $id);
            
        } catch (PDOException $e) {
            error_log("Error al agregar indicación: " . $e->getMessage());
            $_SESSION['error'] = 'Error al agregar indicación';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }
    
    public function downloadFormFile($id, $fieldId) {
        $this->requireLogin();
        
        $role = $this->getUserRole();
        
        try {
            // Obtener solicitud
            $stmt = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();
            
            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }
            
            // REGLA: Asesor no puede acceder a solicitudes finalizadas ni rechazadas
            if ($role === ROLE_ASESOR && ($application['status'] === STATUS_FINALIZADO || $application['status'] === STATUS_RECHAZADO)) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/solicitudes');
            }
            
            // Solo Admin y Gerente pueden descargar archivos
            if (!in_array($role, [ROLE_ADMIN, ROLE_GERENTE])) {
                $_SESSION['error'] = 'No tiene permisos para descargar archivos';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            // Obtener datos del formulario
            $formData = json_decode($application['data_json'], true);
            $formFields = json_decode($application['fields_json'], true);
            
            // Verificar que el campo existe y es de tipo file
            $isFileField = false;
            if ($formFields && isset($formFields['fields'])) {
                foreach ($formFields['fields'] as $field) {
                    if ($field['id'] === $fieldId && $field['type'] === 'file') {
                        $isFileField = true;
                        break;
                    }
                }
            }
            
            if (!$isFileField) {
                $_SESSION['error'] = 'Campo no válido';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            if (!isset($formData[$fieldId]) || empty($formData[$fieldId])) {
                $_SESSION['error'] = 'Archivo no encontrado en los datos';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            $fileName = $formData[$fieldId];
            
            // Buscar el archivo en la tabla de documents
            $stmt = $this->db->prepare("
                SELECT * FROM documents 
                WHERE application_id = ? AND (name LIKE ? OR file_path LIKE ?)
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$id, "%$fileName%", "%$fileName%"]);
            $document = $stmt->fetch();
            
            if (!$document) {
                $_SESSION['error'] = 'El documento no se encuentra registrado';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            $filePath = ROOT_PATH . '/public' . $document['file_path'];
            
            if (!file_exists($filePath)) {
                $_SESSION['error'] = 'El archivo no existe en el servidor';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            // Log audit trail
            logAudit('download', 'solicitudes', 
                "Descarga de archivo '$fileName' de solicitud #$id (campo: $fieldId)");
            
            // Descargar archivo
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
            
        } catch (PDOException $e) {
            error_log("Error al descargar archivo: " . $e->getMessage());
            $_SESSION['error'] = 'Error al descargar archivo';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    public function saveInfoSheet($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            // Asesor solo puede acceder a sus propias solicitudes
            if ($role === ROLE_ASESOR && intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/solicitudes');
            }

            $entryDate      = trim($_POST['entry_date'] ?? date('Y-m-d'));
            $residencePlace = trim($_POST['residence_place'] ?? '');
            $address        = trim($_POST['address'] ?? '');
            $clientEmail    = trim($_POST['client_email'] ?? '');
            $embassyEmail   = trim($_POST['embassy_email'] ?? '');
            $amountPaid     = !empty($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : null;
            $observations   = trim($_POST['observations'] ?? '');

            // Upsert hoja de información
            $stmt = $this->db->prepare("
                INSERT INTO information_sheets
                    (application_id, entry_date, residence_place, address, client_email, embassy_email, amount_paid, observations, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    entry_date = VALUES(entry_date),
                    residence_place = VALUES(residence_place),
                    address = VALUES(address),
                    client_email = VALUES(client_email),
                    embassy_email = VALUES(embassy_email),
                    amount_paid = VALUES(amount_paid),
                    observations = VALUES(observations)
            ");
            $stmt->execute([
                $id, $entryDate, $residencePlace, $address,
                $clientEmail, $embassyEmail, $amountPaid, $observations,
                $_SESSION['user_id']
            ]);

            logAudit('create', 'solicitudes', "Hoja de información guardada para solicitud #$id");

            $_SESSION['success'] = 'Hoja de información guardada correctamente';
            $this->redirect('/solicitudes/ver/' . $id);

        } catch (PDOException $e) {
            error_log("Error al guardar hoja de información: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar hoja de información';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    public function markClientAttended($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            if ($role === ROLE_ASESOR && intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/solicitudes');
            }

            $attended     = isset($_POST['client_attended']) ? 1 : 0;
            $attendedDate = !empty($_POST['client_attended_date']) ? $_POST['client_attended_date'] : null;

            $this->db->prepare("UPDATE applications SET client_attended = ?, client_attended_date = ? WHERE id = ?")
                ->execute([$attended, $attendedDate, $id]);

            // Advance to STATUS_EN_ESPERA_RESULTADO if attended
            if ($attended && $application['status'] === STATUS_CITA_PROGRAMADA) {
                $prevStatus = $application['status'];
                $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([STATUS_EN_ESPERA_RESULTADO, $id]);
                $this->db->prepare("INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$id, $prevStatus, STATUS_EN_ESPERA_RESULTADO, 'Cliente marcó asistencia a cita', $_SESSION['user_id']]);
            }

            $_SESSION['success'] = 'Asistencia registrada correctamente';
            $this->redirect('/solicitudes/ver/' . $id);

        } catch (PDOException $e) {
            error_log("Error al registrar asistencia: " . $e->getMessage());
            $_SESSION['error'] = 'Error al registrar asistencia';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    public function linkForm($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();
        $formLinkId = intval($_POST['form_link_id'] ?? 0);

        if ($formLinkId <= 0) {
            $_SESSION['error'] = 'Formulario no válido';
            $this->redirect('/solicitudes/ver/' . $id);
        }

        try {
            $stmt = $this->db->prepare("SELECT created_by FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            if ($role === ROLE_ASESOR && intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/solicitudes');
            }

            $this->db->prepare("UPDATE applications SET form_link_id = ?, form_link_status = 'enviado', form_link_sent_at = NOW() WHERE id = ?")
                ->execute([$formLinkId, $id]);

            $_SESSION['success'] = 'Formulario vinculado. Copia el enlace y compártelo con el cliente.';
            $this->redirect('/solicitudes/ver/' . $id . '?copiar_enlace=1');

        } catch (PDOException $e) {
            error_log("Error al vincular formulario: " . $e->getMessage());
            $_SESSION['error'] = 'Error al vincular formulario';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    /**
     * Vista pública para que asesoras confirmen citas del día siguiente.
     */
    public function publicSolicitudes() {
        $this->requireLogin();

        $role = $this->getUserRole();
        $userId = $_SESSION['user_id'];

        try {
            // Solicitudes en estado "Cita programada" con cita MAÑANA
            $tomorrow = date('Y-m-d', strtotime('+1 day'));

            $sql = "
                SELECT a.*, u.full_name as creator_name, f.name as form_name,
                       COALESCE(a.appointment_confirmed_day_before, 0) as appointment_confirmed_day_before
                FROM applications a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN forms f ON a.form_id = f.id
                WHERE a.status = ?
                  AND DATE(a.appointment_date) = ?
            ";
            $params = [STATUS_CITA_PROGRAMADA, $tomorrow];

            if ($role === ROLE_ASESOR) {
                $sql .= " AND a.created_by = ?";
                $params[] = $userId;
            }
            $sql .= " ORDER BY a.appointment_date ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $appointmentApplications = $stmt->fetchAll();

            $this->view('public/solicitudes', [
                'appointmentApplications' => $appointmentApplications,
            ]);

        } catch (PDOException $e) {
            error_log("Error en publicSolicitudes: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar solicitudes';
            $this->view('public/solicitudes', ['appointmentApplications' => []]);
        }
    }

    /**
     * Asesor confirma que la cita sigue vigente un día antes.
     */
    public function confirmAppointment($id) {        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/public/solicitudes');
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("SELECT created_by FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/public/solicitudes');
            }

            if ($role === ROLE_ASESOR && intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/public/solicitudes');
            }

            $this->db->prepare("UPDATE applications SET appointment_confirmed_day_before = 1 WHERE id = ?")
                ->execute([$id]);

            $_SESSION['success'] = 'Cita confirmada correctamente';
            $this->redirect('/public/solicitudes');

        } catch (PDOException $e) {
            error_log("Error al confirmar cita: " . $e->getMessage());
            $_SESSION['error'] = 'Error al confirmar cita';
            $this->redirect('/public/solicitudes');
        }
    }

    /**
     * Eliminar solicitud (solo Admin).
     */
    public function delete($id) {
        $this->requireRole([ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes');
        }

        try {
            // Obtener solicitud y sus documentos para borrar archivos físicos
            $stmt = $this->db->prepare("SELECT id FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            // Borrar archivos físicos de documentos
            $stmt = $this->db->prepare("SELECT file_path FROM documents WHERE application_id = ?");
            $stmt->execute([$id]);
            foreach ($stmt->fetchAll() as $doc) {
                $filePath = ROOT_PATH . '/public' . $doc['file_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            // Borrar registros relacionados (tablas explícitas para evitar inyección)
            $relatedTables = [
                "DELETE FROM documents WHERE application_id = ?",
                "DELETE FROM status_history WHERE application_id = ?",
                "DELETE FROM application_notes WHERE application_id = ?",
                "DELETE FROM financial_costs WHERE application_id = ?",
                "DELETE FROM payments WHERE application_id = ?",
                "DELETE FROM financial_status WHERE application_id = ?",
                "DELETE FROM information_sheets WHERE application_id = ?",
                "DELETE FROM public_form_submissions WHERE application_id = ?",
            ];
            foreach ($relatedTables as $sql) {
                try {
                    $this->db->prepare($sql)->execute([$id]);
                } catch (PDOException $e) {
                    // Tabla puede no existir; continuar
                }
            }

            // Borrar solicitud
            $this->db->prepare("DELETE FROM applications WHERE id = ?")->execute([$id]);

            logAudit('delete', 'solicitudes', "Solicitud #$id eliminada por administrador");

            $_SESSION['success'] = 'Solicitud eliminada correctamente';
            $this->redirect('/solicitudes');

        } catch (PDOException $e) {
            error_log("Error al eliminar solicitud: " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar la solicitud';
            $this->redirect('/solicitudes');
        }
    }

    /**
     * Descargar un documento por su ID (Admin y Gerente).
     */
    public function downloadDocument($docId) {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);

        try {
            $stmt = $this->db->prepare("
                SELECT d.*, a.id as app_id
                FROM documents d
                LEFT JOIN applications a ON d.application_id = a.id
                WHERE d.id = ?
            ");
            $stmt->execute([$docId]);
            $doc = $stmt->fetch();

            if (!$doc) {
                $_SESSION['error'] = 'Documento no encontrado';
                $this->redirect('/solicitudes');
            }

            $filePath = ROOT_PATH . '/public' . $doc['file_path'];
            if (!file_exists($filePath)) {
                $_SESSION['error'] = 'El archivo no existe en el servidor';
                $this->redirect('/solicitudes/ver/' . $doc['app_id']);
            }

            logAudit('download', 'documentos', "Descarga de documento #$docId ({$doc['name']})");

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($doc['name']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;

        } catch (PDOException $e) {
            error_log("Error al descargar documento: " . $e->getMessage());
            $_SESSION['error'] = 'Error al descargar documento';
            $this->redirect('/solicitudes');
        }
    }
}
