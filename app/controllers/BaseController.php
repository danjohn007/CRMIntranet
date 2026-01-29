<?php
require_once ROOT_PATH . '/config/database.php';

class BaseController {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    protected function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    protected function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }
    
    protected function requireRole($allowedRoles) {
        $this->requireLogin();
        
        if (!in_array($_SESSION['user_role'], $allowedRoles)) {
            http_response_code(403);
            die("Acceso denegado. No tiene permisos para acceder a esta sección.");
        }
    }
    
    protected function view($viewName, $data = []) {
        extract($data);
        $viewFile = ROOT_PATH . '/app/views/' . $viewName . '.php';
        
        if (!file_exists($viewFile)) {
            die("Vista no encontrada: $viewName");
        }
        
        require_once $viewFile;
    }
    
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    protected function redirect($url) {
        header('Location: ' . BASE_URL . $url);
        exit;
    }
    
    protected function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    protected function canAccessFinancial() {
        $role = $this->getUserRole();
        return in_array($role, [ROLE_ADMIN, ROLE_GERENTE]);
    }
    
    protected function canSeeFinalized() {
        $role = $this->getUserRole();
        return in_array($role, [ROLE_ADMIN, ROLE_GERENTE]);
    }
    
    protected function canChangeStatus() {
        $role = $this->getUserRole();
        return in_array($role, [ROLE_ADMIN, ROLE_GERENTE]);
    }
    
    protected function canManageForms() {
        return $this->getUserRole() === ROLE_ADMIN;
    }
}
