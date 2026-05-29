<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class ApplicationController extends BaseController {
    private function decodeApplicationDataJson($rawData) {
        if (is_array($rawData)) {
            return $rawData;
        }

        $decoded = json_decode((string) $rawData, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return [];
    }

    private function canAdvisorViewClosedApplication(array $application, $advisorId) {
        if (intval($application['created_by'] ?? 0) !== intval($advisorId)) {
            return false;
        }

        if (($application['status'] ?? '') !== STATUS_TRAMITE_CERRADO) {
            return false;
        }

        $data = $this->decodeApplicationDataJson($application['data_json'] ?? '{}');
        $grants = $data['closed_visibility_grants'] ?? [];
        if (!is_array($grants)) {
            return false;
        }

        $nowTs = time();
        foreach ($grants as $grant) {
            if (!is_array($grant)) {
                continue;
            }

            if (intval($grant['advisor_id'] ?? 0) !== intval($advisorId)) {
                continue;
            }

            $startTs = strtotime((string) ($grant['start_at'] ?? ''));
            $endTs = strtotime((string) ($grant['end_at'] ?? ''));
            if ($startTs === false || $endTs === false) {
                continue;
            }

            if ($nowTs >= $startTs && $nowTs <= $endTs) {
                return true;
            }
        }

        return false;
    }

    private function isAdvisorReadOnlyClosedAccess(array $application, $advisorId) {
        return $this->canAdvisorViewClosedApplication($application, $advisorId);
    }

    private function denyAdvisorReadOnlyClosedMutation($applicationId) {
        $_SESSION['error'] = 'Este trámite cerrado está en modo solo lectura para asesor durante la reactivación temporal';
        $this->redirect('/solicitudes/ver/' . $applicationId);
    }

    public function index() {
        $this->requireLogin();

        $role = $this->getUserRole();
        $userId = $_SESSION['user_id'];
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        // Filtros
        $status = $_GET['status'] ?? '';
        $flow = $_GET['flow'] ?? '';  // 'normal', 'canadiense', or '' (todos)
        $searchTerm = trim((string) ($_GET['q'] ?? ''));

        try {
            $applications = [];
            $total = 0;
            $totalPages = 0;

            if ($role === ROLE_ASESOR) {
                $where = ["a.created_by = ?"];
                $params = [$userId];

                if (!empty($status)) {
                    $where[] = "a.status = ?";
                    $params[] = $status;
                }

                if ($flow === 'canadiense') {
                    $where[] = "a.is_canadian_visa = 1";
                } elseif ($flow === 'normal') {
                    $where[] = "(a.is_canadian_visa = 0 OR a.is_canadian_visa IS NULL)";
                }

                $whereClause = 'WHERE ' . implode(' AND ', $where);

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
                ");
                $stmt->execute($params);
                $allApplications = $stmt->fetchAll();

                $visibleApplications = [];
                foreach ($allApplications as $candidate) {
                    $candidateStatus = $candidate['status'] ?? '';

                    if ($candidateStatus === STATUS_TRAMITE_CERRADO && !$this->canAdvisorViewClosedApplication($candidate, $userId)) {
                        continue;
                    }

                    if ($candidateStatus === STATUS_FINALIZADO) {
                        continue;
                    }

                    $visibleApplications[] = $candidate;
                }

                $total = count($visibleApplications);
                $totalPages = max(1, intval(ceil($total / $limit)));
                $applications = array_slice($visibleApplications, $offset, $limit);
            } else {
                $where = [];
                $params = [];

                if (!empty($status)) {
                    $where[] = "a.status = ?";
                    $params[] = $status;
                }

                if ($flow === 'canadiense') {
                    $where[] = "a.is_canadian_visa = 1";
                } elseif ($flow === 'normal') {
                    $where[] = "(a.is_canadian_visa = 0 OR a.is_canadian_visa IS NULL)";
                }

                if ($role === ROLE_ADMIN && $searchTerm !== '') {
                    $where[] = "(
                        a.client_name LIKE ?
                        OR JSON_UNQUOTE(JSON_EXTRACT(a.data_json, '$.nombre')) LIKE ?
                        OR JSON_UNQUOTE(JSON_EXTRACT(a.data_json, '$.apellidos')) LIKE ?
                        OR CONCAT(
                            COALESCE(JSON_UNQUOTE(JSON_EXTRACT(a.data_json, '$.nombre')), ''),
                            ' ',
                            COALESCE(JSON_UNQUOTE(JSON_EXTRACT(a.data_json, '$.apellidos')), '')
                        ) LIKE ?
                    )";
                    $likeSearch = '%' . $searchTerm . '%';
                    $params[] = $likeSearch;
                    $params[] = $likeSearch;
                    $params[] = $likeSearch;
                    $params[] = $likeSearch;
                }

                $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

                $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM applications a $whereClause");
                $stmt->execute($params);
                $total = $stmt->fetch()['total'];

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

                $totalPages = max(1, intval(ceil($total / $limit)));
            }

            $advisors = [];
            if ($role === ROLE_ADMIN) {
                $stmt = $this->db->prepare("SELECT id, full_name FROM users WHERE role = ? ORDER BY full_name ASC");
                $stmt->execute([ROLE_ASESOR]);
                $advisors = $stmt->fetchAll();
            }

            $this->view('applications/index', [
                'applications' => $applications,
                'page' => $page,
                'totalPages' => $totalPages,
                'total' => $total,
                'status' => $status,
                'flow' => $flow,
                'searchTerm' => $searchTerm,
                'advisors' => $advisors,
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

        $isCanadianVisa = (($_POST['is_canadian_visa'] ?? '0') === '1');
        $formId = intval($_POST['form_id'] ?? 0);
        $formData = $_POST['form_data'] ?? [];

        // Keep basic creation fields; sanitise values
        $basicKeys    = ['nombre', 'apellidos', 'email', 'telefono', 'nombre_cliente', 'pago', 'fecha_cita'];
        $filteredData = [];
        foreach ($basicKeys as $key) {
            $filteredData[$key] = trim($formData[$key] ?? '');
        }

        $isUniqueUsPassportForm = false;
        if (!$isCanadianVisa && $formId > 0) {
            $stmtFormType = $this->db->prepare("
                SELECT name, type, subtype
                FROM forms
                WHERE id = ? AND is_published = 1
            ");
            $stmtFormType->execute([$formId]);
            $selectedForm = $stmtFormType->fetch();
            if ($selectedForm) {
                $normalizeText = function ($value) {
                    $value = (string) $value;
                    $value = strtr($value, [
                        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
                        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
                    ]);
                    return strtolower(trim($value));
                };

                $formNameNormalized = $normalizeText($selectedForm['name'] ?? '');
                $formTypeNormalized = $normalizeText($selectedForm['type'] ?? '');
                $formSubtypeNormalized = $normalizeText($selectedForm['subtype'] ?? '');

                $isAmericanPassportForm =
                    strpos($formNameNormalized, 'pasaporte americano') !== false ||
                    strpos($formSubtypeNormalized, 'americano') !== false;

                $isFirstTimePassportSubtype =
                    (strpos($formSubtypeNormalized, 'unica') !== false && strpos($formSubtypeNormalized, 'vez') !== false) ||
                    (strpos($formSubtypeNormalized, 'primera') !== false && strpos($formSubtypeNormalized, 'vez') !== false);

                $isUniqueUsPassportForm =
                    $formTypeNormalized === 'pasaporte' &&
                    $isAmericanPassportForm &&
                    $isFirstTimePassportSubtype;
            }
        }

        if ($isUniqueUsPassportForm) {
            if (empty($filteredData['nombre_cliente'])) {
                $_SESSION['error'] = 'El nombre del cliente es obligatorio';
                $this->redirect('/solicitudes/crear');
            }
            if (empty($filteredData['pago'])) {
                $_SESSION['error'] = 'El pago es obligatorio';
                $this->redirect('/solicitudes/crear');
            }
            if (empty($filteredData['fecha_cita'])) {
                $_SESSION['error'] = 'La fecha de la cita es obligatoria';
                $this->redirect('/solicitudes/crear');
            }
            $clientName = trim($filteredData['nombre_cliente']);
        } else {
            if (empty($filteredData['nombre'])) {
                $_SESSION['error'] = 'El nombre del solicitante es obligatorio';
                $this->redirect('/solicitudes/crear');
            }
            $clientName = trim($filteredData['nombre'] . ' ' . $filteredData['apellidos']);
        }

        // ── Canadian Visa flow ────────────────────────────────────
        if ($isCanadianVisa) {
            $canadianTipo      = trim($_POST['canadian_tipo'] ?? '');
            $canadianModalidad = trim($_POST['canadian_modalidad'] ?? '');

            if (empty($canadianTipo) || empty($canadianModalidad)) {
                $_SESSION['error'] = 'Debe seleccionar el Tipo y la Modalidad para Visa Canadiense';
                $this->redirect('/solicitudes/crear');
            }

            if ($formId <= 0) {
                $_SESSION['error'] = 'Debe seleccionar el formulario de cliente para Visa Canadiense';
                $this->redirect('/solicitudes/crear');
            }

            // Obtener versión del formulario seleccionado
            $stmtForm = $this->db->prepare("SELECT id, version FROM forms WHERE id = ? AND is_published = 1");
            $stmtForm->execute([$formId]);
            $form = $stmtForm->fetch();

            if (!$form) {
                $_SESSION['error'] = 'El formulario seleccionado no es válido';
                $this->redirect('/solicitudes/crear');
            }

            $formVersion = intval($form['version'] ?? 1);

            try {
                $year = date('Y');
                $stmt = $this->db->prepare("
                    SELECT MAX(CAST(SUBSTRING(folio, -6) AS UNSIGNED)) as last_number
                    FROM applications WHERE folio LIKE ? OR folio LIKE ?
                ");
                $stmt->execute(["FOLIO-$year-%", "VISA-$year-%"]);
                $result     = $stmt->fetch();
                $nextNumber = ($result['last_number'] ?? 0) + 1;
                $folio      = sprintf("FOLIO-%s-%06d", $year, $nextNumber);

                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO applications
                            (folio, form_id, form_version, type, subtype,
                             is_canadian_visa, canadian_tipo, canadian_modalidad,
                             data_json, client_name, created_by)
                        VALUES (?, ?, ?, 'Visa', ?, 1, ?, ?, ?, ?, ?)
                    ");
                    // subtype = canadian_modalidad for backward-compat with $isRenovacion check
                    $stmt->execute([
                        $folio,
                        $formId,             // form_id
                        $formVersion,        // form_version
                        $canadianModalidad,  // subtype (backward-compat)
                        $canadianTipo,       // canadian_tipo
                        $canadianModalidad,  // canadian_modalidad
                        json_encode($filteredData, JSON_UNESCAPED_UNICODE),
                        $clientName,
                        $_SESSION['user_id']
                    ]);
                } catch (PDOException $e) {
                    // Fallback if new columns don't exist yet
                    $stmt = $this->db->prepare("
                        INSERT INTO applications
                            (folio, form_id, form_version, type, subtype, data_json, created_by)
                        VALUES (?, ?, ?, 'Visa', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $folio,
                        $formId,
                        $formVersion,
                        $canadianModalidad,
                        json_encode($filteredData, JSON_UNESCAPED_UNICODE),
                        $_SESSION['user_id']
                    ]);
                }

                $applicationId = $this->db->lastInsertId();

                $this->db->prepare("
                    INSERT INTO status_history (application_id, new_status, comment, changed_by)
                    VALUES (?, ?, ?, ?)
                ")->execute([$applicationId, STATUS_NUEVO, 'Solicitud Visa Canadiense creada', $_SESSION['user_id']]);

                $this->db->prepare("
                    INSERT INTO financial_status (application_id, total_costs, total_paid, balance, status)
                    VALUES (?, 0, 0, 0, ?)
                ")->execute([$applicationId, FINANCIAL_PENDIENTE]);

                $_SESSION['success'] = "Solicitud Visa Canadiense creada: $folio";
                $this->redirect('/solicitudes/ver/' . $applicationId);

            } catch (PDOException $e) {
                error_log("Error al crear solicitud canadiense: " . $e->getMessage());
                $_SESSION['error'] = 'Error al crear solicitud';
                $this->redirect('/solicitudes/crear');
            }
            return;
        }

        // ── Standard flow ─────────────────────────────────────────
        if ($formId <= 0) {
            $_SESSION['error'] = 'Debe seleccionar un tipo de trámite';
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
                WHERE folio LIKE ? OR folio LIKE ?
            ");
            $stmt->execute(["FOLIO-$year-%", "VISA-$year-%"]);
            $result     = $stmt->fetch();
            $nextNumber = ($result['last_number'] ?? 0) + 1;
            $folio      = sprintf("FOLIO-%s-%06d", $year, $nextNumber);

            // Crear solicitud con datos básicos
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO applications (folio, form_id, form_version, type, subtype, data_json, client_name, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $folio,
                    $formId,
                    $form['version'],
                    $form['type'],
                    $form['subtype'],
                    json_encode($filteredData, JSON_UNESCAPED_UNICODE),
                    $clientName,
                    $_SESSION['user_id']
                ]);
            } catch (PDOException $e) {
                // Fallback if client_name column doesn't exist yet
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
                    json_encode($filteredData, JSON_UNESCAPED_UNICODE),
                    $_SESSION['user_id']
                ]);
            }

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
        $advisorTemporaryClosedAccess = false;
        
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
            
            // REGLA CRÍTICA: Asesor solo puede ver SUS PROPIAS solicitudes.
            // Las cerradas solo se visualizan en modo lectura cuando tienen reactivación temporal activa.
            if ($role === ROLE_ASESOR) {
                if (intval($application['created_by']) !== intval($userId)) {
                    $_SESSION['error'] = 'No tiene permisos para ver esta solicitud';
                    $this->redirect('/solicitudes');
                }

                if ($application['status'] === STATUS_TRAMITE_CERRADO) {
                    if (!$this->canAdvisorViewClosedApplication($application, $userId)) {
                        $_SESSION['error'] = 'No tiene permisos para ver esta solicitud';
                        $this->redirect('/solicitudes');
                    }
                    $advisorTemporaryClosedAccess = true;
                }

                if ($application['status'] === STATUS_FINALIZADO) {
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
                ORDER BY sh.created_at DESC
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
            $familiarMembers = [];
            try {
                $stmt = $this->db->prepare("SELECT * FROM information_sheets WHERE application_id = ?");
                $stmt->execute([$id]);
                $infoSheet = $stmt->fetch() ?: null;

                if ($infoSheet) {
                    try {
                        $stmtFam = $this->db->prepare("SELECT * FROM information_sheet_familiar WHERE information_sheet_id = ? ORDER BY id");
                        $stmtFam->execute([$infoSheet['id']]);
                        $familiarMembers = $stmtFam->fetchAll();
                    } catch (PDOException $e) {
                        // Table may not exist yet
                    }
                }
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
            $linkedFormId = null;
            if (!empty($application['form_link_id'])) {
                $candidateFormId = intval($application['form_link_id']);
                if ($candidateFormId > 0) {
                    $linkedFormId = $candidateFormId;
                }
            } elseif (!empty($application['form_id'])) {
                $candidateFormId = intval($application['form_id']);
                if ($candidateFormId > 0) {
                    $linkedFormId = $candidateFormId;
                }
            }
            if ($linkedFormId !== null) {
                try {
                    $stmt = $this->db->prepare("SELECT public_token FROM forms WHERE id = ?");
                    $stmt->execute([$linkedFormId]);
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
                'familiarMembers' => $familiarMembers,
                'publishedForms' => $publishedForms,
                'formLinkToken' => $formLinkToken,
                'advisorTemporaryClosedAccess' => $advisorTemporaryClosedAccess,
            ]);
            
        } catch (PDOException $e) {
            error_log("Error al ver solicitud: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar solicitud';
            $this->redirect('/solicitudes');
        }
    }
    
    public function changeStatus($id) {
        $this->requireLogin();

        $role      = $this->getUserRole();
        $newStatus = $_POST['status'] ?? '';

        if (!in_array($role, [ROLE_ADMIN, ROLE_GERENTE, ROLE_ASESOR])) {
            http_response_code(403);
            die("Acceso denegado. No tiene permisos para acceder a esta sección.");
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

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
            // Obtener solicitud completa para validaciones
            $stmt = $this->db->prepare("
                SELECT a.*, a.subtype
                FROM applications a WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            $previousStatus = $application['status'];

            // Asesor: can only modify their own requests
            if ($role === ROLE_ASESOR) {
                if (intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                    $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                    $this->redirect('/solicitudes');
                }

                if ($application['status'] === STATUS_FINALIZADO) {
                    $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                    $this->redirect('/solicitudes');
                }

                if ($application['status'] === STATUS_TRAMITE_CERRADO) {
                    if ($this->isAdvisorReadOnlyClosedAccess($application, $_SESSION['user_id'])) {
                        $this->denyAdvisorReadOnlyClosedMutation($id);
                    }
                    $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                    $this->redirect('/solicitudes');
                }
            }

            // ── Detect Canadian Visa flag ───────────────────────────────────
            $isCanadianVisa = !empty($application['is_canadian_visa']);
            $isPassportService = stripos(trim((string) ($application['type'] ?? '')), 'pasaporte') !== false;

            // For passport service, these statuses are not used.
            if (!$isCanadianVisa && $isPassportService && in_array($newStatus, [STATUS_EN_ESPERA_PAGO, STATUS_EN_ESPERA_RESULTADO], true)) {
                $_SESSION['error'] = 'Para trámites de Pasaporte no se usan los estatus En espera de pago consular ni En espera de resultado.';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            // ── Validaciones antes de pasar de NUEVO → ROJO ────────────────────
            if ($previousStatus === STATUS_NUEVO && $newStatus === STATUS_LISTO_SOLICITUD) {
                // 1. Pasaporte subido
                $stmtDoc = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'pasaporte_vigente'");
                $stmtDoc->execute([$id]);
                if (!$stmtDoc->fetch()) {
                    $_SESSION['error'] = 'No se puede cambiar a este estado: no se ha cargado el pasaporte vigente.';
                    $this->redirect('/solicitudes/ver/' . $id);
                }

                if ($isCanadianVisa) {
                    // Visa canadiense anterior (si Renovación)
                    $isRenovacion = stripos($application['canadian_modalidad'] ?? '', 'renov') !== false;
                    if ($isRenovacion) {
                        $stmtVisa = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'visa_canadiense_anterior'");
                        $stmtVisa->execute([$id]);
                        if (!$stmtVisa->fetch()) {
                            $_SESSION['error'] = 'No se puede cambiar a este estado: para renovación se requiere la visa canadiense anterior.';
                            $this->redirect('/solicitudes/ver/' . $id);
                        }
                    }
                    // ETA anterior (si ETA Canadiense + Renovación)
                    $isETA = stripos($application['canadian_tipo'] ?? '', 'ETA') !== false;
                    if ($isETA && $isRenovacion) {
                        $stmtEta = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'eta_anterior'");
                        $stmtEta->execute([$id]);
                        if (!$stmtEta->fetch()) {
                            $_SESSION['error'] = 'No se puede cambiar a este estado: se requiere el ETA anterior.';
                            $this->redirect('/solicitudes/ver/' . $id);
                        }
                    }
                } else {
                    // 2. Si es renovación (estándar), visa anterior subida
                    $isRenovacion = stripos($application['subtype'] ?? '', 'renov') !== false;
                    if ($isRenovacion) {
                        $stmtVisa = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'visa_anterior'");
                        $stmtVisa->execute([$id]);
                        if (!$stmtVisa->fetch()) {
                            $_SESSION['error'] = 'No se puede cambiar a este estado: para renovación se requiere cargar la visa anterior.';
                            $this->redirect('/solicitudes/ver/' . $id);
                        }
                    }
                }
            }

            // ── Validaciones antes de pasar de ROJO → AMARILLO ─────────────────
            if ($previousStatus === STATUS_LISTO_SOLICITUD && $newStatus === STATUS_EN_ESPERA_PAGO) {
                if ($isCanadianVisa) {
                    // Para Canadian visa: verificar que los documentos estén cargados en portal
                    $docsUploaded = isset($_POST['canadian_docs_uploaded_portal']) ? 1 : intval($application['canadian_docs_uploaded_portal'] ?? 0);
                    if (!$docsUploaded) {
                        $_SESSION['error'] = 'No se puede avanzar: marque "Documentos cargados en portal Canadá" primero.';
                        $this->redirect('/solicitudes/ver/' . $id);
                    }
                } else {
                    // 1. Formulario del cliente completado
                    if ($application['form_link_status'] !== 'completado') {
                        $_SESSION['error'] = 'No se puede cambiar a este estado: el cliente aún no ha completado el cuestionario.';
                        $this->redirect('/solicitudes/ver/' . $id);
                    }

                    // 2. Pasaporte subido
                    $stmtDoc = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'pasaporte_vigente'");
                    $stmtDoc->execute([$id]);
                    if (!$stmtDoc->fetch()) {
                        $_SESSION['error'] = 'No se puede cambiar a este estado: no se ha cargado el pasaporte vigente.';
                        $this->redirect('/solicitudes/ver/' . $id);
                    }

                    // 3. Si es renovación, visa anterior subida
                    $isRenovacion = stripos($application['subtype'] ?? '', 'renov') !== false;
                    if ($isRenovacion) {
                        $stmtVisa = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'visa_anterior'");
                        $stmtVisa->execute([$id]);
                        if (!$stmtVisa->fetch()) {
                            $_SESSION['error'] = 'No se puede cambiar a este estado: para renovación se requiere cargar la visa anterior.';
                            $this->redirect('/solicitudes/ver/' . $id);
                        }
                    }
                }
            }

            // ── Campos adicionales por estado ───────────────────────────────────
            $extraSql    = '';
            $extraParams = [];

            // Email notification tracking: set when an appointment date is being saved
            $notifyAppointmentType = null;
            $notifyAppointmentDate = null;

            if ($isCanadianVisa) {
                // ── Canadian visa extra fields ──────────────────────────────
                if ($newStatus === STATUS_LISTO_SOLICITUD || ($previousStatus === STATUS_LISTO_SOLICITUD && $newStatus === STATUS_EN_ESPERA_PAGO)) {
                    $docsUploaded    = isset($_POST['canadian_docs_uploaded_portal']) ? 1 : 0;
                    $applicationNum  = trim($_POST['canadian_application_number'] ?? '');
                    $extraSql    = ', canadian_docs_uploaded_portal = ?, canadian_application_number = ?';
                    $extraParams = [$docsUploaded, $applicationNum ?: null];
                } elseif ($newStatus === STATUS_EN_ESPERA_PAGO) {
                    // AMARILLO canadiense: biometric appointment fields
                    $biometricGenerated = isset($_POST['canadian_biometric_appointment_generated']) ? 1 : 0;
                    $biometricDate      = !empty($_POST['canadian_biometric_date']) ? $_POST['canadian_biometric_date'] : null;
                    $biometricLocation  = trim($_POST['canadian_biometric_location'] ?? '');
                    $extraSql    = ', canadian_biometric_appointment_generated = ?, canadian_biometric_date = ?, canadian_biometric_location = ?';
                    $extraParams = [$biometricGenerated, $biometricDate, $biometricLocation ?: null];
                    // Trigger email if biometric date is new or changed
                    if (!empty($biometricDate) && $biometricDate !== ($application['canadian_biometric_date'] ?? null)) {
                        $notifyAppointmentType = 'biometric';
                        $notifyAppointmentDate = $biometricDate;
                    }
                } elseif ($newStatus === STATUS_CITA_PROGRAMADA) {
                    // AZUL canadiense: biometrics attendance
                    $attended     = isset($_POST['canadian_client_attended_biometrics']) ? 1 : 0;
                    $attendedDate = !empty($_POST['canadian_biometric_attended_date']) ? $_POST['canadian_biometric_attended_date'] : null;
                    $extraSql    = ', canadian_client_attended_biometrics = ?, canadian_biometric_attended_date = ?';
                    $extraParams = [$attended, $attendedDate];
                } elseif ($newStatus === STATUS_EN_ESPERA_RESULTADO) {
                    // AZUL → MORADO canadiense: biometrics attendance
                    $attended     = isset($_POST['canadian_client_attended_biometrics']) ? 1 : 0;
                    $attendedDate = !empty($_POST['canadian_biometric_attended_date']) ? $_POST['canadian_biometric_attended_date'] : null;
                    $extraSql    = ', canadian_client_attended_biometrics = ?, canadian_biometric_attended_date = ?';
                    $extraParams = [$attended, $attendedDate];
                } elseif ($newStatus === STATUS_TRAMITE_CERRADO) {
                    // MORADO → VERDE canadiense: visa result
                    $visaResult         = trim($_POST['canadian_visa_result'] ?? '');
                    $resolutionDate     = !empty($_POST['canadian_resolution_date']) ? $_POST['canadian_resolution_date'] : null;
                    $guideNumber        = trim($_POST['canadian_guide_number'] ?? '');
                    $finalObservations  = trim($_POST['canadian_final_observations'] ?? '');
                    $extraSql    = ', canadian_visa_result = ?, canadian_resolution_date = ?, canadian_guide_number = ?, canadian_final_observations = ?';
                    $extraParams = [$visaResult ?: null, $resolutionDate, $guideNumber ?: null, $finalObservations ?: null];
                }
            } else {
                // ── Standard flow extra fields ──────────────────────────────
                if ($previousStatus === STATUS_LISTO_SOLICITUD && $newStatus === STATUS_EN_ESPERA_PAGO) {
                    // Checkboxes vienen del estado ROJO (ya deben estar marcados)
                    $officialDone = isset($_POST['official_application_done']) ? 1 : 0;
                    $feeSent      = isset($_POST['consular_fee_sent']) ? 1 : 0;
                    $ds160Num     = trim($_POST['ds160_confirmation_number'] ?? '');
                    $extraSql    = ', official_application_done = ?, consular_fee_sent = ?, ds160_confirmation_number = ?';
                    $extraParams = [$officialDone, $feeSent, $ds160Num ?: null];
                } elseif ($newStatus === STATUS_LISTO_SOLICITUD) {
                    // Saving checkboxes while still in ROJO (no status transition)
                    $officialDone = isset($_POST['official_application_done']) ? 1 : 0;
                    $feeSent      = isset($_POST['consular_fee_sent']) ? 1 : 0;
                    $ds160Num     = trim($_POST['ds160_confirmation_number'] ?? '');
                    $extraSql    = ', official_application_done = ?, consular_fee_sent = ?, ds160_confirmation_number = ?';
                    $extraParams = [$officialDone, $feeSent, $ds160Num ?: null];
                } elseif ($newStatus === STATUS_EN_ESPERA_PAGO) {
                    // Updating AMARILLO fields
                    $consularPaymentConfirmed = isset($_POST['consular_payment_confirmed']) ? 1 : 0;
                    $appointmentDate = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : null;
                    if ($appointmentDate !== null) {
                        $extraSql    = ', consular_payment_confirmed = ?, appointment_date = ?';
                        $extraParams = [$consularPaymentConfirmed, $appointmentDate];
                        // Trigger email if appointment date is new or changed
                        if ($appointmentDate !== ($application['appointment_date'] ?? null)) {
                            $notifyAppointmentType = 'consular';
                            $notifyAppointmentDate = $appointmentDate;
                        }
                    } else {
                        $extraSql    = ', consular_payment_confirmed = ?';
                        $extraParams = [$consularPaymentConfirmed];
                    }
                } elseif ($newStatus === STATUS_TRAMITE_CERRADO) {
                    $dhlTracking  = trim($_POST['dhl_tracking'] ?? '');
                    $deliveryDate = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
                    $extraSql    = ', dhl_tracking = ?, delivery_date = ?';
                    $extraParams = [$dhlTracking ?: null, $deliveryDate];
                }
            }

            $stmt = $this->db->prepare("UPDATE applications SET status = ? $extraSql WHERE id = ?");
            try {
                $stmt->execute(array_merge([$newStatus], $extraParams, [$id]));
            } catch (PDOException $colErr) {
                // New columns may not exist yet; fall back to status-only update
                error_log("changeStatus column fallback: " . $colErr->getMessage());
                $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
            }

            // Handle DS-160 file upload (ROJO state, standard flow)
            if (!$isCanadianVisa && $newStatus === STATUS_LISTO_SOLICITUD && isset($_FILES['ds160_file']) && $_FILES['ds160_file']['error'] === UPLOAD_ERR_OK) {
                $this->saveApplicationFile($id, $_FILES['ds160_file'], 'ds160');
            }

            // Handle Canadian portal capture (ROJO state, Canadian flow)
            if ($isCanadianVisa && in_array($newStatus, [STATUS_LISTO_SOLICITUD, STATUS_EN_ESPERA_PAGO])
                && isset($_FILES['canadian_portal_capture']) && $_FILES['canadian_portal_capture']['error'] === UPLOAD_ERR_OK) {
                $this->saveApplicationFile($id, $_FILES['canadian_portal_capture'], 'canadian_portal_capture');
            }

            // Handle VAC confirmation (AMARILLO state, Canadian flow)
            if ($isCanadianVisa && in_array($newStatus, [STATUS_EN_ESPERA_PAGO, STATUS_CITA_PROGRAMADA])
                && isset($_FILES['canadian_vac_confirmation']) && $_FILES['canadian_vac_confirmation']['error'] === UPLOAD_ERR_OK) {
                $this->saveApplicationFile($id, $_FILES['canadian_vac_confirmation'], 'canadian_vac_confirmation');
            }

            // Handle consular payment evidence (AMARILLO state, standard flow)
            if (!$isCanadianVisa && $newStatus === STATUS_EN_ESPERA_PAGO && isset($_FILES['consular_payment_file']) && $_FILES['consular_payment_file']['error'] === UPLOAD_ERR_OK) {
                $this->saveApplicationFile($id, $_FILES['consular_payment_file'], 'consular_payment_evidence');
            }

            // Handle appointment confirmation and official application (AMARILLO → AZUL, standard flow)
            if (!$isCanadianVisa && ($newStatus === STATUS_CITA_PROGRAMADA || $newStatus === STATUS_EN_ESPERA_PAGO)) {
                if (isset($_FILES['appointment_confirmation_doc']) && $_FILES['appointment_confirmation_doc']['error'] === UPLOAD_ERR_OK) {
                    $this->saveApplicationFile($id, $_FILES['appointment_confirmation_doc'], 'appointment_confirmation');
                }
                if (isset($_FILES['official_application_final']) && $_FILES['official_application_final']['error'] === UPLOAD_ERR_OK) {
                    $this->saveApplicationFile($id, $_FILES['official_application_final'], 'official_application_final');
                }
            }

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

            // Send appointment notification email when a new/changed appointment date is saved
            if ($notifyAppointmentType !== null && $notifyAppointmentDate !== null) {
                try {
                    sendAppointmentNotificationEmail($id, $notifyAppointmentType, $notifyAppointmentDate, false, $this->db);
                } catch (\Exception $e) {
                    error_log("Error sending appointment notification email for application #$id: " . $e->getMessage());
                }
            }
            
            $_SESSION['success'] = 'Estatus actualizado correctamente';
            // Asesor cannot view a closed trámite, redirect them to the list
            if ($role === ROLE_ASESOR && $newStatus === STATUS_TRAMITE_CERRADO) {
                $this->redirect('/solicitudes');
            }
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
                if ($application['status'] === STATUS_FINALIZADO) {
                    $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                    $this->redirect('/solicitudes');
                }
                if (intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                    $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                    $this->redirect('/solicitudes');
                }
                if ($application['status'] === STATUS_TRAMITE_CERRADO) {
                    if ($this->isAdvisorReadOnlyClosedAccess($application, $_SESSION['user_id'])) {
                        $this->denyAdvisorReadOnlyClosedMutation($id);
                    }
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
            $allowedDocTypes = [
                'pasaporte_vigente', 'visa_anterior', 'ficha_pago_consular',
                'consular_payment_evidence', 'adicional',
                // Canadian visa doc types
                'visa_canadiense_anterior', 'eta_anterior',
                'canadian_vac_confirmation', 'canadian_portal_capture',
                // Payment receipts
                'comprobante_pago',
            ];
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

            if (in_array($docType, ['comprobante_pago', 'consular_payment_evidence'], true)) {
                $paymentAllowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
                if (!in_array($fileType, $paymentAllowedExtensions, true)) {
                    $_SESSION['error'] = 'Para comprobantes de pago solo se permiten archivos PDF, JPG o PNG';
                    $this->redirect('/solicitudes/ver/' . $id);
                }
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
            
            // Auto-advance to ROJO when a base doc is uploaded and all conditions are met
            $canadianBaseDocTypes = ['pasaporte_vigente', 'visa_canadiense_anterior', 'eta_anterior'];
            $standardBaseDocTypes = ['pasaporte_vigente', 'visa_anterior'];
            if (in_array($docType, array_merge($standardBaseDocTypes, $canadianBaseDocTypes))) {
                $stmtApp2 = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
                $stmtApp2->execute([$id]);
                $currentApp2 = $stmtApp2->fetch();
                if ($currentApp2 && $currentApp2['status'] === STATUS_NUEVO) {
                    $isCanadianVisa2 = !empty($currentApp2['is_canadian_visa']);

                    if ($isCanadianVisa2) {
                        // Canadian visa auto-advance conditions
                        $stmtSheet2 = $this->db->prepare("SELECT id FROM information_sheets WHERE application_id = ?");
                        $stmtSheet2->execute([$id]);
                        $hasInfoSheet2 = $stmtSheet2->fetch();

                        // form_link_status: required 'completado' if a form is assigned; optional only if no form assigned at all
                        $formOk = ($currentApp2['form_link_status'] === 'completado' || (empty($currentApp2['form_link_id']) && empty($currentApp2['form_id'])));

                        if ($hasInfoSheet2 && $formOk) {
                            $stmtDoc2 = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'pasaporte_vigente'");
                            $stmtDoc2->execute([$id]);
                            $hasPasaporte2 = (bool) $stmtDoc2->fetch();

                            $isRenovacion2 = stripos($currentApp2['canadian_modalidad'] ?? '', 'renov') !== false;
                            $isETA2        = stripos($currentApp2['canadian_tipo'] ?? '', 'ETA') !== false;

                            $hasVisaCanadiensPrev2 = true;
                            if ($isRenovacion2) {
                                $stmtVC = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'visa_canadiense_anterior'");
                                $stmtVC->execute([$id]);
                                $hasVisaCanadiensPrev2 = (bool) $stmtVC->fetch();
                            }

                            $hasEtaAnterior2 = true;
                            if ($isETA2 && $isRenovacion2) {
                                $stmtEta2 = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'eta_anterior'");
                                $stmtEta2->execute([$id]);
                                $hasEtaAnterior2 = (bool) $stmtEta2->fetch();
                            }

                            if ($hasPasaporte2 && $hasVisaCanadiensPrev2 && $hasEtaAnterior2) {
                                $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([STATUS_LISTO_SOLICITUD, $id]);
                                $this->db->prepare("
                                    INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by)
                                    VALUES (?, ?, ?, ?, ?)
                                ")->execute([$id, STATUS_NUEVO, STATUS_LISTO_SOLICITUD, 'Cambio automático: documentos base e hoja de información completos (Visa Canadiense)', $_SESSION['user_id']]);
                            }
                        }
                    } else {
                        // Standard flow auto-advance
                        if ($currentApp2['form_link_status'] === 'completado') {
                            $stmtSheet2 = $this->db->prepare("SELECT id FROM information_sheets WHERE application_id = ?");
                            $stmtSheet2->execute([$id]);
                            $hasInfoSheet2 = $stmtSheet2->fetch();
                            if ($hasInfoSheet2) {
                                $stmtDoc2 = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'pasaporte_vigente'");
                                $stmtDoc2->execute([$id]);
                                $hasPasaporte2 = (bool) $stmtDoc2->fetch();

                                $isRenovacion2 = stripos($currentApp2['subtype'] ?? '', 'renov') !== false;
                                $hasVisaAnterior2 = true;
                                if ($isRenovacion2) {
                                    $stmtVisa2 = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'visa_anterior'");
                                    $stmtVisa2->execute([$id]);
                                    $hasVisaAnterior2 = (bool) $stmtVisa2->fetch();
                                }

                                if ($hasPasaporte2 && $hasVisaAnterior2) {
                                    $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([STATUS_VALIDANDO_RESPUESTAS, $id]);
                                    $this->db->prepare("
                                        INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by)
                                        VALUES (?, ?, ?, ?, ?)
                                    ")->execute([$id, STATUS_NUEVO, STATUS_VALIDANDO_RESPUESTAS, 'Cambio automático: documentos base, cuestionario y hoja de información completos', $_SESSION['user_id']]);
                                }
                            }
                        }
                    }
                }
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

            if ($role === ROLE_ASESOR && $this->isAdvisorReadOnlyClosedAccess($application, $_SESSION['user_id'])) {
                $this->denyAdvisorReadOnlyClosedMutation($id);
            }

            // Asesor solo puede crear la hoja de información, no editarla
            if ($role === ROLE_ASESOR) {
                $stmtExisting = $this->db->prepare("SELECT id FROM information_sheets WHERE application_id = ?");
                $stmtExisting->execute([$id]);
                if ($stmtExisting->fetch()) {
                    $_SESSION['error'] = 'Solo gerente o administrador puede editar la hoja de información';
                    $this->redirect('/solicitudes/ver/' . $id);
                }
            }

            $entryDate      = trim($_POST['entry_date'] ?? date('Y-m-d'));
            $residencePlace = trim($_POST['residence_place'] ?? '');
            $address        = trim($_POST['address'] ?? '');
            $clientEmail    = trim($_POST['client_email'] ?? '');
            $embassyEmail   = trim($_POST['embassy_email'] ?? '');
            $amountPaid     = !empty($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : null;
            $dhl            = trim($_POST['dhl'] ?? '');
            $observations   = trim($_POST['observations'] ?? '');

            // Upsert hoja de información
            $stmt = $this->db->prepare("
                INSERT INTO information_sheets
                    (application_id, entry_date, residence_place, address, client_email, embassy_email, amount_paid, dhl, observations, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    entry_date = VALUES(entry_date),
                    residence_place = VALUES(residence_place),
                    address = VALUES(address),
                    client_email = VALUES(client_email),
                    embassy_email = VALUES(embassy_email),
                    amount_paid = VALUES(amount_paid),
                    dhl = VALUES(dhl),
                    observations = VALUES(observations)
            ");
            $stmt->execute([
                $id, $entryDate, $residencePlace, $address,
                $clientEmail, $embassyEmail, $amountPaid, $dhl ?: null, $observations,
                $_SESSION['user_id']
            ]);

            logAudit('create', 'solicitudes', "Hoja de información guardada para solicitud #$id");

            // Sync financial_status: set total_costs and total_paid to the honorarios amount
            if ($amountPaid !== null) {
                $this->db->prepare("
                    UPDATE financial_status
                    SET total_costs = ?, total_paid = ?, balance = 0, status = ?
                    WHERE application_id = ?
                ")->execute([$amountPaid, $amountPaid, FINANCIAL_PAGADO, $id]);

                $autoPaymentReference = 'INFO-SHEET-' . $id;
                $autoPaymentDate = $entryDate !== '' ? $entryDate : date('Y-m-d');

                $stmtAutoPayment = $this->db->prepare("
                    SELECT id
                    FROM payments
                    WHERE application_id = ?
                      AND reference = ?
                    LIMIT 1
                ");
                $stmtAutoPayment->execute([$id, $autoPaymentReference]);
                $existingAutoPayment = $stmtAutoPayment->fetch();

                if ($existingAutoPayment) {
                    $this->db->prepare("
                        UPDATE payments
                        SET amount = ?,
                            payment_method = ?,
                            notes = ?,
                            registered_by = ?,
                            payment_date = ?
                        WHERE id = ?
                    ")->execute([
                        $amountPaid,
                        'Sistema',
                        'Pago sincronizado desde hoja de información',
                        $_SESSION['user_id'],
                        $autoPaymentDate,
                        $existingAutoPayment['id']
                    ]);
                } else {
                    $stmtAnyPayment = $this->db->prepare("
                        SELECT 1
                        FROM payments
                        WHERE application_id = ?
                        LIMIT 1
                    ");
                    $stmtAnyPayment->execute([$id]);
                    $hasAnyPayment = (bool) $stmtAnyPayment->fetch();

                    if (!$hasAnyPayment) {
                        $this->db->prepare("
                            INSERT INTO payments (application_id, amount, payment_method, reference, notes, registered_by, payment_date)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ")->execute([
                            $id,
                            $amountPaid,
                            'Sistema',
                            $autoPaymentReference,
                            'Pago sincronizado desde hoja de información',
                            $_SESSION['user_id'],
                            $autoPaymentDate
                        ]);
                    }
                }
            }

            // Auto-advance to ROJO if info sheet saved and base documents are present
            $stmtApp = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
            $stmtApp->execute([$id]);
            $currentApp = $stmtApp->fetch();
            if ($currentApp && $currentApp['status'] === STATUS_NUEVO) {
                $isCanadianVisa = !empty($currentApp['is_canadian_visa']);

                if ($isCanadianVisa) {
                    // form_link_status: required 'completado' if a form is assigned; optional only if no form assigned at all
                    $formOk = ($currentApp['form_link_status'] === 'completado' || (empty($currentApp['form_link_id']) && empty($currentApp['form_id'])));
                    if ($formOk) {
                        $stmtDoc = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'pasaporte_vigente'");
                        $stmtDoc->execute([$id]);
                        $hasPasaporte = (bool) $stmtDoc->fetch();

                        $isRenovacion = stripos($currentApp['canadian_modalidad'] ?? '', 'renov') !== false;
                        $isETA        = stripos($currentApp['canadian_tipo'] ?? '', 'ETA') !== false;

                        $hasVisaCanadiensPrev = true;
                        if ($isRenovacion) {
                            $stmtVC = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'visa_canadiense_anterior'");
                            $stmtVC->execute([$id]);
                            $hasVisaCanadiensPrev = (bool) $stmtVC->fetch();
                        }

                        $hasEtaAnterior = true;
                        if ($isETA && $isRenovacion) {
                            $stmtEta = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'eta_anterior'");
                            $stmtEta->execute([$id]);
                            $hasEtaAnterior = (bool) $stmtEta->fetch();
                        }

                        if ($hasPasaporte && $hasVisaCanadiensPrev && $hasEtaAnterior) {
                            $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([STATUS_LISTO_SOLICITUD, $id]);
                            $this->db->prepare("
                                INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by)
                                VALUES (?, ?, ?, ?, ?)
                            ")->execute([$id, STATUS_NUEVO, STATUS_LISTO_SOLICITUD, 'Cambio automático: hoja de información guardada y documentos base completos (Visa Canadiense)', $_SESSION['user_id']]);
                        }
                    }
                } elseif ($currentApp['form_link_status'] === 'completado') {
                    // Standard flow auto-advance
                    $stmtDoc = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'pasaporte_vigente'");
                    $stmtDoc->execute([$id]);
                    $hasPasaporte = (bool) $stmtDoc->fetch();

                    $isRenovacion = stripos($currentApp['subtype'] ?? '', 'renov') !== false;
                    $hasVisaAnterior = true;
                    if ($isRenovacion) {
                        $stmtVisa = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'visa_anterior'");
                        $stmtVisa->execute([$id]);
                        $hasVisaAnterior = (bool) $stmtVisa->fetch();
                    }

                    if ($hasPasaporte && $hasVisaAnterior) {
                        $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([STATUS_VALIDANDO_RESPUESTAS, $id]);
                        $this->db->prepare("
                            INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by)
                            VALUES (?, ?, ?, ?, ?)
                        ")->execute([$id, STATUS_NUEVO, STATUS_VALIDANDO_RESPUESTAS, 'Cambio automático: hoja de información guardada y cuestionario completado', $_SESSION['user_id']]);
                    }
                }
            }

            $_SESSION['success'] = 'Hoja de información guardada correctamente';
            $this->redirect('/solicitudes/ver/' . $id);

        } catch (PDOException $e) {
            error_log("Error al guardar hoja de información: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar hoja de información';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    public function saveFamiliar($id) {
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

            if ($role === ROLE_ASESOR && $this->isAdvisorReadOnlyClosedAccess($application, $_SESSION['user_id'])) {
                $this->denyAdvisorReadOnlyClosedMutation($id);
            }

            // Ensure the parent information_sheet exists
            $stmtSheet = $this->db->prepare("SELECT id FROM information_sheets WHERE application_id = ?");
            $stmtSheet->execute([$id]);
            $sheet = $stmtSheet->fetch();

            if (!$sheet) {
                $_SESSION['error'] = 'Primero debe guardar la hoja de información Individual';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $sheetId           = $sheet['id'];
            $familiarId        = intval($_POST['familiar_id'] ?? 0);
            $entryDate         = trim($_POST['fam_entry_date'] ?? '') ?: null;
            $nombreCompleto    = trim($_POST['fam_nombre_completo'] ?? '');
            $parentesco        = trim($_POST['fam_parentesco'] ?? '');
            $fechaNacimiento   = trim($_POST['fam_fecha_nacimiento'] ?? '') ?: null;
            $pasaporte         = trim($_POST['fam_pasaporte'] ?? '');
            $residencePlace    = trim($_POST['fam_residence_place'] ?? '');
            $address           = trim($_POST['fam_address'] ?? '');
            $clientEmail       = trim($_POST['fam_client_email'] ?? '');
            $embassyEmail      = trim($_POST['fam_embassy_email'] ?? '');
            $amountPaid        = !empty($_POST['fam_amount_paid']) ? floatval($_POST['fam_amount_paid']) : null;
            $dhl               = trim($_POST['fam_dhl'] ?? '');
            $observations      = trim($_POST['fam_observations'] ?? '');

            if ($familiarId > 0) {
                // Update existing
                $stmtUpd = $this->db->prepare("
                    UPDATE information_sheet_familiar SET
                        entry_date = ?, nombre_completo = ?, parentesco = ?, fecha_nacimiento = ?,
                        pasaporte = ?, residence_place = ?, address = ?, client_email = ?,
                        embassy_email = ?, amount_paid = ?, dhl = ?, observations = ?
                    WHERE id = ? AND information_sheet_id = ?
                ");
                $stmtUpd->execute([
                    $entryDate, $nombreCompleto, $parentesco, $fechaNacimiento,
                    $pasaporte, $residencePlace, $address, $clientEmail,
                    $embassyEmail, $amountPaid, $dhl ?: null, $observations,
                    $familiarId, $sheetId
                ]);
            } else {
                // Insert new
                $stmtIns = $this->db->prepare("
                    INSERT INTO information_sheet_familiar
                        (information_sheet_id, entry_date, nombre_completo, parentesco, fecha_nacimiento,
                         pasaporte, residence_place, address, client_email, embassy_email,
                         amount_paid, dhl, observations, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtIns->execute([
                    $sheetId, $entryDate, $nombreCompleto, $parentesco, $fechaNacimiento,
                    $pasaporte, $residencePlace, $address, $clientEmail, $embassyEmail,
                    $amountPaid, $dhl ?: null, $observations, $_SESSION['user_id']
                ]);
            }

            logAudit($familiarId > 0 ? 'update' : 'create', 'solicitudes', "Familiar guardado para solicitud #$id");

            $_SESSION['success'] = 'Familiar guardado correctamente';
            $this->redirect('/solicitudes/ver/' . $id . '#familiar-tab');

        } catch (PDOException $e) {
            error_log("Error al guardar familiar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar familiar: ' . $e->getMessage();
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    public function deleteFamiliar($id) {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        try {
            $familiarId = intval($_POST['familiar_id'] ?? 0);
            if ($familiarId > 0) {
                $this->db->prepare("
                    DELETE isf FROM information_sheet_familiar isf
                    INNER JOIN information_sheets ish ON ish.id = isf.information_sheet_id
                    WHERE isf.id = ? AND ish.application_id = ?
                ")->execute([$familiarId, $id]);
            }

            logAudit('delete', 'solicitudes', "Familiar #$familiarId eliminado para solicitud #$id");

            $_SESSION['success'] = 'Familiar eliminado';
            $this->redirect('/solicitudes/ver/' . $id . '#familiar-tab');

        } catch (PDOException $e) {
            error_log("Error al eliminar familiar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar familiar';
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

            if ($role === ROLE_ASESOR && $this->isAdvisorReadOnlyClosedAccess($application, $_SESSION['user_id'])) {
                $this->denyAdvisorReadOnlyClosedMutation($id);
            }

            $attended     = isset($_POST['client_attended']) ? 1 : 0;
            $attendedDate = !empty($_POST['client_attended_date']) ? $_POST['client_attended_date'] : null;

            $this->db->prepare("UPDATE applications SET client_attended = ?, client_attended_date = ? WHERE id = ?")
                ->execute([$attended, $attendedDate, $id]);

            $isPassportService = stripos(trim((string) ($application['type'] ?? '')), 'pasaporte') !== false;

            // For visa flow, advance to purple after attendance; for passport keep current status (AZUL).
            if ($attended && $application['status'] === STATUS_CITA_PROGRAMADA) {
                if (!$isPassportService) {
                    $prevStatus = $application['status'];
                    $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([STATUS_EN_ESPERA_RESULTADO, $id]);
                    $this->db->prepare("INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$id, $prevStatus, STATUS_EN_ESPERA_RESULTADO, 'Cliente marcó asistencia a cita', $_SESSION['user_id']]);
                }
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
            $stmt = $this->db->prepare("SELECT created_by, status, data_json FROM applications WHERE id = ?");
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

            if ($role === ROLE_ASESOR && $this->isAdvisorReadOnlyClosedAccess($application, $_SESSION['user_id'])) {
                $this->denyAdvisorReadOnlyClosedMutation($id);
            }

            // Verify the form exists and is published before generating a link
            $stmtForm = $this->db->prepare("SELECT id FROM forms WHERE id = ? AND is_published = 1");
            $stmtForm->execute([$formLinkId]);
            if (!$stmtForm->fetch()) {
                $_SESSION['error'] = 'El formulario seleccionado no está publicado o no existe';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $this->db->prepare("UPDATE applications SET form_link_id = ?, form_link_status = 'enviado', form_link_sent_at = NOW() WHERE id = ?")
                ->execute([$formLinkId, $id]);

            // Ensure the linked form is publicly accessible when a link is generated
            $this->db->prepare("UPDATE forms SET public_enabled = 1 WHERE id = ?")
                ->execute([$formLinkId]);

            $_SESSION['success'] = 'Formulario vinculado. Copia el enlace y compártelo con el cliente.';
            $this->redirect('/solicitudes/ver/' . $id . '?copiar_enlace=1');

        } catch (PDOException $e) {
            error_log("Error al vincular formulario: " . $e->getMessage());
            $_SESSION['error'] = 'Error al vincular formulario';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    /**
     * Guardar cita a oficinas (fecha/hora y modalidad) para estado AZUL.
     */
    public function saveOfficeAppointment($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("SELECT created_by, status, data_json FROM applications WHERE id = ?");
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

            if ($role === ROLE_ASESOR && $this->isAdvisorReadOnlyClosedAccess($application, $_SESSION['user_id'])) {
                $this->denyAdvisorReadOnlyClosedMutation($id);
            }

            $officeDate     = !empty($_POST['office_appointment_date']) ? $_POST['office_appointment_date'] : null;
            $officeModality = in_array($_POST['office_appointment_modality'] ?? '', ['Zoom', 'Presencial'])
                ? $_POST['office_appointment_modality'] : null;

            $this->db->prepare("UPDATE applications SET office_appointment_date = ?, office_appointment_modality = ? WHERE id = ?")
                ->execute([$officeDate, $officeModality, $id]);

            $_SESSION['success'] = 'Cita a oficinas guardada correctamente';
            $this->redirect('/solicitudes/ver/' . $id);

        } catch (PDOException $e) {
            error_log("Error al guardar cita a oficinas: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar cita a oficinas';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    /**
     * Guardar cita de la SRE (fecha y hora) para pasaporte americano/mexicano en estado AZUL.
     */
    public function saveSreAppointment($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("
                SELECT a.created_by, a.status, a.type, a.subtype, f.name AS form_name, a.data_json
                FROM applications a
                LEFT JOIN forms f ON a.form_id = f.id
                WHERE a.id = ?
            ");
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

            if ($role === ROLE_ASESOR && $this->isAdvisorReadOnlyClosedAccess($application, $_SESSION['user_id'])) {
                $this->denyAdvisorReadOnlyClosedMutation($id);
            }

            if ($application['status'] !== STATUS_CITA_PROGRAMADA) {
                $_SESSION['error'] = 'La cita de la SRE solo se puede configurar en estatus Cita programada';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $normalizeText = function ($value) {
                $value = (string) $value;
                $value = strtr($value, [
                    'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
                    'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
                ]);
                return strtolower(trim($value));
            };

            $typeNormalized = $normalizeText($application['type'] ?? '');
            $subtypeNormalized = $normalizeText($application['subtype'] ?? '');
            $formNameNormalized = $normalizeText($application['form_name'] ?? '');

            $isAmericanPassport =
                $typeNormalized === 'pasaporte' &&
                (strpos($subtypeNormalized, 'americano') !== false || strpos($formNameNormalized, 'pasaporte americano') !== false);
            $isMexicanPassport =
                $typeNormalized === 'pasaporte' &&
                (strpos($subtypeNormalized, 'mexicano') !== false || strpos($formNameNormalized, 'pasaporte mexicano') !== false);

            if (!$isAmericanPassport && !$isMexicanPassport) {
                $_SESSION['error'] = 'La cita de la SRE aplica solo para solicitudes de Pasaporte Americano o Mexicano';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $sreAppointmentRaw = trim($_POST['sre_appointment_datetime'] ?? '');
            if ($sreAppointmentRaw === '') {
                $_SESSION['error'] = 'Debe capturar la fecha y hora de la cita de la SRE';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $parsedSreDatetime = DateTime::createFromFormat('Y-m-d\TH:i', $sreAppointmentRaw);
            if (!$parsedSreDatetime || $parsedSreDatetime->format('Y-m-d\TH:i') !== $sreAppointmentRaw) {
                $_SESSION['error'] = 'La fecha y hora de la cita de la SRE no es válida';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $existingData = json_decode($application['data_json'], true) ?: [];
            $existingData['cita_sre_fecha_hora'] = $parsedSreDatetime->format('Y-m-d H:i:s');

            $newJson = json_encode($existingData, JSON_UNESCAPED_UNICODE);
            $this->db->prepare("UPDATE applications SET data_json = ? WHERE id = ?")
                ->execute([$newJson, $id]);

            logAudit('update', 'solicitudes', 'Cita SRE configurada para solicitud #' . $id);

            $_SESSION['success'] = 'Cita de la SRE guardada correctamente';
            $this->redirect('/solicitudes/ver/' . $id);
        } catch (PDOException $e) {
            error_log('Error al guardar cita SRE: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar cita de la SRE';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    /**
     * Guardar respuestas editadas del formulario (estado Validando respuestas).
     * Accesible para Asesor y Admin/Gerente.
     */
    public function saveFormResponses($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("SELECT created_by, status, data_json FROM applications WHERE id = ?");
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

            if ($role === ROLE_ASESOR && $this->isAdvisorReadOnlyClosedAccess($application, $_SESSION['user_id'])) {
                $this->denyAdvisorReadOnlyClosedMutation($id);
            }

            if ($application['status'] !== STATUS_VALIDANDO_RESPUESTAS) {
                $_SESSION['error'] = 'Solo se pueden editar respuestas cuando el estatus es "Validando respuestas"';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $existingData = json_decode($application['data_json'], true) ?: [];
            $postedAnswers = $_POST['answers'] ?? [];

            // Merge posted answers into existing data (only overwrite non-file keys)
            foreach ($postedAnswers as $key => $value) {
                $key = strip_tags($key);
                if (is_array($value)) {
                    $existingData[$key] = array_map('strip_tags', $value);
                } else {
                    $existingData[$key] = strip_tags($value);
                }
            }

            $newJson = json_encode($existingData, JSON_UNESCAPED_UNICODE);
            $this->db->prepare("UPDATE applications SET data_json = ? WHERE id = ?")
                ->execute([$newJson, $id]);

            logAudit(
                'update',
                'solicitudes',
                'Respuestas del cuestionario editadas (estatus Validando respuestas) para solicitud #' . $id
            );

            $_SESSION['success'] = 'Respuestas guardadas correctamente';
            $this->redirect('/solicitudes/ver/' . $id);

        } catch (PDOException $e) {
            error_log("Error al guardar respuestas: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar respuestas';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    public function saveReceivedDocumentsChecklist($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("
                SELECT a.created_by, a.status, a.type, a.subtype, f.name AS form_name, a.data_json
                FROM applications a
                LEFT JOIN forms f ON a.form_id = f.id
                WHERE a.id = ?
            ");
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

            if ($role === ROLE_ASESOR && $this->isAdvisorReadOnlyClosedAccess($application, $_SESSION['user_id'])) {
                $this->denyAdvisorReadOnlyClosedMutation($id);
            }

            $normalizeText = function ($value) {
                $value = (string) $value;
                $value = strtr($value, [
                    'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
                    'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
                ]);
                return strtolower(trim($value));
            };

            $typeNormalized = $normalizeText($application['type'] ?? '');
            $subtypeNormalized = $normalizeText($application['subtype'] ?? '');
            $formNameNormalized = $normalizeText($application['form_name'] ?? '');

            $isAmericanPassport =
                $typeNormalized === 'pasaporte' &&
                (strpos($subtypeNormalized, 'americano') !== false || strpos($formNameNormalized, 'pasaporte americano') !== false);
            $isMexicanPassport =
                $typeNormalized === 'pasaporte' &&
                (strpos($subtypeNormalized, 'mexicano') !== false || strpos($formNameNormalized, 'pasaporte mexicano') !== false);

            if (!$isAmericanPassport && !$isMexicanPassport) {
                $_SESSION['error'] = 'Este checklist aplica solo para solicitudes de Pasaporte Americano o Mexicano';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            if ($isMexicanPassport) {
                $dataKey = 'documentos_recibidos_pasaporte_mexicano';
                $allowedDocKeys = [
                    'acta_nacimiento',
                    'curp_certificada',
                    'ine',
                    'pasaporte_anterior',
                    'pago_sre',
                    'carta_consentimiento_menores',
                    'identificacion_padres',
                ];
            } else {
                $dataKey = 'documentos_recibidos_pasaporte_americano';
                $allowedDocKeys = [
                    'acta_nacimiento_americana',
                    'pasaporte_anterior',
                    'identificacion_oficial',
                    'social_security_number',
                    'reporte_policial',
                ];
            }

            $selected = $_POST['received_documents'] ?? [];
            if (!is_array($selected)) {
                $selected = [];
            }

            $selectedNormalized = [];
            foreach ($selected as $docKey) {
                $docKey = trim((string) $docKey);
                if (in_array($docKey, $allowedDocKeys, true) && !in_array($docKey, $selectedNormalized, true)) {
                    $selectedNormalized[] = $docKey;
                }
            }

            $existingData = json_decode($application['data_json'], true) ?: [];
            $existingData[$dataKey] = $selectedNormalized;

            $newJson = json_encode($existingData, JSON_UNESCAPED_UNICODE);
            $this->db->prepare("UPDATE applications SET data_json = ? WHERE id = ?")
                ->execute([$newJson, $id]);

            logAudit('update', 'solicitudes', 'Checklist de documentos recibidos actualizado para solicitud #' . $id);

            $_SESSION['success'] = 'Checklist de documentos recibidos guardado correctamente';
            $this->redirect('/solicitudes/ver/' . $id);
        } catch (PDOException $e) {
            error_log('Error al guardar checklist de documentos recibidos: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar checklist de documentos recibidos';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    public function saveObservationsIncidencesChecklist($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("
                SELECT a.created_by, a.status, a.type, a.subtype, f.name AS form_name, a.data_json
                FROM applications a
                LEFT JOIN forms f ON a.form_id = f.id
                WHERE a.id = ?
            ");
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

            if ($role === ROLE_ASESOR && $this->isAdvisorReadOnlyClosedAccess($application, $_SESSION['user_id'])) {
                $this->denyAdvisorReadOnlyClosedMutation($id);
            }

            $normalizeText = function ($value) {
                $value = (string) $value;
                $value = strtr($value, [
                    'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
                    'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
                ]);
                return strtolower(trim($value));
            };

            $typeNormalized = $normalizeText($application['type'] ?? '');
            $subtypeNormalized = $normalizeText($application['subtype'] ?? '');
            $formNameNormalized = $normalizeText($application['form_name'] ?? '');

            $isAmericanPassport =
                $typeNormalized === 'pasaporte' &&
                (strpos($subtypeNormalized, 'americano') !== false || strpos($formNameNormalized, 'pasaporte americano') !== false);
            $isMexicanPassport =
                $typeNormalized === 'pasaporte' &&
                (strpos($subtypeNormalized, 'mexicano') !== false || strpos($formNameNormalized, 'pasaporte mexicano') !== false);

            if (!$isAmericanPassport && !$isMexicanPassport) {
                $_SESSION['error'] = 'Este checklist aplica solo para solicitudes de Pasaporte Americano o Mexicano';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $allowedKeys = [
                'cliente_no_respondio',
                'documentacion_incompleta',
                'error_curp',
                'pago_pendiente',
                'cita_cancelada',
            ];

            $selected = $_POST['observaciones_incidencias'] ?? [];
            if (!is_array($selected)) {
                $selected = [];
            }

            $selectedNormalized = [];
            foreach ($selected as $itemKey) {
                $itemKey = trim((string) $itemKey);
                if (in_array($itemKey, $allowedKeys, true) && !in_array($itemKey, $selectedNormalized, true)) {
                    $selectedNormalized[] = $itemKey;
                }
            }

            $existingData = json_decode($application['data_json'], true) ?: [];
            $existingData['observaciones_incidencias_pasaporte'] = $selectedNormalized;

            $newJson = json_encode($existingData, JSON_UNESCAPED_UNICODE);
            $this->db->prepare("UPDATE applications SET data_json = ? WHERE id = ?")
                ->execute([$newJson, $id]);

            logAudit('update', 'solicitudes', 'Checklist de observaciones e incidencias actualizado para solicitud #' . $id);

            $_SESSION['success'] = 'Observaciones e incidencias guardadas correctamente';
            $this->redirect('/solicitudes/ver/' . $id);
        } catch (PDOException $e) {
            error_log('Error al guardar observaciones e incidencias: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar observaciones e incidencias';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    /**
     * Confirmar respuestas del formulario y avanzar a "Listo para comenzar" (STATUS_LISTO_SOLICITUD).
     * Accesible para Asesor y Admin/Gerente.
     */
    public function confirmFormResponses($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("SELECT created_by, status, data_json FROM applications WHERE id = ?");
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

            if ($role === ROLE_ASESOR && $this->isAdvisorReadOnlyClosedAccess($application, $_SESSION['user_id'])) {
                $this->denyAdvisorReadOnlyClosedMutation($id);
            }

            if ($application['status'] !== STATUS_VALIDANDO_RESPUESTAS) {
                $_SESSION['error'] = 'La solicitud no está en estado "Validando respuestas"';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")
                ->execute([STATUS_LISTO_SOLICITUD, $id]);

            $this->db->prepare("
                INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([$id, STATUS_VALIDANDO_RESPUESTAS, STATUS_LISTO_SOLICITUD, 'Respuestas confirmadas', $_SESSION['user_id']]);

            logAudit(
                'status_change',
                'solicitudes',
                'Respuestas confirmadas: ' . STATUS_VALIDANDO_RESPUESTAS . ' → ' . STATUS_LISTO_SOLICITUD . ' para solicitud #' . $id
            );

            $_SESSION['success'] = 'Respuestas confirmadas. Estatus actualizado a "Listo para comenzar"';
            $this->redirect('/solicitudes/ver/' . $id);

        } catch (PDOException $e) {
            error_log("Error al confirmar respuestas: " . $e->getMessage());
            $_SESSION['error'] = 'Error al confirmar respuestas';
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
            $stmt = $this->db->prepare("SELECT created_by, status, data_json FROM applications WHERE id = ?");
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

            if ($role === ROLE_ASESOR && $this->isAdvisorReadOnlyClosedAccess($application, $_SESSION['user_id'])) {
                $_SESSION['error'] = 'Este trámite cerrado está en modo solo lectura para asesor durante la reactivación temporal';
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
    public function reactivateTemporary($id) {
        $this->requireRole([ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes');
        }

        $advisorId = intval($_POST['advisor_id'] ?? 0);
        $startRaw = trim($_POST['start_at'] ?? '');
        $endRaw = trim($_POST['end_at'] ?? '');

        if ($advisorId <= 0 || $startRaw === '' || $endRaw === '') {
            $_SESSION['error'] = 'Debe seleccionar asesor y periodo de reactivación';
            $this->redirect('/solicitudes');
        }

        $startAt = str_replace('T', ' ', $startRaw) . ':00';
        $endAt = str_replace('T', ' ', $endRaw) . ':00';
        $startTs = strtotime($startAt);
        $endTs = strtotime($endAt);

        if ($startTs === false || $endTs === false || $endTs <= $startTs) {
            $_SESSION['error'] = 'El periodo de reactivación no es válido';
            $this->redirect('/solicitudes');
        }

        try {
            $stmt = $this->db->prepare("SELECT id, status, data_json FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            if (($application['status'] ?? '') !== STATUS_TRAMITE_CERRADO) {
                $_SESSION['error'] = 'Solo se puede reactivar temporalmente un trámite cerrado';
                $this->redirect('/solicitudes');
            }

            $stmtAdvisor = $this->db->prepare("SELECT id FROM users WHERE id = ? AND role = ? LIMIT 1");
            $stmtAdvisor->execute([$advisorId, ROLE_ASESOR]);
            if (!$stmtAdvisor->fetch()) {
                $_SESSION['error'] = 'El asesor seleccionado no es válido';
                $this->redirect('/solicitudes');
            }

            $data = $this->decodeApplicationDataJson($application['data_json'] ?? '{}');
            $grants = $data['closed_visibility_grants'] ?? [];
            if (!is_array($grants)) {
                $grants = [];
            }

            $cleanedGrants = [];
            $nowTs = time();
            foreach ($grants as $grant) {
                if (!is_array($grant)) {
                    continue;
                }

                $grantAdvisorId = intval($grant['advisor_id'] ?? 0);
                if ($grantAdvisorId === $advisorId) {
                    continue;
                }

                $grantEndTs = strtotime((string) ($grant['end_at'] ?? ''));
                if ($grantEndTs !== false && $grantEndTs >= $nowTs) {
                    $cleanedGrants[] = $grant;
                }
            }

            $cleanedGrants[] = [
                'advisor_id' => $advisorId,
                'start_at' => date('Y-m-d H:i:s', $startTs),
                'end_at' => date('Y-m-d H:i:s', $endTs),
                'granted_by' => intval($_SESSION['user_id']),
                'granted_at' => date('Y-m-d H:i:s'),
            ];

            $data['closed_visibility_grants'] = $cleanedGrants;

            $this->db->prepare("UPDATE applications SET data_json = ? WHERE id = ?")
                ->execute([json_encode($data, JSON_UNESCAPED_UNICODE), $id]);

            logAudit('update', 'solicitudes', "Reactivación temporal para solicitud #$id asignada a asesor #$advisorId");

            $_SESSION['success'] = 'Reactivación temporal guardada correctamente';
            $this->redirect('/solicitudes');
        } catch (PDOException $e) {
            error_log("Error al guardar reactivación temporal: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar la reactivación temporal';
            $this->redirect('/solicitudes');
        }
    }

    /**
     * Enviar correo personalizado de "trámite listo" al email de datos básicos del solicitante.
     * Solo Administrador.
     */
    public function sendReadyProcedureEmail($id) {
        $this->requireRole([ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $subject = trim((string) ($_POST['email_subject'] ?? ''));
        $bodyText = trim((string) ($_POST['email_body'] ?? ''));

        if ($subject === '' || $bodyText === '') {
            $_SESSION['error'] = 'El asunto y el contenido del correo son obligatorios';
            $this->redirect('/solicitudes/ver/' . $id);
        }

        try {
            $stmt = $this->db->prepare("SELECT id, folio, type, subtype, data_json, client_name, created_by FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            $basicData = $this->decodeApplicationDataJson($application['data_json'] ?? '{}');
            $recipient = trim((string) ($basicData['email'] ?? ''));

            if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'No se encontró un email válido en los datos básicos del solicitante';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $stmtCfg = $this->db->query("SELECT config_key, config_value FROM global_config WHERE config_key IN ('smtp_user','smtp_password','smtp_host','smtp_port','site_name')");
            $config = $stmtCfg->fetchAll(PDO::FETCH_KEY_PAIR);

            $smtpHost = $config['smtp_host'] ?? '';
            $smtpUser = $config['smtp_user'] ?? '';
            $smtpPassword = $config['smtp_password'] ?? '';
            $smtpPort = intval($config['smtp_port'] ?? 465);
            $siteName = $config['site_name'] ?? (function_exists('getSiteName') ? getSiteName() : 'CRM Visas');

            if ($smtpHost === '' || $smtpUser === '' || $smtpPassword === '') {
                $_SESSION['error'] = 'La configuración SMTP está incompleta. Verifique Configuración del Sistema.';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            require_once ROOT_PATH . '/vendor/autoload.php';

            $safeBody = nl2br(htmlspecialchars($bodyText, ENT_QUOTES, 'UTF-8'));

            $attachmentFiles = [];
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
            if (!empty($_FILES['email_attachments']) && is_array($_FILES['email_attachments']['name'] ?? null)) {
                $totalFiles = count($_FILES['email_attachments']['name']);
                for ($i = 0; $i < $totalFiles; $i++) {
                    $error = $_FILES['email_attachments']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                    if ($error === UPLOAD_ERR_NO_FILE || $error !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $tmpName = $_FILES['email_attachments']['tmp_name'][$i] ?? '';
                    $originalName = $_FILES['email_attachments']['name'][$i] ?? 'adjunto';
                    $size = intval($_FILES['email_attachments']['size'][$i] ?? 0);
                    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                    if (!in_array($extension, $allowedExtensions, true)) {
                        continue;
                    }
                    if ($size <= 0 || $size > MAX_FILE_SIZE) {
                        continue;
                    }
                    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                        continue;
                    }

                    $attachmentFiles[] = ['tmp' => $tmpName, 'name' => $originalName];
                }
            }

            $sendAttempt = function () use (
                $smtpHost,
                $smtpUser,
                $smtpPassword,
                $smtpPort,
                $siteName,
                $recipient,
                $subject,
                $safeBody,
                $bodyText,
                $attachmentFiles
            ) {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $smtpPassword;
                $mail->Port = $smtpPort;
                $mail->SMTPSecure = ($smtpPort === 465)
                    ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                    : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
                $mail->Timeout = 25;
                $mail->CharSet = 'UTF-8';
                $mail->setFrom($smtpUser, $siteName);
                $mail->addAddress($recipient);

                foreach ($attachmentFiles as $attachmentFile) {
                    $mail->addAttachment($attachmentFile['tmp'], $attachmentFile['name']);
                }

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = '<div style="font-family:Arial,sans-serif;max-width:700px;line-height:1.5;">' . $safeBody . '</div>';
                $mail->AltBody = $bodyText;

                $mail->send();
            };

            try {
                $sendAttempt();
            } catch (\PHPMailer\PHPMailer\Exception $firstTryError) {
                error_log("Primer intento fallido (trámite listo #$id): " . $firstTryError->getMessage());
                // Reintento inmediato para errores transitorios del SMTP.
                $sendAttempt();
            }

            $clientName = trim((string) ($application['client_name'] ?? ''));
            logAudit('email', 'solicitudes', "Correo 'trámite listo' enviado a $recipient para solicitud #$id");
            logCustomerJourney(
                $id,
                'email',
                'Correo de trámite listo enviado',
                "Asunto: $subject" . ($clientName !== '' ? " | Cliente: $clientName" : ''),
                'email'
            );

            $_SESSION['success'] = 'Correo enviado correctamente a ' . $recipient . '. Si su proveedor lo retrasa, puede tardar unos minutos en recibirse.';
            $this->redirect('/solicitudes/ver/' . $id);
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Error PHPMailer al enviar trámite listo: " . $e->getMessage());
            $_SESSION['error'] = 'Error al enviar correo: ' . $e->getMessage();
            $this->redirect('/solicitudes/ver/' . $id);
        } catch (PDOException $e) {
            error_log("Error al enviar correo de trámite listo: " . $e->getMessage());
            $_SESSION['error'] = 'Error al enviar correo';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

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
     * Visualizar un documento por su ID.
     */
    public function viewDocument($docId) {
        $this->requireLogin();
        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("
                SELECT d.*, a.id as app_id, a.created_by as app_created_by, a.status, a.data_json
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

            $docStatus = $doc['status'] ?? '';
            $docCreatorId = intval($doc['app_created_by'] ?? 0);

            if ($role === ROLE_ASESOR) {
                if ($docCreatorId !== intval($_SESSION['user_id'])) {
                    $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                    $this->redirect('/solicitudes');
                }

                if ($docStatus === STATUS_FINALIZADO) {
                    $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                    $this->redirect('/solicitudes');
                }

                if ($docStatus === STATUS_TRAMITE_CERRADO && !$this->canAdvisorViewClosedApplication([
                    'created_by' => $docCreatorId,
                    'status' => $docStatus,
                    'data_json' => $doc['data_json'] ?? '{}',
                ], $_SESSION['user_id'])) {
                    $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                    $this->redirect('/solicitudes');
                }
            } elseif (!in_array($role, [ROLE_ADMIN, ROLE_GERENTE])) {
                $_SESSION['error'] = 'No tiene permisos para visualizar documentos';
                $this->redirect('/solicitudes');
            }

            $filePath = ROOT_PATH . '/public' . $doc['file_path'];
            if (!file_exists($filePath)) {
                $_SESSION['error'] = 'El archivo no existe en el servidor';
                $this->redirect('/solicitudes/ver/' . $doc['app_id']);
            }

            logAudit('view', 'documentos', "Visualización de documento #$docId ({$doc['name']})");

            $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: inline; filename="' . basename($doc['name']) . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: private, no-cache');
            readfile($filePath);
            exit;

        } catch (PDOException $e) {
            error_log("Error al visualizar documento: " . $e->getMessage());
            $_SESSION['error'] = 'Error al visualizar documento';
            $this->redirect('/solicitudes');
        }
    }

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

    /**
     * Helper: save an uploaded file as a document of the given doc_type.
     * Returns true on success, false on failure.
     */
    private function saveApplicationFile($appId, array $fileInfo, string $docType): bool {
        $fileName    = $fileInfo['name'];
        $fileSize    = $fileInfo['size'];
        $fileTmpName = $fileInfo['tmp_name'];
        $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileSize > MAX_FILE_SIZE || !in_array($fileExt, ALLOWED_EXTENSIONS)) {
            return false;
        }

        $uploadDir = ROOT_PATH . '/public/uploads/applications/' . $appId;
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFileName  = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
        $filePath     = $uploadDir . '/' . $newFileName;

        if (!move_uploaded_file($fileTmpName, $filePath)) {
            return false;
        }

        $relativePath = '/uploads/applications/' . $appId . '/' . $newFileName;
        try {
            $this->db->prepare("
                INSERT INTO documents (application_id, name, doc_type, file_path, file_type, file_size, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $appId,
                $fileName,
                $docType,
                $relativePath,
                $fileExt,
                $fileSize,
                $_SESSION['user_id']
            ]);
        } catch (PDOException $e) {
            error_log("saveApplicationFile PDO error: " . $e->getMessage());
            return false;
        }

        return true;
    }
}
