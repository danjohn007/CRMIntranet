<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/controllers/Router.php';

// Inicializar Router
$router = new Router();

// Obtener la URI solicitada
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath !== '/') {
    $uri = substr($uri, strlen($basePath));
}
$uri = '/' . trim($uri, '/');

// Ejecutar la ruta
$router->dispatch($uri);
