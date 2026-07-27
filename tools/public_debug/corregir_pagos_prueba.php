<?php
/**
 * Script para corregir pagos de prueba con moneda_pago vacía
 */

require_once __DIR__ . '/../config/database.php';

echo "<h1>Corrigiendo Pagos de Prueba</h1>";
echo "<hr>";

try {
    // Actualizar pagos con moneda_pago vacía
    $sql = "UPDATE pagos 
            SET moneda_pago = 'bs_transferencia' 
            WHERE moneda_pago IS NULL OR moneda_pago = ''";
    
    $affected = Database::execute($sql);
    
    echo "<h2>✅ Corrección completada</h2>";
    echo "<p><strong>Pagos actualizados:</strong> $affected</p>";
    
    // Verificar
    $sql = "SELECT id, numero_recibo, moneda_pago, estado_comprobante 
            FROM pagos 
            WHERE estado_comprobante = 'pendiente'
            ORDER BY id DESC
            LIMIT 10";
    
    $pagos = Database::fetchAll($sql);
    
    echo "<h3>Pagos pendientes actualizados:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
    echo "<tr style='background-color:#f0f0f0;'>";
    echo "<th>ID</th><th>Recibo</th><th>Moneda Pago</th><th>Estado</th>";
    echo "</tr>";
    
    foreach ($pagos as $pago) {
        echo "<tr>";
        echo "<td>{$pago['id']}</td>";
        echo "<td>{$pago['numero_recibo']}</td>";
        echo "<td><strong>{$pago['moneda_pago']}</strong></td>";
        echo "<td>{$pago['estado_comprobante']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>Acciones:</h3>";
    echo "<ul>";
    echo "<li><a href='/controldepagosestacionamiento/public/operador/dashboard'>Ir al Dashboard del Operador</a></li>";
    echo "<li><a href='/controldepagosestacionamiento/public/operador/pagos-pendientes'>Ver Pagos Pendientes</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; padding: 15px; border: 1px solid #dc3545; border-radius: 5px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
