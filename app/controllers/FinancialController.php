<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class FinancialController extends BaseController {
    
    public function index() {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        try {
            // Obtener resumen financiero
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total_applications,
                    SUM(total_costs) as total_costs,
                    SUM(total_paid) as total_paid,
                    SUM(balance) as total_balance,
                    SUM(CASE WHEN status = 'Pendiente' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'Parcial' THEN 1 ELSE 0 END) as partial_count,
                    SUM(CASE WHEN status = 'Pagado' THEN 1 ELSE 0 END) as paid_count
                FROM financial_status
            ");
            $summary = $stmt->fetch();
            
            // Obtener solicitudes con información financiera
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM applications");
            $total = $stmt->fetch()['total'];
            
            $stmt = $this->db->prepare("
                SELECT a.*, u.full_name as creator_name,
                       fs.total_costs, fs.total_paid, fs.balance, fs.status as financial_status
                FROM applications a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN financial_status fs ON a.id = fs.application_id
                ORDER BY a.created_at DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute();
            $applications = $stmt->fetchAll();
            
            $totalPages = ceil($total / $limit);
            
            $this->view('financial/index', [
                'summary' => $summary,
                'applications' => $applications,
                'page' => $page,
                'totalPages' => $totalPages
            ]);
            
        } catch (PDOException $e) {
            error_log("Error en módulo financiero: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar información financiera';
            $this->redirect('/dashboard');
        }
    }
    
    public function show($id) {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);
        
        try {
            // Obtener solicitud con info financiera
            $stmt = $this->db->prepare("
                SELECT a.*, u.full_name as creator_name,
                       fs.total_costs, fs.total_paid, fs.balance, fs.status as financial_status
                FROM applications a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN financial_status fs ON a.id = fs.application_id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $application = $stmt->fetch();
            
            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/financiero');
            }
            
            // Obtener costos
            $stmt = $this->db->prepare("
                SELECT fc.*, u.full_name as created_by_name
                FROM financial_costs fc
                LEFT JOIN users u ON fc.created_by = u.id
                WHERE fc.application_id = ?
                ORDER BY fc.created_at DESC
            ");
            $stmt->execute([$id]);
            $costs = $stmt->fetchAll();
            
            // Obtener pagos
            $stmt = $this->db->prepare("
                SELECT p.*, u.full_name as registered_by_name
                FROM payments p
                LEFT JOIN users u ON p.registered_by = u.id
                WHERE p.application_id = ?
                ORDER BY p.payment_date DESC
            ");
            $stmt->execute([$id]);
            $payments = $stmt->fetchAll();
            
            $this->view('financial/show', [
                'application' => $application,
                'costs' => $costs,
                'payments' => $payments
            ]);
            
        } catch (PDOException $e) {
            error_log("Error al ver detalles financieros: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar información';
            $this->redirect('/financiero');
        }
    }
    
    public function addCost($id) {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/financiero/solicitud/' . $id);
        }
        
        $concept = trim($_POST['concept'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        
        if (empty($concept) || $amount <= 0) {
            $_SESSION['error'] = 'Concepto y monto son obligatorios';
            $this->redirect('/financiero/solicitud/' . $id);
        }
        
        try {
            // Agregar costo
            $stmt = $this->db->prepare("
                INSERT INTO financial_costs (application_id, concept, amount, created_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$id, $concept, $amount, $_SESSION['user_id']]);
            
            // Actualizar totales
            $this->updateFinancialStatus($id);
            
            $_SESSION['success'] = 'Costo agregado correctamente';
            $this->redirect('/financiero/solicitud/' . $id);
            
        } catch (PDOException $e) {
            error_log("Error al agregar costo: " . $e->getMessage());
            $_SESSION['error'] = 'Error al agregar costo';
            $this->redirect('/financiero/solicitud/' . $id);
        }
    }
    
    public function registerPayment($id) {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/financiero/solicitud/' . $id);
        }
        
        $amount = floatval($_POST['amount'] ?? 0);
        $method = trim($_POST['payment_method'] ?? '');
        $reference = trim($_POST['reference'] ?? '');
        $date = $_POST['payment_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');
        
        if ($amount <= 0 || empty($method)) {
            $_SESSION['error'] = 'Monto y método de pago son obligatorios';
            $this->redirect('/financiero/solicitud/' . $id);
        }
        
        try {
            // Registrar pago
            $stmt = $this->db->prepare("
                INSERT INTO payments (application_id, amount, payment_method, reference, notes, registered_by, payment_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id, $amount, $method, $reference, $notes, $_SESSION['user_id'], $date]);
            
            // Actualizar totales
            $this->updateFinancialStatus($id);
            
            $_SESSION['success'] = 'Pago registrado correctamente';
            $this->redirect('/financiero/solicitud/' . $id);
            
        } catch (PDOException $e) {
            error_log("Error al registrar pago: " . $e->getMessage());
            $_SESSION['error'] = 'Error al registrar pago';
            $this->redirect('/financiero/solicitud/' . $id);
        }
    }
    
    private function updateFinancialStatus($applicationId) {
        // Calcular total de costos
        $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM financial_costs WHERE application_id = ?");
        $stmt->execute([$applicationId]);
        $totalCosts = $stmt->fetch()['total'] ?? 0;
        
        // Calcular total pagado
        $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM payments WHERE application_id = ?");
        $stmt->execute([$applicationId]);
        $totalPaid = $stmt->fetch()['total'] ?? 0;
        
        // Calcular balance
        $balance = $totalCosts - $totalPaid;
        
        // Determinar estatus
        if ($totalPaid == 0) {
            $status = FINANCIAL_PENDIENTE;
        } elseif ($balance > 0) {
            $status = FINANCIAL_PARCIAL;
        } else {
            $status = FINANCIAL_PAGADO;
        }
        
        // Actualizar o crear registro
        $stmt = $this->db->prepare("
            INSERT INTO financial_status (application_id, total_costs, total_paid, balance, status)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            total_costs = VALUES(total_costs),
            total_paid = VALUES(total_paid),
            balance = VALUES(balance),
            status = VALUES(status)
        ");
        $stmt->execute([$applicationId, $totalCosts, $totalPaid, $balance, $status]);
    }
}
