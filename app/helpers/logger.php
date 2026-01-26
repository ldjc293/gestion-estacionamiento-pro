<?php
/**
 * Logging Helper Functions
 */

require_once __DIR__ . '/../../config/constants.php';

/**
 * Write a log message to the application log
 *
 * @param string $message Log message
 * @param string $level Log level (info, warning, error, debug)
 * @param string $category Log category
 * @return bool True if log was written successfully, false otherwise
 */
function writeLog(string $message, string $level = 'info', string $category = 'application'): bool
{
    try {
        $logDir = __DIR__ . '/../../logs';
        
        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = "[$timestamp] [$level] [$category] $message" . PHP_EOL;
        
        return file_put_contents($logFile, $logEntry, FILE_APPEND) !== false;
    } catch (Exception $e) {
        // If logging fails, we can't log the error (infinite loop)
        return false;
    }
}

/**
 * Log an info message
 *
 * @param string $message Log message
 * @param string $category Log category
 * @return bool True if log was written successfully, false otherwise
 */
function logInfo(string $message, string $category = 'application'): bool
{
    return writeLog($message, 'info', $category);
}

/**
 * Log a warning message
 *
 * @param string $message Log message
 * @param string $category Log category
 * @return bool True if log was written successfully, false otherwise
 */
function logWarning(string $message, string $category = 'application'): bool
{
    return writeLog($message, 'warning', $category);
}

/**
 * Log an error message
 *
 * @param string $message Log message
 * @param string $category Log category
 * @return bool True if log was written successfully, false otherwise
 */
function logError(string $message, string $category = 'application'): bool
{
    return writeLog($message, 'error', $category);
}

/**
 * Log a debug message
 *
 * @param string $message Log message
 * @param string $category Log category
 * @return bool True if log was written successfully, false otherwise
 */
function logDebug(string $message, string $category = 'application'): bool
{
    // Only log debug messages in development environment
    if (APP_ENV === 'development') {
        return writeLog($message, 'debug', $category);
    }
    
    return true;
}

/**
 * Log an exception
 *
 * @param Exception $exception Exception to log
 * @param string $category Log category
 * @return bool True if log was written successfully, false otherwise
 */
function logException(Exception $exception, string $category = 'application'): bool
{
    $message = sprintf(
        "Exception: %s in %s:%d\nStack trace:\n%s",
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    return writeLog($message, 'error', $category);
}