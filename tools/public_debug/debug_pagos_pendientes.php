<?php
/**
 * Script de debug para verificar pagos pendientes
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Pago.php';

echo "<h1>Debug: Pagos Pendientes</h1>";
echo "<hr>";

try {
    // Obtener pagos pendientes usando el método del modelo
    $pagosPendientes = Pago::getPendientesAprobar();
    
    echo "<h2>Resultado de Pago::getPendientesAprobar()</h2>";
    echo "<p><strong>Tipo:</strong> " . gettype($pagosPendientes) . "</p>";
    echo "<p><strong>Es array:</strong> " . (is_array($pagosPendientes) ? 'Sí' : 'No') . "</p>";
    echo "<p><strong>Count:</strong> " . (is_array($pagosPendientes) ? count($pagosPendientes) : 'N/A') . "</p>";
    
    if (is_array($pagosPendientes) && count($pagosPendientes) > 0) {
        echo "<h3>✅ Se encontraron " . count($pagosPendientes) . " pagos pendientes</h3>";
        
        echo "<h3>Primeros 3 pagos:</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse; width:100%;'>";
        echo "<tr style='background-color:#f0f0f0;'>";
        echo "<th>ID</th><th>Recibo</th><th>Cliente</th><th>Apartamento</th><th>Fecha Pago</th><th>Monto USD</th><th>Monto Bs</th><th>Moneda</th>";
        echo "</tr>";
        
        foreach (array_slice($pagosPendientes, 0, 3) as $pago) {
            echo "<tr>";
            echo "<td>{$pago['id']}</td>";
            echo "<td>{$pago['numero_recibo']}</td>";
            echo "<td>" . htmlspecialchars($pago['cliente_nombre']) . "</td>";
            echo "<td>{$pago['apartamento']}</td>";
            echo "<td>{$pago['fecha_pago']}</td>";
            echo "<td>\${$pago['monto_usd']}</td>";
            echo "<td>{$pago['monto_bs']} Bs</td>";
            echo "<td>{$pago['moneda_pago']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Estructura del primer pago:</h3>";
        echo "<pre>" . print_r($pagosPendientes[0], true) . "</pre>";
        
    } else {
        echo "<h3>⚠️ No se encontraron pagos pendientes</h3>";
    }
    
    // Verificar directamente en la base de datos
    echo "<hr>";
    echo "<h2>Verificación directa en BD</h2>";
    
    $sql = "SELECT COUNT(*) as total FROM pagos WHERE estado_comprobante = 'pendiente'";
    $result = Database::fetchOne($sql);
    echo "<p><strong>Total en BD:</strong> {$result['total']}</p>";
    
    // Verificar si hay problemas con la consulta JOIN
    $sql = "SELECT p.id, p.numero_recibo, p.estado_comprobante, 
                   p.apartamento_usuario_id,
                   au.usuario_id,
                   u.nombre_completo,
                   a.bloque, a.numero_apartamento
            FROM pagos p
            LEFT JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
            LEFT JOIN usuarios u ON u.id = au.usuario_id
            LEFT JOIN apartamentos a ON a.id = au.apartamento_id
            WHERE p.estado_comprobante = 'pendiente'
            LIMIT 3";
    
    $pagosDebug = Database::fetchAll($sql);
    
    echo "<h3>Pagos con LEFT JOIN (primeros 3):</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse; width:100%;'>";
    echo "<tr style='background-color:#f0f0f0;'>";
    echo "<th>ID</th><th>Recibo</th><th>AU ID</th><th>Usuario ID</th><th>Nombre</th><th>Bloque</th><th>Apto</th>";
    echo "</tr>";
    
    foreach ($pagosDebug as $pago) {
        $hasNull = false;
        if (is_null($pago['usuario_id']) || is_null($pago['nombre_completo'])) {
            $hasNull = true;
        }
        
        echo "<tr" . ($hasNull ? " style='background-color:#ffcccc;'" : "") . ">";
        echo "<td>{$pago['id']}</td>";
        echo "<td>{$pago['numero_recibo']}</td>";
        echo "<td>" . ($pago['apartamento_usuario_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($pago['usuario_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($pago['nombre_completo'] ?? 'NULL') . "</td>";
        echo "<td>" . ($pago['bloque'] ?? 'NULL') . "</td>";
        echo "<td>" . ($pago['numero_apartamento'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (count($pagosDebug) > 0) {
        $hasNulls = false;
        foreach ($pagosDebug as $pago) {
            if (is_null($pago['usuario_id']) || is_null($pago['nombre_completo'])) {
                $hasNulls = true;
                break;
            }
        }
        
        if ($hasNulls) {
            echo "<div style='background-color:#ffcccc; padding:15px; margin-top:15px;'>";
            echo "<h3>⚠️ PROBLEMA DETECTADO</h3>";
            echo "<p>Algunos pagos tienen datos NULL en las tablas relacionadas (usuario o apartamento).</p>";
            echo "<p>Esto causa que el INNER JOIN en getPendientesAprobar() los excluya del resultado.</p>";
            echo "<p><strong>Solución:</strong> Cambiar INNER JOIN a LEFT JOIN en el método getPendientesAprobar()</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; padding: 15px; border: 1px solid #dc3545; border-radius: 5px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>
