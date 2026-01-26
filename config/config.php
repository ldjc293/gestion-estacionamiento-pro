<?php
/**
 * Configuración General del Sistema
 *
 * Centraliza todas las configuraciones de la aplicación
 * Lee valores del archivo .env y define constantes globales
 */

// Cargar autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar helpers
require_once __DIR__ . '/../app/helpers/DateHelper.php';

use Dotenv\Dotenv;

// Cargar variables de entorno
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} else {
    die("ERROR: Archivo .env no encontrado. Copie .env.example a .env y configure sus valores.");
}

// ============================================================================
// CONFIGURACIÓN DE PHP
// ============================================================================

// Configurar encoding UTF-8
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Establecer header Content-Type con UTF-8
header('Content-Type: text/html; charset=UTF-8');

// Zona horaria
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Caracas');

// Errores (solo en desarrollo)
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
}

// ============================================================================
// CONFIGURACIÓN DE SESIONES (ANTES de session_start)
// ============================================================================

// IMPORTANTE: Estas configuraciones deben hacerse ANTES de session_start()
// Si la sesión ya está iniciada, estas configuraciones no tendrán efecto
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_secure', '0'); // Cambiar a '1' si usa HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', ($_ENV['SESSION_LIFETIME'] ?? 30) * 60);
    ini_set('session.cookie_lifetime', ($_ENV['SESSION_LIFETIME'] ?? 30) * 60);
}

// Tamaño máximo de uploads
ini_set('upload_max_filesize', '5M');
ini_set('post_max_size', '6M');

// ============================================================================
// CONSTANTES DE APLICACIÓN
// ============================================================================

define('APP_NAME', $_ENV['APP_NAME'] ?? 'Sistema de Estacionamiento');
define('APP_VERSION', '1.0.0');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', ($_ENV['APP_DEBUG'] ?? 'false') === 'true');
define('APP_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost/controldepagosestacionamiento', '/'));
define('APP_KEY', $_ENV['APP_KEY'] ?? '');

// ============================================================================
// RUTAS DEL SISTEMA
// ============================================================================

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('COMPROBANTES_PATH', UPLOAD_PATH . '/comprobantes');
define('RECIBOS_PATH', UPLOAD_PATH . '/recibos');
define('LOGS_PATH', ROOT_PATH . '/logs');

// Crear directorios si no existen
if (!is_dir(COMPROBANTES_PATH)) mkdir(COMPROBANTES_PATH, 0755, true);
if (!is_dir(RECIBOS_PATH)) mkdir(RECIBOS_PATH, 0755, true);
if (!is_dir(LOGS_PATH)) mkdir(LOGS_PATH, 0755, true);

// ============================================================================
// CONFIGURACIÓN DE SEGURIDAD
// ============================================================================

define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 30)); // minutos
define('MAX_LOGIN_ATTEMPTS', (int)($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5));
define('LOGIN_LOCKOUT_TIME', (int)($_ENV['LOGIN_LOCKOUT_TIME'] ?? 30)); // minutos

// Recuperación de contraseña (User Story #6)
define('PASSWORD_RESET_CODE_EXPIRATION', (int)($_ENV['PASSWORD_RESET_CODE_EXPIRATION'] ?? 15)); // minutos
define('PASSWORD_RESET_RATE_LIMIT', (int)($_ENV['PASSWORD_RESET_RATE_LIMIT'] ?? 60)); // segundos
define('PASSWORD_RESET_MAX_ATTEMPTS', (int)($_ENV['PASSWORD_RESET_MAX_ATTEMPTS'] ?? 3));

// ============================================================================
// CONFIGURACIÓN DE UPLOADS
// ============================================================================

define('MAX_UPLOAD_SIZE', (int)($_ENV['MAX_UPLOAD_SIZE'] ?? 5242880)); // 5MB en bytes
define('ALLOWED_FORMATS', explode(',', $_ENV['ALLOWED_FORMATS'] ?? 'jpg,jpeg,png,pdf'));

