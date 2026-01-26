<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Morosidad - Impresión</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0 0; color: #666; }
        .info { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .text-right { text-align: right; }
        .text-danger { color: #dc3545; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>Reporte de Morosidad</h1>
        <p>Control de Pagos de Estacionamiento</p>
        <p>Generado el <?= date('d/m/Y H:i') ?></p>
    </div>
    <div class="info">
        <strong>Filtros aplicados:</strong><br>
        <?= $torre ? "Bloque: " . $torre . "<br>" : "" ?>
        <?= ($mesesMin) ? "Meses Vencidos: " . $mesesMin . "+<br>" : "" ?>
    </div>
    <?php if (empty($morosos)): ?>
        <p style="text-align: center;">No hay clientes con morosidad para los criterios seleccionados.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>CI</th>
                    <th>Ubicación</th>
                    <th>Controles</th>
                    <th>Meses Vencidos</th>
                    <th>Deuda Total</th>
                    <th>Última Mens.</th>
                    <th>Contacto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($morosos as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($m['cedula']) ?></td>
                        <td>Blq <?= $m['torre'] ?> - <?= $m['apartamento'] ?></td>
                        <td><?= $m['total_controles'] ?></td>
                        <td><?= $m['meses_vencidos'] ?></td>
                        <td class="text-right text-danger"><?= number_format($m['deuda_total'], 2) ?> USD</td>
                        <td><?= $m['ultima_mensualidad'] ?></td>
                        <td><?= htmlspecialchars($m['telefono'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <div class="footer">Documento generado automáticamente.</div>
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Imprimir</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer;">Cerrar</button>
    </div>
</body>
</html>
