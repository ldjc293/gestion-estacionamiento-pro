<?php
/**
 * Script para depurar la aprobación de pagos (V3)
 */

// Cargar configuración y modelos
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Pago.php';
require_once __DIR__ . '/../app/models/Mensualidad.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/helpers/PDFHelper.php';
require_once __DIR__ . '/../app/helpers/QRHelper.php';

// Definir constantes si no existen
if (!defined('APP_DEBUG')) define('APP_DEBUG', true);

echo "<h1>Debug: Aprobación de Pago #15 (V3)</h1>";
echo "<hr>";

try {
    $pagoId = 15;
    $usuarioId = 4;
    
    echo "<p>Buscando pago ID: $pagoId...</p>";
    $pago = Pago::findById($pagoId);
    
    if (!$pago) {
        die("❌ Error: Pago no encontrado");
    }
    
    echo "<p>✅ Pago encontrado. Estado actual: <strong>{$pago->estado_comprobante}</strong></p>";
    
    // Verificar MailHelper
    if (class_exists('MailHelper')) {
        echo "<h3>Diagnóstico MailHelper:</h3>";
        $reflector = new ReflectionClass('MailHelper');
        $methods = $reflector->getMethods();
        echo "<ul>";
        foreach ($methods as $method) {
            echo "<li>" . $method->getName() . "</li>";
        }
        echo "</ul>";
        echo "<p>Archivo: " . $reflector->getFileName() . "</p>";
    } else {
        echo "<p>⚠️ MailHelper no está cargado.</p>";
        // Intentar cargar manualmente
        $mailHelperPath = __DIR__ . '/../app/helpers/MailHelper.php';
        if (file_exists($mailHelperPath)) {
            require_once $mailHelperPath;
            echo "<p>✅ MailHelper cargado manualmente desde $mailHelperPath</p>";
            
            // Verificar de nuevo
            if (class_exists('MailHelper')) {
                $reflector = new ReflectionClass('MailHelper');
                $methods = $reflector->getMethods();
                echo "<h4>Métodos después de cargar:</h4><ul>";
                foreach ($methods as $method) {
                    echo "<li>" . $method->getName() . "</li>";
                }
                echo "</ul>";
            }
        }
    }
    
    if ($pago->estado_comprobante !== 'pendiente') {
        echo "<p>⚠️ El pago no está pendiente. Forzando estado a 'pendiente' para prueba...</p>";
        $sql = "UPDATE pagos SET estado_comprobante = 'pendiente' WHERE id = ?";
        Database::execute($sql, [$pagoId]);
        $pago->estado_comprobante = 'pendiente';
        echo "<p>✅ Estado actualizado a pendiente.</p>";
    }
    
    echo "<p>Intentando aprobar pago usando método \$pago->aprobar($usuarioId)...</p>";
    
    // Llamar al método aprobar directamente
    if ($pago->aprobar($usuarioId)) {
        echo "<h2>🎉 ¡Aprobación exitosa!</h2>";
        echo "<p>El método aprobar() devolvió TRUE.</p>";
        
        // Verificar estado final
        $pagoFinal = Pago::findById($pagoId);
        echo "<p>Estado final en BD: <strong>{$pagoFinal->estado_comprobante}</strong></p>";
        
    } else {
        echo "<h2>❌ Falló la aprobación</h2>";
        echo "<p>El método aprobar() devolvió FALSE.</p>";
        
        // Ver si hay logs de error
        $logFile = __DIR__ . '/../logs/app.log';
        if (file_exists($logFile)) {
            echo "<h3>Últimos logs:</h3>";
            $logs = file($logFile);
            $lastLogs = array_slice($logs, -10);
            echo "<pre>" . implode("", $lastLogs) . "</pre>";
        }
    }
    
} catch (Throwable $e) {
    echo "<div style='background-color: #f8d7da; padding: 15px; border: 1px solid #dc3545; border-radius: 5px;'>";
    echo "<h3>❌ Error Fatal</h3>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>