// ============================================================================
// CONFIGURACIÓN DE NEGOCIO
// ============================================================================

define('TARIFA_MENSUAL_USD', (float)($_ENV['TARIFA_MENSUAL_USD'] ?? 1.00));
define('MONTO_RECONEXION_USD', (float)($_ENV['MONTO_RECONEXION_USD'] ?? 2.00));
define('DIAS_GRACIA', (int)($_ENV['DIAS_GRACIA'] ?? 5));
define('MESES_ALERTA', (int)($_ENV['MESES_ALERTA'] ?? 3));
define('MESES_BLOQUEO', (int)($_ENV['MESES_BLOQUEO'] ?? 4));

// Formato de recibos
define('RECIBO_PREFIX', $_ENV['RECIBO_PREFIX'] ?? 'EST-');
define('RECIBO_PADDING', (int)($_ENV['RECIBO_PADDING'] ?? 6));

// ============================================================================
// INFORMACIÓN DEL ESTACIONAMIENTO
// ============================================================================

define('ESTACIONAMIENTO_NOMBRE', $_ENV['ESTACIONAMIENTO_NOMBRE'] ?? 'Estacionamiento Bloques 27-32');
define('ESTACIONAMIENTO_DIRECCION', $_ENV['ESTACIONAMIENTO_DIRECCION'] ?? 'Caricuao Ud 5, Caracas, Venezuela');
define('ESTACIONAMIENTO_TELEFONO', $_ENV['ESTACIONAMIENTO_TELEFONO'] ?? '+58 424 1234567');
define('ESTACIONAMIENTO_EMAIL', $_ENV['ESTACIONAMIENTO_EMAIL'] ?? 'admin@estacionamiento.local');
define('TOTAL_POSICIONES', (int)($_ENV['TOTAL_POSICIONES'] ?? 250));
define('RECEPTORES', explode(',', $_ENV['RECEPTORES'] ?? 'A,B'));

// ============================================================================
// CONFIGURACIÓN DE EMAIL (PHPMailer)
// ============================================================================

define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
define('MAIL_PORT', (int)($_ENV['MAIL_PORT'] ?? 587));
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@estacionamiento.local');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'Estacionamiento');

// ============================================================================
// CONFIGURACIÓN DE GOOGLE SHEETS (Opcional)
// ============================================================================

define('GOOGLE_SHEETS_ENABLED', ($_ENV['GOOGLE_SHEETS_ENABLED'] ?? 'false') === 'true');
define('GOOGLE_SHEETS_ID', $_ENV['GOOGLE_SHEETS_ID'] ?? '');
define('GOOGLE_SHEETS_CREDENTIALS_PATH', ROOT_PATH . '/' . ($_ENV['GOOGLE_SHEETS_CREDENTIALS_PATH'] ?? 'config/google-credentials.json'));
define('GOOGLE_SHEETS_TAB_NAME', $_ENV['GOOGLE_SHEETS_TAB_NAME'] ?? 'Recibos');

// ============================================================================
// CONFIGURACIÓN DE API BCV
// ============================================================================

define('BCV_API_URL', $_ENV['BCV_API_URL'] ?? 'https://api.exchangerate.host/latest?base=USD&symbols=VES');
define('BCV_API_ENABLED', ($_ENV['BCV_API_ENABLED'] ?? 'true') === 'true');
define('BCV_AUTO_UPDATE', ($_ENV['BCV_AUTO_UPDATE'] ?? 'true') === 'true');

// ============================================================================
// CONFIGURACIÓN DE LOGS
// ============================================================================

define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'debug');
define('LOG_ROTATION_DAYS', (int)($_ENV['LOG_ROTATION_DAYS'] ?? 30));

// ============================================================================
// ROLES DEL SISTEMA
// ============================================================================

define('ROLES', [
    'cliente' => 'Cliente',
    'operador' => 'Operador',
    'consultor' => 'Consultor',
    'administrador' => 'Administrador'
]);

