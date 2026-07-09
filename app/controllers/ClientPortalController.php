<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class ClientPortalController extends BaseController {
    private function normalizeFieldsPayload($decodedFields) {
        if (isset($decodedFields['fields']) && is_array($decodedFields['fields'])) {
            return ['fields' => $decodedFields['fields']];
        }
        if (is_array($decodedFields)) {
            return ['fields' => $decodedFields];
        }
        return ['fields' => []];
    }

    private function loadClientApplication($id) {
        $stmt = $this->db->prepare("\n            SELECT a.*, u.full_name as creator_name, u.email as creator_email,\n                   f.name as form_name, f.fields_json, f.pages_json, f.pagination_enabled\n            FROM applications a\n            LEFT JOIN users u ON a.created_by = u.id\n            LEFT JOIN forms f ON a.form_id = f.id\n            WHERE a.id = ? AND a.client_user_id = ?\n        ");
        $stmt->execute([$id, $_SESSION['user_id']]);
        return $stmt->fetch();
    }

    private function calculateProgress($fields, $data) {
        $total = 0;
        $filled = 0;
        foreach ($fields['fields'] ?? [] as $field) {
            $type = $field['type'] ?? 'text';
            if (in_array($type, ['label', 'paragraph', 'html', 'heading'])) {
                continue;
            }
            if ($type === 'file') {
                continue;
            }
            $id = $field['id'] ?? null;
            if (!$id) {
                continue;
            }
            $total++;
            if (isset($data[$id])) {
                $value = $data[$id];
                if (is_array($value)) {
                    if (count(array_filter($value, fn($v) => trim((string)$v) !== '')) > 0) {
                        $filled++;
                    }
                } elseif (trim((string)$value) !== '') {
                    $filled++;
                }
            }
        }

        return $total > 0 ? round(($filled / $total) * 100, 2) : 0;
    }

    public function index() {
        $this->requireClient();

        try {
            $stmt = $this->db->prepare("SELECT id, username, email, full_name, phone FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $client = $stmt->fetch();

            $stmt = $this->db->prepare("\n                SELECT a.*, f.name as form_name, u.full_name as creator_name,\n                       COUNT(DISTINCT d.id) as documents_count,\n                       (\n                           SELECT COUNT(*)\n                           FROM client_messages cm_unread\n                           WHERE cm_unread.application_id = a.id\n                             AND cm_unread.sender_role = 'Equipo'\n                             AND cm_unread.is_read_by_client = 0\n                       ) as unread_messages,\n                       (\n                           SELECT cm_last.message\n                           FROM client_messages cm_last\n                           WHERE cm_last.application_id = a.id\n                           ORDER BY cm_last.created_at DESC, cm_last.id DESC\n                           LIMIT 1\n                       ) as latest_message,\n                       (\n                           SELECT cm_last.sender_role\n                           FROM client_messages cm_last\n                           WHERE cm_last.application_id = a.id\n                           ORDER BY cm_last.created_at DESC, cm_last.id DESC\n                           LIMIT 1\n                       ) as latest_message_role,\n                       (\n                           SELECT cm_last.created_at\n                           FROM client_messages cm_last\n                           WHERE cm_last.application_id = a.id\n                           ORDER BY cm_last.created_at DESC, cm_last.id DESC\n                           LIMIT 1\n                       ) as latest_message_at\n                FROM applications a\n                LEFT JOIN forms f ON a.form_id = f.id\n                LEFT JOIN users u ON a.created_by = u.id\n                LEFT JOIN documents d ON d.application_id = a.id\n                WHERE a.client_user_id = ?\n                GROUP BY a.id\n                ORDER BY a.updated_at DESC, a.created_at DESC\n            ");
            $stmt->execute([$_SESSION['user_id']]);
            $applications = $stmt->fetchAll();

            $this->view('client-portal/index', [
                'client' => $client,
                'applications' => $applications,
            ]);
        } catch (PDOException $e) {
            error_log('Error en portal cliente index: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar tu portal';
            $this->view('client-portal/index', ['client' => [], 'applications' => []]);
        }
    }

    public function show($id) {
        $this->requireClient();

        try {
            $application = $this->loadClientApplication($id);
            if (!$application) {
                $_SESSION['error'] = 'No tienes acceso a este trámite o aún no está vinculado a tu usuario';
                $this->redirect('/mi-tramite');
            }

            $dataJson = json_decode($application['data_json'] ?? '{}', true) ?: [];
            $fields = $this->normalizeFieldsPayload(json_decode($application['fields_json'] ?? '[]', true));

            $stmt = $this->db->prepare("\n                SELECT d.*, u.full_name as uploaded_by_name\n                FROM documents d\n                LEFT JOIN users u ON d.uploaded_by = u.id\n                WHERE d.application_id = ?\n                ORDER BY d.created_at DESC\n            ");
            $stmt->execute([$id]);
            $documents = $stmt->fetchAll();

            try {
                $stmt = $this->db->prepare("\n                    SELECT n.*, u.full_name as created_by_name\n                    FROM application_notes n\n                    LEFT JOIN users u ON n.created_by = u.id\n                    WHERE n.application_id = ? AND COALESCE(n.visible_to_client, 0) = 1\n                    ORDER BY n.is_important DESC, n.created_at DESC\n                ");
                $stmt->execute([$id]);
                $notes = $stmt->fetchAll();
            } catch (PDOException $e) {
                $notes = [];
            }

            try {
                $stmt = $this->db->prepare("\n                    SELECT *\n                    FROM application_note_responses\n                    WHERE application_id = ? AND client_user_id = ?\n                    ORDER BY created_at DESC\n                ");
                $stmt->execute([$id, $_SESSION['user_id']]);
                $responses = $stmt->fetchAll();
            } catch (PDOException $e) {
                $responses = [];
            }

            try {
                $stmt = $this->db->prepare("\n                    SELECT cm.*, u.full_name as sender_name\n                    FROM client_messages cm\n                    LEFT JOIN users u ON cm.sender_user_id = u.id\n                    WHERE cm.application_id = ?\n                    ORDER BY cm.created_at ASC\n                ");
                $stmt->execute([$id]);
                $messages = $stmt->fetchAll();

                $this->db->prepare("UPDATE client_messages SET is_read_by_client = 1 WHERE application_id = ? AND sender_role = 'Equipo'")
                    ->execute([$id]);
            } catch (PDOException $e) {
                $messages = [];
            }

            $stmt = $this->db->prepare("\n                SELECT sh.*, u.full_name as changed_by_name\n                FROM status_history sh\n                LEFT JOIN users u ON sh.changed_by = u.id\n                WHERE sh.application_id = ?\n                ORDER BY sh.created_at DESC\n            ");
            $stmt->execute([$id]);
            $history = $stmt->fetchAll();

            $currentPage = intval($_GET['pagina'] ?? ($application['client_form_current_page'] ?? 1));
            if ($currentPage < 1) {
                $currentPage = 1;
            }

            $this->view('client-portal/show', [
                'application' => $application,
                'dataJson' => $dataJson,
                'fields' => $fields,
                'documents' => $documents,
                'notes' => $notes,
                'responses' => $responses,
                'messages' => $messages,
                'history' => $history,
                'currentPage' => $currentPage,
            ]);
        } catch (PDOException $e) {
            error_log('Error en portal cliente show: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar tu trámite';
            $this->redirect('/mi-tramite');
        }
    }

    public function updateProfile() {
        $this->requireClient();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/mi-tramite');
        }

        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($fullName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Nombre y correo válido son obligatorios';
            $this->redirect('/mi-tramite');
        }

        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()['total'] > 0) {
                $_SESSION['error'] = 'Ese correo ya está registrado en otro usuario';
                $this->redirect('/mi-tramite');
            }

            $stmt = $this->db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ? AND role = ?");
            $stmt->execute([$fullName, $email, $phone, $_SESSION['user_id'], ROLE_CLIENTE]);

            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;
            $_SESSION['success'] = 'Información personal actualizada';
            $this->redirect('/mi-tramite');
        } catch (PDOException $e) {
            error_log('Error al actualizar perfil cliente: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar información personal';
            $this->redirect('/mi-tramite');
        }
    }

    public function updateForm($id) {
        $this->requireClient();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/mi-tramite/ver/' . $id);
        }

        try {
            $application = $this->loadClientApplication($id);
            if (!$application) {
                $_SESSION['error'] = 'No tienes acceso a este trámite';
                $this->redirect('/mi-tramite');
            }

            $currentData = json_decode($application['data_json'] ?? '{}', true) ?: [];
            $postedData = $_POST['form_data'] ?? [];
            $pageFieldIds = $_POST['page_field_ids'] ?? [];
            $currentPage = max(1, intval($_POST['current_page'] ?? 1));
            $totalPages = max(1, intval($_POST['total_pages'] ?? 1));
            $action = $_POST['client_action'] ?? 'save';

            if (!is_array($postedData)) {
                $postedData = [];
            }
            if (!is_array($pageFieldIds)) {
                $pageFieldIds = [];
            }

            // Los campos de la página actual se reemplazan en el JSON existente.
            // Esto permite guardar avances parciales sin borrar lo que ya se había capturado en otras páginas.
            foreach ($pageFieldIds as $fieldId) {
                $fieldId = trim((string)$fieldId);
                if ($fieldId === '') {
                    continue;
                }
                if (!array_key_exists($fieldId, $postedData)) {
                    $currentData[$fieldId] = '';
                }
            }

            foreach ($postedData as $key => $value) {
                if (is_array($value)) {
                    $currentData[$key] = array_map(fn($v) => trim((string)$v), $value);
                } else {
                    $currentData[$key] = trim((string)$value);
                }
            }

            $fields = $this->normalizeFieldsPayload(json_decode($application['fields_json'] ?? '[]', true));
            $progress = $this->calculateProgress($fields, $currentData);

            $redirectPage = $currentPage;
            if ($action === 'next') {
                $redirectPage = min($totalPages, $currentPage + 1);
            } elseif ($action === 'prev') {
                $redirectPage = max(1, $currentPage - 1);
            }

            try {
                $stmt = $this->db->prepare("
                    UPDATE applications
                    SET data_json = ?, progress_percentage = ?, client_form_current_page = ?,
                        client_update_pending = 1, client_last_update_at = NOW(),
                        client_last_update_comment = ?, updated_at = NOW()
                    WHERE id = ? AND client_user_id = ?
                ");
                $stmt->execute([
                    json_encode($currentData, JSON_UNESCAPED_UNICODE),
                    $progress,
                    $redirectPage,
                    'Formulario actualizado por el cliente',
                    $id,
                    $_SESSION['user_id']
                ]);
            } catch (PDOException $columnError) {
                // Compatibilidad si aún no se ejecutó la migración de client_form_current_page.
                $stmt = $this->db->prepare("
                    UPDATE applications
                    SET data_json = ?, progress_percentage = ?, client_update_pending = 1,
                        client_last_update_at = NOW(), client_last_update_comment = ?, updated_at = NOW()
                    WHERE id = ? AND client_user_id = ?
                ");
                $stmt->execute([
                    json_encode($currentData, JSON_UNESCAPED_UNICODE),
                    $progress,
                    'Formulario actualizado por el cliente',
                    $id,
                    $_SESSION['user_id']
                ]);
            }

            logCustomerJourney($id, 'client_portal', 'Formulario actualizado por cliente', 'El cliente actualizó información del formulario desde su portal', 'portal');

            $_SESSION['success'] = $action === 'next' ? 'Avance guardado. Puedes continuar con la siguiente sección.' : 'Avance guardado correctamente.';
            $this->redirect('/mi-tramite/ver/' . $id . '?pagina=' . $redirectPage . '#formulario-cliente');
        } catch (PDOException $e) {
            error_log('Error al actualizar formulario cliente: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar formulario. Verifica que la migración esté aplicada.';
            $this->redirect('/mi-tramite/ver/' . $id);
        }
    }

    public function uploadDocument($id) {
        $this->requireClient();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/mi-tramite/ver/' . $id);
        }

        try {
            $application = $this->loadClientApplication($id);
            if (!$application) {
                $_SESSION['error'] = 'No tienes acceso a este trámite';
                $this->redirect('/mi-tramite');
            }

            if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'Selecciona un archivo válido';
                $this->redirect('/mi-tramite/ver/' . $id);
            }

            $file = $_FILES['document'];
            $fileName = $file['name'];
            $fileSize = $file['size'];
            $fileTmpName = $file['tmp_name'];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $docType = trim($_POST['doc_type'] ?? 'cliente_adicional');

            $allowedDocTypes = [
                'pasaporte_vigente', 'visa_anterior', 'ficha_pago_consular', 'adicional',
                'visa_canadiense_anterior', 'eta_anterior', 'cliente_adicional',
                'comprobante_domicilio', 'fotografia', 'comprobante_ingresos', 'carta_laboral'
            ];
            if (!in_array($docType, $allowedDocTypes)) {
                $docType = 'cliente_adicional';
            }

            if ($fileSize > MAX_FILE_SIZE) {
                $_SESSION['error'] = 'El archivo excede el tamaño máximo permitido (2MB)';
                $this->redirect('/mi-tramite/ver/' . $id);
            }
            if (!in_array($fileType, ALLOWED_EXTENSIONS)) {
                $_SESSION['error'] = 'Tipo de archivo no permitido';
                $this->redirect('/mi-tramite/ver/' . $id);
            }

            $uploadDir = ROOT_PATH . '/public/uploads/applications/' . $id;
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newFileName = uniqid('cliente_', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
            $filePath = $uploadDir . '/' . $newFileName;
            if (!move_uploaded_file($fileTmpName, $filePath)) {
                $_SESSION['error'] = 'Error al guardar el archivo';
                $this->redirect('/mi-tramite/ver/' . $id);
            }

            $relativePath = '/uploads/applications/' . $id . '/' . $newFileName;
            try {
                $stmt = $this->db->prepare("\n                    INSERT INTO documents (application_id, name, doc_type, file_path, file_type, file_size, uploaded_by, uploaded_source, review_status)\n                    VALUES (?, ?, ?, ?, ?, ?, ?, 'cliente', 'pendiente')\n                ");
                $stmt->execute([$id, $fileName, $docType, $relativePath, $fileType, $fileSize, $_SESSION['user_id']]);
            } catch (PDOException $e) {
                $stmt = $this->db->prepare("\n                    INSERT INTO documents (application_id, name, doc_type, file_path, file_type, file_size, uploaded_by)\n                    VALUES (?, ?, ?, ?, ?, ?, ?)\n                ");
                $stmt->execute([$id, $fileName, $docType, $relativePath, $fileType, $fileSize, $_SESSION['user_id']]);
            }

            $this->db->prepare("\n                UPDATE applications\n                SET client_update_pending = 1, client_last_update_at = NOW(), client_last_update_comment = ?\n                WHERE id = ?\n            ")->execute(['Documento subido por el cliente: ' . $fileName, $id]);

            logCustomerJourney($id, 'document_upload', 'Documento subido por cliente', $fileName, 'portal');

            $_SESSION['success'] = 'Documento subido correctamente. Quedará pendiente de revisión.';
            $this->redirect('/mi-tramite/ver/' . $id);
        } catch (PDOException $e) {
            error_log('Error al subir documento cliente: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al subir documento. Verifica que la migración esté aplicada.';
            $this->redirect('/mi-tramite/ver/' . $id);
        }
    }

    public function respondObservation($id) {
        $this->requireClient();

        $noteId = intval($_POST['note_id'] ?? 0);
        $responseText = trim($_POST['response_text'] ?? '');

        if ($noteId <= 0 || $responseText === '') {
            $_SESSION['error'] = 'Selecciona una observación y escribe una respuesta';
            $this->redirect('/mi-tramite/ver/' . $id);
        }

        try {
            $application = $this->loadClientApplication($id);
            if (!$application) {
                $_SESSION['error'] = 'No tienes acceso a este trámite';
                $this->redirect('/mi-tramite');
            }

            $stmt = $this->db->prepare("SELECT id FROM application_notes WHERE id = ? AND application_id = ? AND COALESCE(visible_to_client, 0) = 1");
            $stmt->execute([$noteId, $id]);
            if (!$stmt->fetch()) {
                $_SESSION['error'] = 'La observación no está disponible para responder';
                $this->redirect('/mi-tramite/ver/' . $id);
            }

            $stmt = $this->db->prepare("\n                INSERT INTO application_note_responses (application_id, note_id, client_user_id, response_text)\n                VALUES (?, ?, ?, ?)\n            ");
            $stmt->execute([$id, $noteId, $_SESSION['user_id'], $responseText]);

            $this->db->prepare("\n                UPDATE applications\n                SET client_update_pending = 1, client_last_update_at = NOW(), client_last_update_comment = ?\n                WHERE id = ?\n            ")->execute(['Respuesta del cliente a una observación', $id]);

            logCustomerJourney($id, 'client_response', 'Cliente respondió observación', $responseText, 'portal');

            $_SESSION['success'] = 'Respuesta enviada al equipo';
            $this->redirect('/mi-tramite/ver/' . $id);
        } catch (PDOException $e) {
            error_log('Error al responder observación: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al enviar respuesta. Verifica que la migración esté aplicada.';
            $this->redirect('/mi-tramite/ver/' . $id);
        }
    }

    public function sendMessage($id) {
        $this->requireClient();

        $message = trim($_POST['message'] ?? '');
        if ($message === '') {
            $_SESSION['error'] = 'El mensaje no puede estar vacío';
            $this->redirect('/mi-tramite/ver/' . $id);
        }

        try {
            $application = $this->loadClientApplication($id);
            if (!$application) {
                $_SESSION['error'] = 'No tienes acceso a este trámite';
                $this->redirect('/mi-tramite');
            }

            $stmt = $this->db->prepare("\n                INSERT INTO client_messages (application_id, sender_user_id, sender_role, message, is_read_by_client, is_read_by_staff)\n                VALUES (?, ?, 'Cliente', ?, 1, 0)\n            ");
            $stmt->execute([$id, $_SESSION['user_id'], $message]);

            $this->db->prepare("\n                UPDATE applications\n                SET client_update_pending = 1, client_last_update_at = NOW(), client_last_update_comment = ?\n                WHERE id = ?\n            ")->execute(['Mensaje nuevo del cliente', $id]);

            logCustomerJourney($id, 'message', 'Mensaje del cliente', $message, 'portal', [
                'sender_role' => 'Cliente',
                'source' => 'client_portal'
            ]);

            $_SESSION['success'] = 'Mensaje enviado';
            $this->redirect('/mi-tramite/ver/' . $id);
        } catch (PDOException $e) {
            error_log('Error al enviar mensaje cliente: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al enviar mensaje. Verifica que la migración esté aplicada.';
            $this->redirect('/mi-tramite/ver/' . $id);
        }
    }

    public function submitForReview($id) {
        $this->requireClient();

        $comment = trim($_POST['comment'] ?? '');
        if ($comment === '') {
            $comment = 'El cliente solicita revisión de su trámite';
        }

        try {
            $application = $this->loadClientApplication($id);
            if (!$application) {
                $_SESSION['error'] = 'No tienes acceso a este trámite';
                $this->redirect('/mi-tramite');
            }

            $stmt = $this->db->prepare("\n                UPDATE applications\n                SET client_update_pending = 1, client_last_update_at = NOW(), client_last_update_comment = ?\n                WHERE id = ? AND client_user_id = ?\n            ");
            $stmt->execute([$comment, $id, $_SESSION['user_id']]);

            logCustomerJourney($id, 'client_portal', 'Cliente envió trámite a revisión', $comment, 'portal');

            $_SESSION['success'] = 'Tu trámite quedó marcado para revisión del equipo';
            $this->redirect('/mi-tramite/ver/' . $id);
        } catch (PDOException $e) {
            error_log('Error al enviar revisión cliente: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al enviar a revisión';
            $this->redirect('/mi-tramite/ver/' . $id);
        }
    }
}
