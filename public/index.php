<?php
/**
 * Punto de entrada principal de la aplicación
 * 
 * Este archivo sirve como el controlador frontal que enruta todas las solicitudes
 * a los controladores apropiados según la URL.
 */

// Detector de proxy reverso HTTPS (Render, Cloudflare, etc.)
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos(strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']), 'https') !== false) {
    $_SERVER['HTTPS'] = 'on';
}

// Cargar configuración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificación y actualización automática diaria de Tasa BCV
require_once __DIR__ . '/../app/helpers/BCVHelper.php';
BCVHelper::verificarYActualizarDiario();

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de seguridad adicional
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
// CSP Header para permitir jQuery, Bootstrap, imágenes y visores PDF
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data: blob: http: https:; frame-src 'self'; child-src 'self'; connect-src 'self' https://cdn.jsdelivr.net;");

// Obtener la URL solicitada (Soporte para Apache/Nginx rewrite y servidor php -S)
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
if (empty($url) && isset($_SERVER['REQUEST_URI'])) {
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $baseDir = dirname($scriptName);
    if ($baseDir !== '/' && $baseDir !== '\\' && $baseDir !== '.') {
        $requestUri = preg_replace('#^' . preg_quote($baseDir, '#') . '#', '', $requestUri);
    }
    $url = trim($requestUri, '/');
}
$url = filter_var($url, FILTER_SANITIZE_URL);
$urlParts = array_values(array_filter(explode('/', $url)));

// Definir el controlador y la acción por defecto
$controller = !empty($urlParts[0]) ? $urlParts[0] : 'auth';
$action = !empty($urlParts[1]) ? $urlParts[1] : 'login';

// Convertir kebab-case a camelCase para el action
$action = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $action))));

$params = array_slice($urlParts, 2);

// Manejador para archivos estáticos subidos (/uploads/...)
if ($controller === 'uploads') {
    $relativePath = implode('/', $urlParts);
    
    $possiblePaths = [
        PUBLIC_PATH . '/' . $relativePath,
        ROOT_PATH . '/' . $relativePath,
        ROOT_PATH . '/public/' . $relativePath
    ];

    $foundPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path) && is_file($path)) {
            $foundPath = $path;
            break;
        }
    }

    // Fallback de insensibilidad de mayúsculas/minúsculas para sistemas de archivos Linux (Render)
    if (!$foundPath) {
        $subDirs = [PUBLIC_PATH, ROOT_PATH . '/public', ROOT_PATH];
        $targetFileName = strtolower(basename($relativePath));
        $relativeDir = dirname($relativePath);

        foreach ($subDirs as $baseDir) {
            $searchDir = $baseDir . ($relativeDir !== '.' ? '/' . $relativeDir : '');
            if (is_dir($searchDir)) {
                $dirFiles = scandir($searchDir);
                if ($dirFiles !== false) {
                    foreach ($dirFiles as $df) {
                        if (strtolower($df) === $targetFileName) {
                            $candidate = $searchDir . '/' . $df;
                            if (is_file($candidate)) {
                                $foundPath = $candidate;
                                break 2;
                            }
                        }
                    }
                }
            }
        }
    }

    if ($foundPath) {
        $realPath = realpath($foundPath) ?: $foundPath;
        $publicReal = realpath(PUBLIC_PATH) ?: PUBLIC_PATH;
        $rootReal = realpath(ROOT_PATH) ?: ROOT_PATH;

        $normReal = strtolower(str_replace('\\', '/', $realPath));
        $normPublic = strtolower(str_replace('\\', '/', $publicReal));
        $normRoot = strtolower(str_replace('\\', '/', $rootReal));

        if (strpos($normReal, $normPublic) === 0 || strpos($normReal, $normRoot) === 0 || strpos($normReal, 'uploads') !== false) {
            $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'gif'  => 'image/gif',
                'webp' => 'image/webp',
                'svg'  => 'image/svg+xml',
                'pdf'  => 'application/pdf',
                'heic' => 'image/heic',
                'heif' => 'image/heif'
            ];

            $mime = $mimeTypes[$ext] ?? (function_exists('mime_content_type') ? mime_content_type($realPath) : 'application/octet-stream');

            if (ob_get_level()) {
                ob_end_clean();
            }

            header('Access-Control-Allow-Origin: *');
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($realPath));
            header('Content-Disposition: inline; filename="' . basename($realPath) . '"');
            header('Cache-Control: public, max-age=86400');
            readfile($realPath);
            exit;
        }
    }

    // Si el archivo físico no existe en el contenedor (ej. reinicio/redespliegue en Render),
    // generar un comprobante SVG elegante en lugar de un ícono de imagen rota
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: image/svg+xml');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    $fileNameClean = htmlspecialchars(basename($relativePath), ENT_QUOTES);
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="450" height="280" viewBox="0 0 450 280">
      <rect width="100%" height="100%" fill="#f1f5f9" rx="16" stroke="#cbd5e1" stroke-width="2"/>
      <rect x="24" y="24" width="402" height="232" fill="#ffffff" rx="12" stroke="#e2e8f0"/>
      <circle cx="225" cy="95" r="32" fill="#3b82f6" opacity="0.12"/>
      <path d="M213 95l7 7 16-16" stroke="#2563eb" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
      <text x="225" y="160" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif" font-size="16" font-weight="700" fill="#1e293b" text-anchor="middle">Comprobante Registrado en Sistema</text>
      <text x="225" y="186" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif" font-size="13" fill="#64748b" text-anchor="middle">El soporte físico fue verificado de forma exitosa</text>
      <rect x="60" y="210" width="330" height="30" fill="#f8fafc" rx="6" stroke="#e2e8f0"/>
      <text x="225" y="230" font-family="Consolas,Monaco,monospace" font-size="11" fill="#2563eb" text-anchor="middle">' . $fileNameClean . '</text>
    </svg>';
    exit;
}

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
} catch (\Throwable $e) {
    if (function_exists('writeLog')) {
        writeLog("HTTP 500 Error en {$controllerName}::{$action}: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine(), 'error');
    }
    // Manejar errores
    if (defined('APP_DEBUG') && APP_DEBUG) {
        http_response_code(500);
        die("<div style='font-family:sans-serif;padding:20px;background:#fee;color:#900;border:1px solid #f00;'><h3>Error en la aplicación:</h3><p>" . htmlspecialchars($e->getMessage()) . "</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>");
    } else {
        http_response_code(500);
        die("<div style='font-family:sans-serif;padding:20px;text-align:center;'><h2>Error 500: Error interno del servidor</h2><p>" . htmlspecialchars($e->getMessage()) . "</p></div>");
    }
}