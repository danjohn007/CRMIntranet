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
                // REGLA CRÍTICA: Asesor NO puede ver solicitudes finalizadas
                $where[] = "a.status != ?";
                $params[] = STATUS_FINALIZADO;
                $where[] = "a.created_by = ?";
                $params[] = $userId;
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
                STATUS_CREADO,
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
            
            // REGLA CRÍTICA: Verificar que Asesor no pueda ver solicitudes finalizadas
            if ($role === ROLE_ASESOR && $application['status'] === STATUS_FINALIZADO) {
                $_SESSION['error'] = 'No tiene permisos para ver esta solicitud';
                $this->redirect('/solicitudes');
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
            
            $this->view('applications/show', [
                'application' => $application,
                'history' => $history,
                'documents' => $documents,
                'costs' => $costs,
                'payments' => $payments
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
            
            // Actualizar estatus
            $stmt = $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);
            
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
            
            // REGLA: Asesor no puede acceder a solicitudes finalizadas
            if ($role === ROLE_ASESOR && $application['status'] === STATUS_FINALIZADO) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/solicitudes');
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
            
            // Validaciones
            if ($fileSize > MAX_FILE_SIZE) {
                $_SESSION['error'] = 'El archivo excede el tamaño máximo permitido (10MB)';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            if (!in_array($fileType, ALLOWED_EXTENSIONS)) {
                $_SESSION['error'] = 'Tipo de archivo no permitido';
                $this->redirect('/solicitudes/ver/' . $id);
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
            
            // Guardar en base de datos
            $relativePath = '/uploads/applications/' . $id . '/' . $newFileName;
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
            
            $_SESSION['success'] = 'Documento subido correctamente';
            $this->redirect('/solicitudes/ver/' . $id);
            
        } catch (PDOException $e) {
            error_log("Error al subir documento: " . $e->getMessage());
            $_SESSION['error'] = 'Error al subir documento';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }
}