// ============================================================================
// PERMISOS POR ROL
// ============================================================================

define('PERMISSIONS', [
    'cliente' => [
        'view_own_estado_cuenta',
        'upload_comprobante',
        'view_own_historial',
        'update_own_profile',
        'create_solicitud',
    ],
    'operador' => [
        'view_all_estados_cuenta',
        'register_manual_payment',
        'approve_comprobante',
        'reject_comprobante',
        'approve_solicitud',
        'generate_recibo',
        'view_dashboard',
    ],
    'consultor' => [
        'view_all_estados_cuenta',
        'view_reportes',
        'export_excel',
        'export_pdf',
        'view_estadisticas',
        'view_dashboard',
    ],
    'administrador' => [
        'all', // Acceso completo a todas las funcionalidades
    ]
]);

// ============================================================================
// ESTADOS Y ENUMS
// ============================================================================

define('ESTADOS_CONTROL', [
    'activo' => 'Activo',
    'suspendido' => 'Suspendido',
    'desactivado' => 'Desactivado',
    'perdido' => 'Perdido',
    'bloqueado' => 'Bloqueado',
    'vacio' => 'Vacío'
]);

define('ESTADOS_MENSUALIDAD', [
    'pendiente' => 'Pendiente',
    'pagado' => 'Pagado',
    'vencido' => 'Vencido'
]);

define('MONEDAS_PAGO', [
    'usd_efectivo' => 'USD Efectivo',
    'bs_transferencia' => 'Bs Transferencia',
    'bs_efectivo' => 'Bs Efectivo'
]);

define('ESTADOS_COMPROBANTE', [
    'pendiente' => 'Pendiente',
    'aprobado' => 'Aprobado',
    'rechazado' => 'Rechazado',
    'no_aplica' => 'No Aplica'
]);

define('TIPOS_SOLICITUD', [
    'registro_nuevo_usuario' => 'Registro de Nuevo Usuario',
    'cambio_cantidad_controles' => 'Cambio de Cantidad de Controles',
    'suspension_control' => 'Suspensión de Control',
    'desactivacion_control' => 'Desactivación de Control'
]);

define('TIPOS_NOTIFICACION', [
    'alerta_3_meses' => 'Alerta 3 Meses',
    'alerta_bloqueo' => 'Alerta de Bloqueo',
    'comprobante_rechazado' => 'Comprobante Rechazado',
    'pago_aprobado' => 'Pago Aprobado',
    'solicitud_aprobada' => 'Solicitud Aprobada',
    'solicitud_rechazada' => 'Solicitud Rechazada',
    'bienvenida' => 'Bienvenida',
    'password_cambiado' => 'Contraseña Cambiada'
]);

// ============================================================================
// FUNCIONES HELPER GLOBALES
// ============================================================================

/**
 * Obtener URL completa del sistema
 *
 * @param string $path Ruta relativa
 * @return string URL completa
 */
function url(string $path = ''): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Redirigir a una URL
 *
 * @param string $path Ruta a redirigir
 */
function redirect(string $path = ''): void
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Verificar si el usuario tiene un permiso específico
 *
 * @param string $permission Permiso a verificar
 * @param string|null $rol Rol del usuario (null = usar sesión actual)
 * @return bool
 */
function hasPermission(string $permission, ?string $rol = null): bool
{
    $rol = $rol ?? ($_SESSION['user_rol'] ?? '');

    // Administrador tiene todos los permisos
    if ($rol === 'administrador') {
        return true;
    }

    $userPermissions = PERMISSIONS[$rol] ?? [];
    return in_array($permission, $userPermissions) || in_array('all', $userPermissions);
}

/**
 * Formatear moneda USD
 *
 * @param float $amount Monto
 * @return string Monto formateado
 */
function formatUSD(float $amount): string
{
    return '$' . number_format($amount, 2, '.', ',') . ' USD';
}

