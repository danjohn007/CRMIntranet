<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class DashboardController extends BaseController {
    
    public function index() {
        $this->requireLogin();
        
        $role = $this->getUserRole();
        $userId = $_SESSION['user_id'];
        
        // Estadísticas generales
        $stats = [];
        
        try {
            // Total de solicitudes (según rol)
            if ($role === ROLE_ASESOR) {
                // Asesor solo ve solicitudes NO finalizadas
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as total FROM applications 
                    WHERE created_by = ? AND status != ?
                ");
                $stmt->execute([$userId, STATUS_FINALIZADO]);
            } else {
                // Admin y Gerente ven todas
                $stmt = $this->db->query("SELECT COUNT(*) as total FROM applications");
            }
            $stats['total_applications'] = $stmt->fetch()['total'];
            
            // Solicitudes por estatus
            if ($role === ROLE_ASESOR) {
                $stmt = $this->db->prepare("
                    SELECT status, COUNT(*) as count 
                    FROM applications 
                    WHERE created_by = ? AND status != ?
                    GROUP BY status
                ");
                $stmt->execute([$userId, STATUS_FINALIZADO]);
            } else {
                $stmt = $this->db->query("
                    SELECT status, COUNT(*) as count 
                    FROM applications 
                    GROUP BY status
                ");
            }
            $stats['by_status'] = $stmt->fetchAll();
            
            // Solicitudes recientes
            if ($role === ROLE_ASESOR) {
                $stmt = $this->db->prepare("
                    SELECT a.*, u.full_name as creator_name 
                    FROM applications a
                    LEFT JOIN users u ON a.created_by = u.id
                    WHERE a.created_by = ? AND a.status != ?
                    ORDER BY a.created_at DESC
                    LIMIT 10
                ");
                $stmt->execute([$userId, STATUS_FINALIZADO]);
            } else {
                $stmt = $this->db->query("
                    SELECT a.*, u.full_name as creator_name 
                    FROM applications a
                    LEFT JOIN users u ON a.created_by = u.id
                    ORDER BY a.created_at DESC
                    LIMIT 10
                ");
            }
            $stats['recent_applications'] = $stmt->fetchAll();
            
            // Estadísticas financieras (solo Admin y Gerente)
            if ($this->canAccessFinancial()) {
                $stmt = $this->db->query("
                    SELECT 
                        SUM(total_costs) as total_costs,
                        SUM(total_paid) as total_paid,
                        SUM(balance) as total_balance
                    FROM financial_status
                ");
                $stats['financial'] = $stmt->fetch();
                
                // Pagos recientes
                $stmt = $this->db->query("
                    SELECT p.*, a.folio 
                    FROM payments p
                    LEFT JOIN applications a ON p.application_id = a.id
                    ORDER BY p.payment_date DESC
                    LIMIT 5
                ");
                $stats['recent_payments'] = $stmt->fetchAll();
            }
            
        } catch (PDOException $e) {
            error_log("Error en dashboard: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar estadísticas';
        }
        
        $this->view('dashboard/index', ['stats' => $stats]);
    }
}
