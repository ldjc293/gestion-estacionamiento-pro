<?php
/**
 * Script para generar pago de prueba pendiente de aprobación
 * Ejecutar desde: http://localhost/controldepagosestacionamiento/public/generar_pago_prueba.php
 */

require_once __DIR__ . '/../config/database.php';

echo "<h1>Generando Pago de Prueba</h1>";
echo "<hr>";

try {
    // Obtener un apartamento_usuario activo
    $sql = "SELECT au.id, u.nombre_completo, CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento
            FROM apartamento_usuario au
            JOIN usuarios u ON u.id = au.usuario_id
            JOIN apartamentos a ON a.id = au.apartamento_id
            WHERE au.activo = TRUE 
            AND u.rol = 'cliente'
            LIMIT 1";
    
    $apartamentoUsuario = Database::fetchOne($sql);
    
    if (!$apartamentoUsuario) {
        die("<p style='color:red;'>❌ ERROR: No hay apartamentos con usuarios activos</p>");
    }
    
    echo "<p>✅ Apartamento encontrado: {$apartamentoUsuario['apartamento']} - {$apartamentoUsuario['nombre_completo']}</p>";
    
    // Obtener la tasa BCV más reciente
    $sql = "SELECT id, tasa_usd_bs FROM tasa_cambio_bcv ORDER BY fecha_registro DESC LIMIT 1";
    $tasaBCV = Database::fetchOne($sql);
    
    if (!$tasaBCV) {
        die("<p style='color:red;'>❌ ERROR: No hay tasas BCV registradas</p>");
    }
    
    echo "<p>✅ Tasa BCV encontrada: {$tasaBCV['tasa_usd_bs']} Bs/USD</p>";
    
    // Generar número de recibo único
    $numeroRecibo = 'TEST-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Datos del pago
    $montoUSD = 5.00;
    $montoBS = $montoUSD * $tasaBCV['tasa_usd_bs'];
    
    echo "<p>📝 Generando pago:</p>";
    echo "<ul>";
    echo "<li>Número de recibo: <strong>{$numeroRecibo}</strong></li>";
    echo "<li>Monto USD: <strong>\${$montoUSD}</strong></li>";
    echo "<li>Monto Bs: <strong>{$montoBS} Bs</strong></li>";
    echo "<li>Estado: <strong style='color:orange;'>PENDIENTE</strong></li>";
    echo "</ul>";
    
    // Insertar el pago
    $sql = "INSERT INTO pagos (
                apartamento_usuario_id,
                numero_recibo,
                monto_usd,
                monto_bs,
                tasa_cambio_id,
                moneda_pago,
                metodo_pago,
                referencia_pago,
                fecha_pago,
                comprobante_ruta,
                estado_comprobante,
                registrado_por,
                fecha_registro,
                google_sheets_sync
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, NOW(), FALSE)";
    
    $params = [
        $apartamentoUsuario['id'],
        $numeroRecibo,
        $montoUSD,
        $montoBS,
        $tasaBCV['id'],
        'Bs',
        'transferencia',
        'REF-TEST-123456',
        '/uploads/comprobantes/test_comprobante.pdf',
        'pendiente',
        $apartamentoUsuario['id']
    ];
    
    $pagoId = Database::execute($sql, $params);
    
    echo "<p>✅ Pago insertado con ID: <strong>{$pagoId}</strong></p>";
    
    // Obtener mensualidad pendiente
    $sql = "SELECT id, mes, anio FROM mensualidades 
            WHERE apartamento_usuario_id = ? 
            AND estado = 'pendiente'
            ORDER BY mes_correspondiente ASC
            LIMIT 1";
    
    $mensualidad = Database::fetchOne($sql, [$apartamentoUsuario['id']]);
    
    if ($mensualidad) {
        // Asociar pago con mensualidad
        $sql = "INSERT INTO pago_mensualidad (pago_id, mensualidad_id) VALUES (?, ?)";
        Database::execute($sql, [$pagoId, $mensualidad['id']]);
        
        echo "<p>✅ Pago asociado con mensualidad {$mensualidad['mes']}/{$mensualidad['anio']}</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ No hay mensualidades pendientes para asociar</p>";
    }
    
    echo "<hr>";
    echo "<h2>🎉 ¡Pago de prueba creado exitosamente!</h2>";
    echo "<p>El pago aparecerá en: <a href='/controldepagosestacionamiento/public/operador/pagos-pendientes' target='_blank'>/operador/pagos-pendientes</a></p>";
    
    // Mostrar detalles del pago
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
    
    $pagoDetalle = Database::fetchOne($sql, [$pagoId]);
    
    echo "<h3>Detalles del Pago:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td>ID</td><td>{$pagoDetalle['id']}</td></tr>";
    echo "<tr><td>Recibo</td><td>{$pagoDetalle['numero_recibo']}</td></tr>";
    echo "<tr><td>Cliente</td><td>{$pagoDetalle['cliente']}</td></tr>";
    echo "<tr><td>Email</td><td>{$pagoDetalle['email']}</td></tr>";
    echo "<tr><td>Apartamento</td><td>{$pagoDetalle['apartamento']}</td></tr>";
    echo "<tr><td>Monto USD</td><td>\${$pagoDetalle['monto_usd']}</td></tr>";
    echo "<tr><td>Monto Bs</td><td>{$pagoDetalle['monto_bs']} Bs</td></tr>";
    echo "<tr><td>Estado</td><td style='color:orange;font-weight:bold;'>{$pagoDetalle['estado_comprobante']}</td></tr>";
    echo "<tr><td>Fecha Pago</td><td>{$pagoDetalle['fecha_pago']}</td></tr>";
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
