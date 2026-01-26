<?php
/**
 * Script mejorado para generar pago de prueba con manejo de errores
 * Ejecutar desde: http://localhost/controldepagosestacionamiento/public/generar_pago_prueba_v2.php
 */

// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "<h1>Generando Pago de Prueba (Versión 2 - Con Debug)</h1>";
echo "<hr>";

try {
    echo "<h2>Paso 1: Verificando apartamento_usuario activo</h2>";
    
    // Obtener un apartamento_usuario activo
    $sql = "SELECT au.id, u.nombre_completo, u.email, u.id as usuario_id,
                   CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento
            FROM apartamento_usuario au
            JOIN usuarios u ON u.id = au.usuario_id
            JOIN apartamentos a ON a.id = au.apartamento_id
            WHERE au.activo = TRUE 
            AND u.rol = 'cliente'
            LIMIT 1";
    
    echo "<pre>SQL: $sql</pre>";
    
    $apartamentoUsuario = Database::fetchOne($sql);
    
    if (!$apartamentoUsuario) {
        throw new Exception("No hay apartamentos con usuarios activos");
    }
    
    echo "<p>✅ Apartamento encontrado:</p>";
    echo "<ul>";
    echo "<li>ID apartamento_usuario: {$apartamentoUsuario['id']}</li>";
    echo "<li>Cliente: {$apartamentoUsuario['nombre_completo']}</li>";
    echo "<li>Email: {$apartamentoUsuario['email']}</li>";
    echo "<li>Apartamento: {$apartamentoUsuario['apartamento']}</li>";
    echo "</ul>";
    
    echo "<h2>Paso 2: Obteniendo tasa BCV</h2>";
    
    // Obtener la tasa BCV más reciente
    $sql = "SELECT id, tasa_usd_bs FROM tasa_cambio_bcv ORDER BY fecha_registro DESC LIMIT 1";
    echo "<pre>SQL: $sql</pre>";
    
    $tasaBCV = Database::fetchOne($sql);
    
    if (!$tasaBCV) {
        throw new Exception("No hay tasas BCV registradas");
    }
    
    echo "<p>✅ Tasa BCV encontrada:</p>";
    echo "<ul>";
    echo "<li>ID: {$tasaBCV['id']}</li>";
    echo "<li>Tasa: {$tasaBCV['tasa_usd_bs']} Bs/USD</li>";
    echo "</ul>";
    
    echo "<h2>Paso 3: Preparando datos del pago</h2>";
    
    // Generar número de recibo único (máximo 20 caracteres)
    // Formato: TEST-HHMMSS-RRR (18 caracteres)
    $numeroRecibo = 'TEST-' . date('His') . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
    
    // Datos del pago
    $montoUSD = 5.00;
    $montoBS = round($montoUSD * $tasaBCV['tasa_usd_bs'], 2);
    
    echo "<ul>";
    echo "<li>Número de recibo: <strong>{$numeroRecibo}</strong></li>";
    echo "<li>Monto USD: <strong>\${$montoUSD}</strong></li>";
    echo "<li>Monto Bs: <strong>{$montoBS} Bs</strong></li>";
    echo "<li>Estado: <strong style='color:orange;'>pendiente</strong></li>";
    echo "</ul>";
    
    echo "<h2>Paso 4: Insertando pago en la base de datos</h2>";
    
    // SQL de inserción - SOLO COLUMNAS BÁSICAS
    $sql = "INSERT INTO pagos (
                apartamento_usuario_id,
                numero_recibo,
                monto_usd,
                monto_bs,
                tasa_cambio_id,
                moneda_pago,
                fecha_pago,
                comprobante_ruta,
                estado_comprobante,
                registrado_por
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)";
    
    echo "<pre>SQL: $sql</pre>";
    
    $params = [
        $apartamentoUsuario['id'],           // apartamento_usuario_id
        $numeroRecibo,                        // numero_recibo
        $montoUSD,                           // monto_usd
        $montoBS,                            // monto_bs
        $tasaBCV['id'],                      // tasa_cambio_id
        'bs_transferencia',                  // moneda_pago ⭐ CORREGIDO: valor válido del ENUM
        '/uploads/comprobantes/test_' . date('Ymd_His') . '.pdf', // comprobante_ruta
        'pendiente',                         // estado_comprobante ⭐ IMPORTANTE
        $apartamentoUsuario['usuario_id']   // registrado_por
    ];
    
    echo "<p>Parámetros:</p>";
    echo "<pre>" . print_r($params, true) . "</pre>";
    
    // Ejecutar inserción
    $pagoId = Database::insert($sql, $params);  // ⭐ CORREGIDO: usar insert() en lugar de execute()
    
    if (!$pagoId) {
        throw new Exception("Error: Database::insert() retornó false o 0");
    }
    
    echo "<p>✅ <strong>Pago insertado exitosamente con ID: {$pagoId}</strong></p>";
    
    echo "<h2>Paso 5: Buscando mensualidad pendiente</h2>";
    
    // Obtener mensualidad pendiente
    $sql = "SELECT id, mes, anio FROM mensualidades 
            WHERE apartamento_usuario_id = ? 
            AND estado = 'pendiente'
            ORDER BY anio ASC, mes ASC
            LIMIT 1";
    
    echo "<pre>SQL: $sql</pre>";
    echo "<p>Parámetro: apartamento_usuario_id = {$apartamentoUsuario['id']}</p>";
    
    $mensualidad = Database::fetchOne($sql, [$apartamentoUsuario['id']]);
    
    if ($mensualidad) {
        echo "<p>✅ Mensualidad encontrada:</p>";
        echo "<ul>";
        echo "<li>ID: {$mensualidad['id']}</li>";
        echo "<li>Mes/Año: {$mensualidad['mes']}/{$mensualidad['anio']}</li>";
        echo "</ul>";
        
        echo "<h2>Paso 6: Asociando pago con mensualidad</h2>";
        
        // Asociar pago con mensualidad
        $sql = "INSERT INTO pago_mensualidad (pago_id, mensualidad_id, monto_aplicado_usd) 
                VALUES (?, ?, ?)";
        
        echo "<pre>SQL: $sql</pre>";
        echo "<p>Parámetros: pago_id={$pagoId}, mensualidad_id={$mensualidad['id']}, monto={$montoUSD}</p>";
        
        $result = Database::insert($sql, [$pagoId, $mensualidad['id'], $montoUSD]);  // ⭐ CORREGIDO
        
        if ($result) {
            echo "<p>✅ Pago asociado con mensualidad {$mensualidad['mes']}/{$mensualidad['anio']}</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ No se pudo asociar con mensualidad (pero el pago se creó)</p>";
        }
    } else {
        echo "<p style='color:orange;'>⚠️ No hay mensualidades pendientes para este apartamento</p>";
        echo "<p>El pago se creó pero no está asociado a ninguna mensualidad</p>";
    }
    
    echo "<hr>";
    echo "<h2>🎉 ¡Proceso completado!</h2>";
    
    echo "<h3>Verificación final:</h3>";
    
    // Verificar que el pago se insertó correctamente
    $sql = "SELECT 
                p.id,
                p.numero_recibo,
                p.monto_usd,
                p.monto_bs,
                p.estado_comprobante,
                p.fecha_pago,
                u.nombre_completo as cliente,
                u.email,
                CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento
            FROM pagos p
            JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
            JOIN usuarios u ON u.id = au.usuario_id
            JOIN apartamentos a ON a.id = au.apartamento_id
            WHERE p.id = ?";
    
    $pagoVerificado = Database::fetchOne($sql, [$pagoId]);
    
    if ($pagoVerificado) {
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse; background-color:white;'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>ID</td><td><strong>{$pagoVerificado['id']}</strong></td></tr>";
        echo "<tr><td>Recibo</td><td><strong style='color:blue;'>{$pagoVerificado['numero_recibo']}</strong></td></tr>";
        echo "<tr><td>Cliente</td><td>{$pagoVerificado['cliente']}</td></tr>";
        echo "<tr><td>Email</td><td>{$pagoVerificado['email']}</td></tr>";
        echo "<tr><td>Apartamento</td><td>{$pagoVerificado['apartamento']}</td></tr>";
        echo "<tr><td>Monto USD</td><td>\${$pagoVerificado['monto_usd']}</td></tr>";
        echo "<tr><td>Monto Bs</td><td>{$pagoVerificado['monto_bs']} Bs</td></tr>";
        echo "<tr><td>Estado</td><td><strong style='color:orange;'>{$pagoVerificado['estado_comprobante']}</strong></td></tr>";
        echo "<tr><td>Fecha Pago</td><td>{$pagoVerificado['fecha_pago']}</td></tr>";
        echo "</table>";
        
        echo "<h3>Enlaces:</h3>";
        echo "<ul>";
        echo "<li><a href='/controldepagosestacionamiento/public/verificar_pagos_pendientes.php' target='_blank'>Ver todos los pagos pendientes</a></li>";
        echo "<li><a href='/controldepagosestacionamiento/public/operador/pagos-pendientes' target='_blank'>Ir a Pagos Pendientes (Vista del Operador)</a></li>";
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>❌ ERROR: No se pudo verificar el pago insertado</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; padding: 15px; border: 1px solid #dc3545; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "<h4>Stack Trace:</h4>";
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
pre {
    background-color: #f0f0f0;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
}
table {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
a {
    color: #007bff;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>
