<?php
/**
 * Punto de entrada principal de la aplicación
 * 
 * Este archivo sirve como el controlador frontal que enruta todas las solicitudes
 * a los controladores apropiados según la URL.
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Configuración de seguridad adicional
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
// CSP Header para permitir jQuery y Bootstrap
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self' https://cdn.jsdelivr.net;");

// Obtener la URL solicitada
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
$url = filter_var($url, FILTER_SANITIZE_URL);
$urlParts = explode('/', $url);

// Definir el controlador y la acción por defecto
$controller = !empty($urlParts[0]) ? $urlParts[0] : 'auth';
$action = !empty($urlParts[1]) ? $urlParts[1] : 'login';

// Convertir kebab-case a camelCase para el action
$action = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $action))));

$params = array_slice($urlParts, 2);

// Mapeo de controladores
$controllerMap = [
    'auth' => 'AuthController',
    'admin' => 'AdminController',
    'cliente' => 'ClienteController',
    'operador' => 'OperadorController',
    'consultor' => 'ConsultorController',
    'api' => 'ApiController',
    'admin-solicitudes' => 'AdminSolicitudesController'
];

// Verificar si el controlador existe
if (!isset($controllerMap[$controller])) {
    // Si no existe, redirigir a la página de login
    header('Location: ' . url('auth/login'));
    exit;
}

$controllerName = $controllerMap[$controller];
$controllerFile = __DIR__ . "/../app/controllers/{$controllerName}.php";

// Verificar si el archivo del controlador existe
if (!file_exists($controllerFile)) {
    // Si no existe, mostrar error 404
    http_response_code(404);
    echo "Error 404: Controlador no encontrado";
    exit;
}

// Incluir el controlador
require_once $controllerFile;

// Verificar si la clase del controlador existe
if (!class_exists($controllerName)) {
    http_response_code(500);
    die("Error: La clase del controlador {$controllerName} no existe.");
}

// Crear instancia del controlador
$controllerInstance = new $controllerName();

// Verificar si el método existe
if (!method_exists($controllerInstance, $action)) {
    http_response_code(404);
    echo "Error 404: Método no encontrado";
    exit;
}

// Llamar al método del controlador con los parámetros
try {
    call_user_func_array([$controllerInstance, $action], $params);
} catch (Exception $e) {
    // Manejar errores
    if (APP_DEBUG) {
        die("Error: " . $e->getMessage());
    } else {
        http_response_code(500);
        echo "Error 500: Error interno del servidor";
        exit;
    }
}