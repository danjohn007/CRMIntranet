<?php
// Configuración automática de URL Base
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $dir = str_replace('\\', '/', dirname($script));
    $dir = $dir === '/' ? '' : $dir;
    return $protocol . '://' . $host . $dir;
}

define('BASE_URL', getBaseUrl());
define('ROOT_PATH', dirname(__DIR__));

// Configuración de Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'recursos_visas');
define('DB_USER', 'recursos_visas');
define('DB_PASS', '}hwFM2gahfZ%');
define('DB_CHARSET', 'utf8mb4');

// Configuración de Timezone
date_default_timezone_set('America/Mexico_City');

// Configuración de Sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Error Reporting (Solo en desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/error.log');

// Configuraciones Globales del Sistema (se cargarán de BD)
define('SITE_NAME', 'CRM Visas y Pasaportes');
define('ITEMS_PER_PAGE', 20);
define('MAX_FILE_SIZE', 2097152); // 2MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

// Roles del Sistema
define('ROLE_ADMIN', 'Administrador');
define('ROLE_GERENTE', 'Gerente');
define('ROLE_ASESOR', 'Asesor');

// Estatus de Solicitudes (sistema de colores)
define('STATUS_NUEVO', 'Nuevo');                          // GRIS - recién creada
define('STATUS_LISTO_SOLICITUD', 'Listo para solicitud'); // ROJO - info+cuestionario+docs base
define('STATUS_EN_ESPERA_PAGO', 'En espera de pago consular'); // AMARILLO - solicitud oficial hecha
define('STATUS_CITA_PROGRAMADA', 'Cita programada');      // AZUL - pago+citas+docs finales
define('STATUS_EN_ESPERA_RESULTADO', 'En espera de resultado'); // MORADO - cliente en embajada
define('STATUS_TRAMITE_CERRADO', 'Trámite cerrado');      // VERDE - visa recibida/cerrado

// Estatus legacy (compatibilidad)
define('STATUS_FORMULARIO_RECIBIDO', 'Formulario recibido');
define('STATUS_PAGO_VERIFICADO', 'Pago verificado');
define('STATUS_EN_ELABORACION_HOJA', 'En elaboración de hoja de información');
define('STATUS_EN_REVISION', 'En revisión');
define('STATUS_RECHAZADO', 'Rechazado (requiere corrección)');
define('STATUS_APROBADO', 'Aprobado');
define('STATUS_CITA_SOLICITADA', 'Cita solicitada');
define('STATUS_CITA_CONFIRMADA', 'Cita confirmada');
define('STATUS_PROCESO_EMBAJADA', 'Proceso en embajada');
define('STATUS_FINALIZADO', 'Finalizado');

// Estados Financieros
define('FINANCIAL_PENDIENTE', 'Pendiente');
define('FINANCIAL_PARCIAL', 'Parcial');
define('FINANCIAL_PAGADO', 'Pagado');

// Load helper functions
require_once ROOT_PATH . '/config/helpers.php';
