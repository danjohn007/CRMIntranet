<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class AuditController extends BaseController {
    
    public function index() {
        $this->requireRole([ROLE_ADMIN]);
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 30;
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $userId = $_GET['user_id'] ?? '';
        $action = $_GET['action'] ?? '';
        $module = $_GET['module'] ?? '';
        
        // Construir consulta con filtros
        $where = ["DATE(created_at) BETWEEN ? AND ?"];
        $params = [$startDate, $endDate];
        $paramTypes = 'ss';
        
        if (!empty($userId) && $userId !== 'all') {
            $where[] = "user_id = ?";
            $params[] = $userId;
            $paramTypes .= 'i';
        }
        
        if (!empty($action)) {
            $where[] = "action LIKE ?";
            $params[] = "%$action%";
            $paramTypes .= 's';
        }
        
        if (!empty($module)) {
            $where[] = "module = ?";
            $params[] = $module;
            $paramTypes .= 's';
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        // Contar total de registros
        $countQuery = "SELECT COUNT(*) as total FROM audit_trail $whereClause";
        $stmt = $this->db->prepare($countQuery);
        if (!empty($params)) {
            $stmt->bind_param($paramTypes, ...$params);
        }
        $stmt->execute();
        $totalResult = $stmt->get_result()->fetch_assoc();
        $total = $totalResult['total'];
        
        // Obtener registros de auditoría
        $offset = ($page - 1) * $perPage;
        $query = "SELECT * FROM audit_trail 
                  $whereClause 
                  ORDER BY created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($query);
        $params[] = $perPage;
        $params[] = $offset;
        $paramTypes .= 'ii';
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $auditLogs = [];
        while ($row = $result->fetch_assoc()) {
            $auditLogs[] = $row;
        }
        
        // Obtener usuarios para filtro
        $usersQuery = "SELECT id, full_name, email FROM users WHERE is_active = 1 ORDER BY full_name";
        $usersResult = $this->db->query($usersQuery);
        $users = [];
        while ($row = $usersResult->fetch_assoc()) {
            $users[] = $row;
        }
        
        // Obtener módulos únicos para filtro
        $modulesQuery = "SELECT DISTINCT module FROM audit_trail ORDER BY module";
        $modulesResult = $this->db->query($modulesQuery);
        $modules = [];
        while ($row = $modulesResult->fetch_assoc()) {
            $modules[] = $row['module'];
        }
        
        // Obtener estadísticas del período
        $statsQuery = "SELECT 
                        COUNT(*) as total_records,
                        COUNT(DISTINCT user_id) as active_users,
                        COUNT(DISTINCT DATE(created_at)) as days_with_activity
                       FROM audit_trail 
                       $whereClause";
        $stmt = $this->db->prepare($statsQuery);
        if (!empty($params)) {
            // Remove last two params (LIMIT and OFFSET)
            array_pop($params);
            array_pop($params);
            $paramTypes = substr($paramTypes, 0, -2);
            $stmt->bind_param($paramTypes, ...$params);
        }
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        
        $totalPages = ceil($total / $perPage);
        
        $this->view('audit/index', [
            'auditLogs' => $auditLogs,
            'users' => $users,
            'modules' => $modules,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'userId' => $userId,
            'action' => $action,
            'module' => $module,
            'stats' => $stats
        ]);
    }
}
