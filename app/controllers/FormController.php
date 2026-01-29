<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class FormController extends BaseController {
    
    public function index() {
        $this->requireRole([ROLE_ADMIN]);
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        try {
            // Contar total
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM forms");
            $total = $stmt->fetch()['total'];
            
            // Obtener formularios
            $stmt = $this->db->prepare("
                SELECT f.*, u.full_name as creator_name
                FROM forms f
                LEFT JOIN users u ON f.created_by = u.id
                ORDER BY f.created_at DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute();
            $forms = $stmt->fetchAll();
            
            $totalPages = ceil($total / $limit);
            
            $this->view('forms/index', [
                'forms' => $forms,
                'page' => $page,
                'totalPages' => $totalPages,
                'total' => $total
            ]);
            
        } catch (PDOException $e) {
            error_log("Error al listar formularios: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar formularios';
            $this->view('forms/index', ['forms' => []]);
        }
    }
    
    public function create() {
        $this->requireRole([ROLE_ADMIN]);
        $this->view('forms/create');
    }
    
    public function store() {
        $this->requireRole([ROLE_ADMIN]);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/formularios');
        }
        
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? '';
        $subtype = trim($_POST['subtype'] ?? '');
        $fieldsJson = $_POST['fields_json'] ?? '';
        
        if (empty($name) || empty($type) || empty($fieldsJson)) {
            $_SESSION['error'] = 'Todos los campos obligatorios deben estar completos';
            $this->redirect('/formularios/crear');
        }
        
        // Validar JSON
        $fields = json_decode($fieldsJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $_SESSION['error'] = 'El JSON de campos no es válido';
            $this->redirect('/formularios/crear');
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO forms (name, description, type, subtype, fields_json, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name,
                $description,
                $type,
                $subtype,
                $fieldsJson,
                $_SESSION['user_id']
            ]);
            
            $_SESSION['success'] = 'Formulario creado exitosamente';
            $this->redirect('/formularios');
            
        } catch (PDOException $e) {
            error_log("Error al crear formulario: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear formulario';
            $this->redirect('/formularios/crear');
        }
    }
    
    public function edit($id) {
        $this->requireRole([ROLE_ADMIN]);
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM forms WHERE id = ?");
            $stmt->execute([$id]);
            $form = $stmt->fetch();
            
            if (!$form) {
                $_SESSION['error'] = 'Formulario no encontrado';
                $this->redirect('/formularios');
            }
            
            $this->view('forms/edit', ['form' => $form]);
            
        } catch (PDOException $e) {
            error_log("Error al cargar formulario: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar formulario';
            $this->redirect('/formularios');
        }
    }
    
    public function update($id) {
        $this->requireRole([ROLE_ADMIN]);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/formularios');
        }
        
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? '';
        $subtype = trim($_POST['subtype'] ?? '');
        $fieldsJson = $_POST['fields_json'] ?? '';
        
        if (empty($name) || empty($type) || empty($fieldsJson)) {
            $_SESSION['error'] = 'Todos los campos obligatorios deben estar completos';
            $this->redirect('/formularios/editar/' . $id);
        }
        
        // Validar JSON
        $fields = json_decode($fieldsJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $_SESSION['error'] = 'El JSON de campos no es válido';
            $this->redirect('/formularios/editar/' . $id);
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE forms 
                SET name = ?, description = ?, type = ?, subtype = ?, fields_json = ?, version = version + 1
                WHERE id = ?
            ");
            $stmt->execute([
                $name,
                $description,
                $type,
                $subtype,
                $fieldsJson,
                $id
            ]);
            
            $_SESSION['success'] = 'Formulario actualizado exitosamente';
            $this->redirect('/formularios');
            
        } catch (PDOException $e) {
            error_log("Error al actualizar formulario: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar formulario';
            $this->redirect('/formularios/editar/' . $id);
        }
    }
    
    public function delete($id) {
        $this->requireRole([ROLE_ADMIN]);
        
        try {
            // Verificar si hay solicitudes usando este formulario
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM applications WHERE form_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetch()['total'];
            
            if ($count > 0) {
                $_SESSION['error'] = 'No se puede eliminar el formulario porque tiene solicitudes asociadas';
                $this->redirect('/formularios');
            }
            
            $stmt = $this->db->prepare("DELETE FROM forms WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = 'Formulario eliminado exitosamente';
            $this->redirect('/formularios');
            
        } catch (PDOException $e) {
            error_log("Error al eliminar formulario: " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar formulario';
            $this->redirect('/formularios');
        }
    }
    
    public function publish($id) {
        $this->requireRole([ROLE_ADMIN]);
        
        try {
            // Obtener estado actual
            $stmt = $this->db->prepare("SELECT is_published FROM forms WHERE id = ?");
            $stmt->execute([$id]);
            $form = $stmt->fetch();
            
            if (!$form) {
                $_SESSION['error'] = 'Formulario no encontrado';
                $this->redirect('/formularios');
            }
            
            // Toggle estado
            $newStatus = $form['is_published'] ? 0 : 1;
            $stmt = $this->db->prepare("UPDATE forms SET is_published = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);
            
            $_SESSION['success'] = $newStatus ? 'Formulario publicado' : 'Formulario despublicado';
            $this->redirect('/formularios');
            
        } catch (PDOException $e) {
            error_log("Error al cambiar estado: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cambiar estado de publicación';
            $this->redirect('/formularios');
        }
    }
}
