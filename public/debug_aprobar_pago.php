<?php
/**
 * Script para depurar la aprobación de pagos
 */

// Cargar configuración y modelos
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Pago.php';
require_once __DIR__ . '/../app/models/Mensualidad.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/helpers/PDFHelper.php';
require_once __DIR__ . '/../app/helpers/QRHelper.php';

// Definir constantes si no existen (para evitar errores si config.php no las define todas)
if (!defined('APP_DEBUG')) define('APP_DEBUG', true);

echo "<h1>Debug: Aprobación de Pago #15</h1>";
echo "<hr>";

try {
    $pagoId = 15;
    $usuarioId = 4; // ID del operador (asumiendo que es 4 según logs anteriores)
    
    echo "<p>Buscando pago ID: $pagoId...</p>";
    $pago = Pago::findById($pagoId);
    
    if (!$pago) {
        die("❌ Error: Pago no encontrado");
    }
    
    echo "<p>✅ Pago encontrado. Estado actual: <strong>{$pago->estado_comprobante}</strong></p>";
    echo "<pre>" . print_r($pago, true) . "</pre>";
    
    if ($pago->estado_comprobante !== 'pendiente') {
        echo "<p>⚠️ El pago no está pendiente. Forzando estado a 'pendiente' para prueba...</p>";
        $sql = "UPDATE pagos SET estado_comprobante = 'pendiente' WHERE id = ?";
        Database::execute($sql, [$pagoId]);
        $pago->estado_comprobante = 'pendiente';
        echo "<p>✅ Estado actualizado a pendiente.</p>";
    }
    
    echo "<p>Intentando aprobar pago usando método del modelo...</p>";
    
    // Llamar al método aprobar directamente
    if ($pago->aprobar($usuarioId)) {
        echo "<h2>🎉 ¡Aprobación exitosa!</h2>";
        echo "<p>El método aprobar() devolvió TRUE.</p>";
    } else {
        echo "<h2>❌ Falló la aprobación</h2>";
        echo "<p>El método aprobar() devolvió FALSE.</p>";
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
