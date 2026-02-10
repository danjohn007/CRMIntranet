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
                // Asesor solo ve solicitudes NO finalizadas ni rechazadas
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as total FROM applications 
                    WHERE status NOT IN (?, ?)
                ");
                $stmt->execute([STATUS_FINALIZADO, STATUS_RECHAZADO]);
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
                    WHERE status NOT IN (?, ?)
                    GROUP BY status
                ");
                $stmt->execute([STATUS_FINALIZADO, STATUS_RECHAZADO]);
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
                    WHERE a.status NOT IN (?, ?)
                    ORDER BY a.created_at DESC
                    LIMIT 10
                ");
                $stmt->execute([STATUS_FINALIZADO, STATUS_RECHAZADO]);
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
                
                // Datos para gráfica de pagos por método
                $stmt = $this->db->query("
                    SELECT payment_method, COUNT(*) as count, SUM(amount) as total
                    FROM payments
                    GROUP BY payment_method
                ");
                $stats['payments_by_method'] = $stmt->fetchAll();
            }
            
            // Datos para gráfica de solicitudes por mes (últimos 6 meses)
            if ($role === ROLE_ASESOR) {
                $stmt = $this->db->prepare("
                    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
                    FROM applications
                    WHERE status NOT IN (?, ?) 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY month
                ");
                $stmt->execute([STATUS_FINALIZADO, STATUS_RECHAZADO]);
            } else {
                $stmt = $this->db->query("
                    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
                    FROM applications
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY month
                ");
            }
            $stats['applications_by_month'] = $stmt->fetchAll();
            
            // Datos para gráfica de tipos de solicitudes
            if ($role === ROLE_ASESOR) {
                $stmt = $this->db->prepare("
                    SELECT type, COUNT(*) as count
                    FROM applications
                    WHERE status NOT IN (?, ?)
                    GROUP BY type
                ");
                $stmt->execute([STATUS_FINALIZADO, STATUS_RECHAZADO]);
            } else {
                $stmt = $this->db->query("
                    SELECT type, COUNT(*) as count
                    FROM applications
                    GROUP BY type
                ");
            }
            $stats['applications_by_type'] = $stmt->fetchAll();
            
            
        } catch (PDOException $e) {
            error_log("Error en dashboard: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar estadísticas';
        }
        
        $this->view('dashboard/index', ['stats' => $stats]);
    }
}
