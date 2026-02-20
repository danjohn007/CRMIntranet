<?php
class Router {
    private $routes = [];
    
    public function __construct() {
        $this->defineRoutes();
    }
    
    private function defineRoutes() {
        // Rutas de Autenticación
        $this->routes['GET']['/'] = ['AuthController', 'login'];
        $this->routes['GET']['/login'] = ['AuthController', 'login'];
        $this->routes['POST']['/login'] = ['AuthController', 'authenticate'];
        $this->routes['GET']['/logout'] = ['AuthController', 'logout'];
        
        // Dashboard
        $this->routes['GET']['/dashboard'] = ['DashboardController', 'index'];
        
        // Usuarios
        $this->routes['GET']['/usuarios'] = ['UserController', 'index'];
        $this->routes['GET']['/usuarios/crear'] = ['UserController', 'create'];
        $this->routes['POST']['/usuarios/guardar'] = ['UserController', 'store'];
        $this->routes['GET']['/usuarios/editar/{id}'] = ['UserController', 'edit'];
        $this->routes['POST']['/usuarios/actualizar/{id}'] = ['UserController', 'update'];
        $this->routes['POST']['/usuarios/eliminar/{id}'] = ['UserController', 'delete'];
        
        // Constructor de Formularios
        $this->routes['GET']['/formularios'] = ['FormController', 'index'];
        $this->routes['GET']['/formularios/crear'] = ['FormController', 'create'];
        $this->routes['POST']['/formularios/guardar'] = ['FormController', 'store'];
        $this->routes['GET']['/formularios/editar/{id}'] = ['FormController', 'edit'];
        $this->routes['POST']['/formularios/actualizar/{id}'] = ['FormController', 'update'];
        $this->routes['POST']['/formularios/eliminar/{id}'] = ['FormController', 'delete'];
        $this->routes['POST']['/formularios/publicar/{id}'] = ['FormController', 'publish'];
        
        $this->routes['POST']['/solicitudes/guardar-hoja-info/{id}'] = ['ApplicationController', 'saveInfoSheet'];
        $this->routes['POST']['/solicitudes/marcar-asistencia/{id}'] = ['ApplicationController', 'markClientAttended'];
        $this->routes['POST']['/solicitudes/vincular-formulario/{id}'] = ['ApplicationController', 'linkForm'];
        
        // Solicitudes
        $this->routes['GET']['/solicitudes'] = ['ApplicationController', 'index'];
        $this->routes['GET']['/solicitudes/crear'] = ['ApplicationController', 'create'];
        $this->routes['POST']['/solicitudes/crear'] = ['ApplicationController', 'store'];
        $this->routes['GET']['/solicitudes/ver/{id}'] = ['ApplicationController', 'show'];
        $this->routes['POST']['/solicitudes/cambiar-estatus/{id}'] = ['ApplicationController', 'changeStatus'];
        $this->routes['POST']['/solicitudes/subir-documento/{id}'] = ['ApplicationController', 'uploadDocument'];
        $this->routes['POST']['/solicitudes/agregar-indicacion/{id}'] = ['ApplicationController', 'addNote'];
        $this->routes['GET']['/solicitudes/descargar-archivo/{id}/{fieldId}'] = ['ApplicationController', 'downloadFormFile'];
        $this->routes['GET']['/solicitudes/descargar-documento/{docId}'] = ['ApplicationController', 'downloadDocument'];
        $this->routes['POST']['/solicitudes/eliminar/{id}'] = ['ApplicationController', 'delete'];
        
        // Módulo Financiero
        $this->routes['GET']['/financiero'] = ['FinancialController', 'index'];
        $this->routes['GET']['/financiero/solicitud/{id}'] = ['FinancialController', 'show'];
        $this->routes['POST']['/financiero/agregar-costo/{id}'] = ['FinancialController', 'addCost'];
        $this->routes['POST']['/financiero/registrar-pago/{id}'] = ['FinancialController', 'registerPayment'];
        
        // Reportes
        $this->routes['GET']['/reportes'] = ['ReportController', 'index'];
        $this->routes['GET']['/reportes/exportar'] = ['ReportController', 'export'];
        
        // Configuración
        $this->routes['GET']['/configuracion'] = ['ConfigController', 'index'];
        $this->routes['POST']['/configuracion/guardar'] = ['ConfigController', 'save'];
        
        // Logs de Errores
        $this->routes['GET']['/logs'] = ['LogController', 'index'];
        $this->routes['POST']['/logs/limpiar'] = ['LogController', 'clear'];
        $this->routes['GET']['/logs/descargar'] = ['LogController', 'download'];
        
        // Auditoría
        $this->routes['GET']['/auditoria'] = ['AuditController', 'index'];
        
        // Customer Journey
        $this->routes['GET']['/customer-journey/{id}'] = ['CustomerJourneyController', 'show'];
        $this->routes['POST']['/customer-journey/agregar/{id}'] = ['CustomerJourneyController', 'addTouchpoint'];
        
        // Public Forms (no authentication required)
        $this->routes['GET']['/public/form/{token}'] = ['PublicFormController', 'show'];
        $this->routes['POST']['/public/form/{token}/submit'] = ['PublicFormController', 'submit'];

        // Public solicitudes (for asesoras confirming appointments)
        $this->routes['GET']['/public/solicitudes'] = ['ApplicationController', 'publicSolicitudes'];
        $this->routes['POST']['/solicitudes/confirmar-cita/{id}'] = ['ApplicationController', 'confirmAppointment'];
        
        // Test de Conexión
        $this->routes['GET']['/test-conexion'] = ['TestController', 'connection'];
    }
    
    public function dispatch($uri) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Buscar ruta exacta
        if (isset($this->routes[$method][$uri])) {
            $this->callController($this->routes[$method][$uri]);
            return;
        }
        
        // Buscar ruta con parámetros
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $route);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remover el match completo
                $this->callController($handler, $matches);
                return;
            }
        }
        
        // Ruta no encontrada
        http_response_code(404);
        echo "404 - Página no encontrada";
    }
    
    private function callController($handler, $params = []) {
        list($controllerName, $method) = $handler;
        
        $controllerFile = ROOT_PATH . '/app/controllers/' . $controllerName . '.php';
        if (!file_exists($controllerFile)) {
            die("Controlador no encontrado: $controllerName");
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controllerName)) {
            die("Clase del controlador no encontrada: $controllerName");
        }
        
        $controller = new $controllerName();
        
        if (!method_exists($controller, $method)) {
            die("Método no encontrado: $method en $controllerName");
        }
        
        call_user_func_array([$controller, $method], $params);
    }
}
