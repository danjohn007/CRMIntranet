<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class AuthController extends BaseController {
    
    public function login() {
        if ($this->isLoggedIn()) {
            $this->redirect('/dashboard');
        }
        
        $this->view('auth/login');
    }
    
    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $captcha = trim($_POST['captcha'] ?? '');
        
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Por favor, ingrese usuario y contraseña';
            $this->redirect('/login');
        }
        
        // Validate captcha
        if (empty($captcha) || !isset($_SESSION['captcha_answer'])) {
            $_SESSION['error'] = 'Por favor, complete la verificación humana';
            unset($_SESSION['captcha_answer'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2']);
            $this->redirect('/login');
        }
        
        if ((int)$captcha !== (int)$_SESSION['captcha_answer']) {
            $_SESSION['error'] = 'Verificación humana incorrecta. Por favor, intente nuevamente';
            unset($_SESSION['captcha_answer'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2']);
            $this->redirect('/login');
        }
        
        // Clear captcha after successful validation
        unset($_SESSION['captcha_answer'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2']);
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, password, full_name, role, is_active 
                FROM users 
                WHERE (username = :username OR email = :email) AND is_active = 1
            ");
            $stmt->execute(['username' => $username, 'email' => $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Registrar último acceso
                $stmt = $this->db->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                $this->redirect('/dashboard');
            } else {
                $_SESSION['error'] = 'Usuario o contraseña incorrectos';
                $this->redirect('/login');
            }
        } catch (PDOException $e) {
            error_log("Error en autenticación: " . $e->getMessage());
            $_SESSION['error'] = 'Error al iniciar sesión. Por favor, intente nuevamente.';
            $this->redirect('/login');
        }
    }
    
    public function logout() {
        session_destroy();
        $this->redirect('/login');
    }
}
