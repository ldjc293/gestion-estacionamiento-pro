<?php
/**
 * Application Constants
 */

// Verificar si las constantes ya están definidas antes de definirlas
if (!defined('MAX_LOGIN_ATTEMPTS')) {
    // Login security constants
    define('MAX_LOGIN_ATTEMPTS', 5);
    define('LOGIN_LOCKOUT_TIME', 15); // minutes
}

if (!defined('APP_NAME')) {
    // Application settings
    define('APP_NAME', 'Sistema de Control de Pagos de Estacionamiento');
    define('APP_URL', 'http://localhost:8080');
    define('APP_ENV', 'development');
}

if (!defined('SESSION_LIFETIME')) {
    // Session settings
    define('SESSION_LIFETIME', 7200); // 2 hours in seconds
}

// Password settings
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', false);

if (!defined('UPLOAD_MAX_SIZE')) {
    // File upload settings
    define('UPLOAD_MAX_SIZE', 5242880); // 5MB in bytes
    define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', __DIR__ . '/../public/uploads');
}

// Pagination settings
define('ITEMS_PER_PAGE', 20);

// Date formats
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd/m/Y');
define('DISPLAY_DATETIME_FORMAT', 'd/m/Y H:i');

// Currency settings
define('CURRENCY_CODE', 'USD');
define('CURRENCY_SYMBOL', '$');
define('DECIMAL_PLACES', 2);