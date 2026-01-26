<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Controles - Impresión</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0 0; color: #666; }
        .info { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>Reporte de Controles</h1>
        <p>Control de Pagos de Estacionamiento</p>
        <p>Generado el <?= date('d/m/Y H:i') ?></p>
    </div>
    <div class="info">
        <strong>Filtros aplicados:</strong><br>
        <?= $estado ? "Estado: " . ucfirst($estado) . "<br>" : "" ?>
        <?= $torre ? "Bloque: " . $torre . "<br>" : "" ?>
        <?= $posicion ? "Posición: " . $posicion : "" ?>
    </div>
    <?php if (empty($controles)): ?>
        <p style="text-align: center;">No se encontraron registros.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Pos</th>
                    <th>Receptor</th>
                    <th>Código</th>
                    <th>Estado</th>
                    <th>Ubicación</th>
                    <th>Residente</th>
                    <th>Fecha Asignación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($controles as $c): ?>
                    <tr>
                        <td><strong><?= $c['posicion_numero'] ?></strong></td>
                        <td><?= $c['receptor'] ?></td>
                        <td><?= $c['numero_control_completo'] ?></td>
                        <td><?= ucfirst($c['estado']) ?></td>
                        <td><?= $c['torre'] ? "Blq {$c['torre']}-Esc {$c['escalera']}-Apto {$c['apartamento']}" : '-' ?></td>
                        <td><?= htmlspecialchars($c['residente_nombre'] ?? '') ?></td>
                        <td><?= $c['fecha_asignacion'] ? date('d/m/Y', strtotime($c['fecha_asignacion'])) : '-' ?></td>
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
