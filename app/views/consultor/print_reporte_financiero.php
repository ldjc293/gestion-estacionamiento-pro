<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero - Impresión</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 5px 0 0; color: #666; }
        .section { margin-bottom: 25px; page-break-inside: avoid; }
        .section h2 { font-size: 14px; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 10px; }
        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .summary-card { border: 1px solid #ddd; padding: 10px; border-radius: 4px; }
        .summary-card .label { font-size: 11px; color: #666; margin-bottom: 5px; }
        .summary-card .value { font-size: 18px; font-weight: bold; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; font-size: 11px; }
        td { font-size: 11px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        @media print { 
            .no-print { display: none; } 
            body { padding: 0; }
            .summary-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>Reporte Financiero</h1>
        <p>Control de Pagos de Estacionamiento</p>
        <p>Periodo: <?= date('d/m/Y', strtotime($inicio)) ?> al <?= date('d/m/Y', strtotime($fin)) ?></p>
        <p>Generado el <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="section">
        <h2>Resumen General</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="label">Total de Pagos</div>
                <div class="value"><?= number_format($finanzas['total_pagos']) ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Ingresos USD</div>
                <div class="value">$<?= number_format($finanzas['ingresos_usd'], 2) ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Ingresos Bs</div>
                <div class="value">Bs <?= number_format($finanzas['ingresos_bs'], 2) ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($desgloseMetodos)): ?>
    <div class="section">
        <h2>Desglose por Método de Pago</h2>
        <table>
            <thead>
                <tr>
                    <th>Método</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-right">Total USD</th>
                    <th class="text-right">Total Bs</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($desgloseMetodos as $metodo): ?>
                    <tr>
                        <td><?= ucfirst(str_replace('_', ' ', $metodo['metodo'])) ?></td>
                        <td class="text-center"><?= $metodo['cantidad'] ?></td>
                        <td class="text-right">$<?= number_format($metodo['total_usd'], 2) ?></td>
                        <td class="text-right">Bs <?= number_format($metodo['total_bs'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($topClientes)): ?>
    <div class="section">
        <h2>Top 10 Clientes</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Ubicación</th>
                    <th class="text-center">Pagos</th>
                    <th class="text-right">Monto Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($topClientes as $cliente): ?>
                    <tr>
                        <td class="text-center"><?= $i++ ?></td>
                        <td><?= htmlspecialchars($cliente['nombre_completo']) ?></td>
                        <td>Blq <?= $cliente['torre'] ?>-<?= $cliente['apartamento'] ?></td>
                        <td class="text-center"><?= $cliente['total_pagos'] ?></td>
                        <td class="text-right">$<?= number_format($cliente['monto_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="footer">
        Documento generado automáticamente por el Sistema de Control de Pagos.
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Imprimir</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer;">Cerrar</button>
    </div>
</body>
</html>
