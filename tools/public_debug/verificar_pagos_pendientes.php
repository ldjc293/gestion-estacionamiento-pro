<?php
/**
 * Script para verificar pagos pendientes en la base de datos
 * Ejecutar desde: http://localhost/controldepagosestacionamiento/public/verificar_pagos_pendientes.php
 */

require_once __DIR__ . '/../config/database.php';

echo "<h1>Verificación de Pagos Pendientes</h1>";
echo "<hr>";

try {
    // Consulta exacta que usa el sistema
    $sql = "SELECT p.*,
                   u.nombre_completo as cliente_nombre,
                   u.email as cliente_email,
                   CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento
            FROM pagos p
            JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
            JOIN usuarios u ON u.id = au.usuario_id
            JOIN apartamentos a ON a.id = au.apartamento_id
            WHERE p.estado_comprobante = 'pendiente'
            ORDER BY p.fecha_pago DESC";
    
    $pagosPendientes = Database::fetchAll($sql);
    
    echo "<h2>Resultados de la consulta:</h2>";
    echo "<p><strong>Total de pagos pendientes encontrados:</strong> " . count($pagosPendientes) . "</p>";
    
    if (empty($pagosPendientes)) {
        echo "<div style='background-color: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px;'>";
        echo "<h3>⚠️ No hay pagos pendientes</h3>";
        echo "<p>No se encontraron pagos con estado 'pendiente' en la base de datos.</p>";
        echo "</div>";
        
        // Verificar si hay pagos en general
        $sqlTodos = "SELECT COUNT(*) as total FROM pagos";
        $totalPagos = Database::fetchOne($sqlTodos);
        echo "<p>Total de pagos en la base de datos: <strong>{$totalPagos['total']}</strong></p>";
        
        // Mostrar últimos 5 pagos sin importar estado
        $sqlUltimos = "SELECT p.id, p.numero_recibo, p.estado_comprobante, p.fecha_pago, 
                              u.nombre_completo as cliente
                       FROM pagos p
                       JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
                       JOIN usuarios u ON u.id = au.usuario_id
                       ORDER BY p.id DESC
                       LIMIT 5";
        $ultimosPagos = Database::fetchAll($sqlUltimos);
        
        echo "<h3>Últimos 5 pagos registrados (cualquier estado):</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse; width:100%;'>";
        echo "<tr style='background-color:#f0f0f0;'>";
        echo "<th>ID</th><th>Recibo</th><th>Cliente</th><th>Estado</th><th>Fecha</th>";
        echo "</tr>";
        
        foreach ($ultimosPagos as $pago) {
            $colorEstado = $pago['estado_comprobante'] == 'pendiente' ? 'orange' : 
                          ($pago['estado_comprobante'] == 'aprobado' ? 'green' : 'red');
            echo "<tr>";
            echo "<td>{$pago['id']}</td>";
            echo "<td>{$pago['numero_recibo']}</td>";
            echo "<td>{$pago['cliente']}</td>";
            echo "<td style='color:{$colorEstado}; font-weight:bold;'>{$pago['estado_comprobante']}</td>";
            echo "<td>{$pago['fecha_pago']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<div style='background-color: #d4edda; padding: 15px; border: 1px solid #28a745; border-radius: 5px; margin-bottom: 20px;'>";
        echo "<h3>✅ Se encontraron " . count($pagosPendientes) . " pago(s) pendiente(s)</h3>";
        echo "</div>";
        
        echo "<h3>Detalles de los pagos pendientes:</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse; width:100%;'>";
        echo "<tr style='background-color:#f0f0f0;'>";
        echo "<th>ID</th><th>Recibo</th><th>Cliente</th><th>Email</th><th>Apartamento</th>";
        echo "<th>Monto USD</th><th>Monto Bs</th><th>Estado</th><th>Fecha Pago</th><th>Comprobante</th>";
        echo "</tr>";
        
        foreach ($pagosPendientes as $pago) {
            $esTest = strpos($pago['numero_recibo'], 'TEST-') === 0;
            $bgColor = $esTest ? '#ffffcc' : 'white';
            
            echo "<tr style='background-color:{$bgColor};'>";
            echo "<td><strong>{$pago['id']}</strong></td>";
            echo "<td><strong>{$pago['numero_recibo']}</strong>" . ($esTest ? " <span style='color:blue;'>📝 TEST</span>" : "") . "</td>";
            echo "<td>{$pago['cliente_nombre']}</td>";
            echo "<td><small>{$pago['cliente_email']}</small></td>";
            echo "<td>{$pago['apartamento']}</td>";
            echo "<td>\${$pago['monto_usd']}</td>";
            echo "<td>{$pago['monto_bs']} Bs</td>";
            echo "<td style='color:orange; font-weight:bold;'>{$pago['estado_comprobante']}</td>";
            echo "<td>{$pago['fecha_pago']}</td>";
            echo "<td><small>{$pago['comprobante_ruta']}</small></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verificar mensualidades asociadas
        echo "<h3>Mensualidades asociadas a los pagos pendientes:</h3>";
        foreach ($pagosPendientes as $pago) {
            $sqlMensualidades = "SELECT m.mes, m.anio, m.estado, pm.monto_aplicado_usd
                                FROM pago_mensualidad pm
                                JOIN mensualidades m ON m.id = pm.mensualidad_id
                                WHERE pm.pago_id = ?";
            $mensualidades = Database::fetchAll($sqlMensualidades, [$pago['id']]);
            
            echo "<h4>Pago #{$pago['id']} - {$pago['numero_recibo']}:</h4>";
            if (empty($mensualidades)) {
                echo "<p style='color:red;'>⚠️ No tiene mensualidades asociadas</p>";
            } else {
                echo "<ul>";
                foreach ($mensualidades as $mens) {
                    echo "<li>Mes {$mens['mes']}/{$mens['anio']} - Estado: {$mens['estado']} - Monto aplicado: \${$mens['monto_aplicado_usd']}</li>";
                }
                echo "</ul>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>Acciones:</h3>";
    echo "<ul>";
    echo "<li><a href='/controldepagosestacionamiento/public/operador/pagos-pendientes' target='_blank'>Ir a Pagos Pendientes (Vista del Operador)</a></li>";
    echo "<li><a href='/controldepagosestacionamiento/public/generar_pago_prueba.php' target='_blank'>Generar otro pago de prueba</a></li>";
    echo "<li><a href='javascript:location.reload();'>Recargar esta página</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; padding: 15px; border: 1px solid #dc3545; border-radius: 5px;'>";
    echo "<h3>❌ Error al consultar la base de datos</h3>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}
h1, h2, h3 {
    color: #333;
}
table {
    background-color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
th {
    text-align: left;
}
a {
    color: #007bff;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>
