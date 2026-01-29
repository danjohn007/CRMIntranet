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
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

// Roles del Sistema
define('ROLE_ADMIN', 'Administrador');
define('ROLE_GERENTE', 'Gerente');
define('ROLE_ASESOR', 'Asesor');

// Estatus de Solicitudes
define('STATUS_CREADO', 'Creado');
define('STATUS_EN_REVISION', 'En revisión');
define('STATUS_INFO_INCOMPLETA', 'Información incompleta');
define('STATUS_DOC_VALIDADA', 'Documentación validada');
define('STATUS_EN_PROCESO', 'En proceso');
define('STATUS_APROBADO', 'Aprobado');
define('STATUS_RECHAZADO', 'Rechazado');
define('STATUS_FINALIZADO', 'Finalizado');

// Estados Financieros
define('FINANCIAL_PENDIENTE', 'Pendiente');
define('FINANCIAL_PARCIAL', 'Parcial');
define('FINANCIAL_PAGADO', 'Pagado');