/**
 * Formatear moneda Bs
 *
 * @param float $amount Monto
 * @return string Monto formateado
 */
function formatBs(float $amount): string
{
    return 'Bs ' . number_format($amount, 2, ',', '.');
}

/**
 * Formatear fecha
 *
 * @param string $date Fecha en formato Y-m-d
 * @param string $format Formato de salida
 * @return string Fecha formateada
 */
function formatDate(string $date, string $format = 'd/m/Y'): string
{
    return date($format, strtotime($date));
}

/**
 * Formatear fecha y hora
 *
 * @param string $datetime Fecha y hora
 * @return string Fecha formateada
 */
function formatDateTime(string $datetime): string
{
    return date('d/m/Y H:i:s', strtotime($datetime));
}

/**
 * Sanitizar input del usuario
 *
 * @param string $input Input a sanitizar
 * @return string Input sanitizado
 */
function sanitize(string $input): string
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generar token CSRF
 *
 * @return string Token generado
 */
function generateCSRFToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 *
 * @param string $token Token a verificar
 * @return bool
 */
function verifyCSRFToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Escribir log
 *
 * @param string $message Mensaje
 * @param string $level Nivel (debug, info, warning, error)
 */
function writeLog(string $message, string $level = 'info'): void
{
    $logLevels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
    $currentLevel = $logLevels[LOG_LEVEL] ?? 0;
    $messageLevel = $logLevels[$level] ?? 1;

    if ($messageLevel >= $currentLevel) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents(LOGS_PATH . '/app.log', $logMessage, FILE_APPEND);
    }
}

/**
 * Generar número de recibo único
 *
 * @param int $numero Número secuencial
 * @return string Número de recibo formateado
 */
function generateReciboNumber(int $numero): string
{
    return RECIBO_PREFIX . str_pad($numero, RECIBO_PADDING, '0', STR_PAD_LEFT);
}

// ============================================================================
// INICIALIZACIÓN
// ============================================================================

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================================
// HEADERS DE SEGURIDAD HTTP
// ============================================================================

// Prevenir clickjacking (no permitir iframe)
header('X-Frame-Options: DENY');

// Prevenir MIME sniffing
header('X-Content-Type-Options: nosniff');

// Activar filtro XSS del navegador
header('X-XSS-Protection: 1; mode=block');

// Política de referrer
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy (CSP)
// Permitir recursos del mismo origen + CDNs específicos
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com; font-src 'self' cdn.jsdelivr.net fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'");

// Strict-Transport-Security (HSTS) - Solo activar si HTTPS está habilitado
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ============================================================================
// VERIFICACIÓN DE TIMEOUT DE SESIÓN
// ============================================================================

// Verificar timeout de sesión (solo para usuarios autenticados)
if (isset($_SESSION['user_id'])) {
    $sessionLifetime = SESSION_LIFETIME * 60; // Convertir minutos a segundos

    // Si es el primer acceso, guardar timestamp
    if (!isset($_SESSION['LAST_ACTIVITY'])) {
        $_SESSION['LAST_ACTIVITY'] = time();
        $_SESSION['CREATED_AT'] = time();
    }

    // Verificar si la sesión expiró por inactividad
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $sessionLifetime)) {
        // Sesión expirada por inactividad
        writeLog("Sesión expirada por inactividad para usuario ID: {$_SESSION['user_id']}", 'info');

        // Guardar mensaje antes de destruir sesión
        $timeoutMessage = "Su sesión expiró por inactividad. Por favor, inicie sesión nuevamente.";

        session_unset();
        session_destroy();
        session_start();

        $_SESSION['error'] = $timeoutMessage;

        // Redirigir al login
        header('Location: ' . url('auth/login?timeout=1'));
        exit();
    }

    // Actualizar timestamp de última actividad
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Log de inicialización (solo en debug)
if (APP_DEBUG) {
    writeLog("Configuración cargada correctamente. Entorno: " . APP_ENV, 'debug');
}
