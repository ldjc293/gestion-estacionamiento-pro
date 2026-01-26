<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Pagos - Impresión</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
        }
        .info {
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h1>Reporte de Pagos</h1>
        <p>Control de Pagos de Estacionamiento</p>
        <p>Generado el <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="info">
        <strong>Filtros aplicados:</strong><br>
        Fecha: <?= date('d/m/Y', strtotime($fechaInicio)) ?> al <?= date('d/m/Y', strtotime($fechaFin)) ?><br>
        <?= $estado ? "Estado: " . ucfirst($estado) . "<br>" : "" ?>
        <?= $moneda ? "Moneda: " . $moneda . "<br>" : "" ?>
        <?= $torre ? "Bloque: " . $torre : "" ?>
    </div>

    <?php if (empty($pagos)): ?>
        <p style="text-align: center; margin-top: 30px;">No se encontraron registros para los filtros seleccionados.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Cédula</th>
                    <th>Ubicación</th>
                    <th>Monto USD</th>
                    <th>Monto Bs</th>
                    <th>Método</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagos as $pago): ?>
                    <tr>
                        <td><?= $pago['id'] ?></td>
                        <td><?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?></td>
                        <td><?= htmlspecialchars($pago['cliente_nombre']) ?></td>
                        <td><?= htmlspecialchars($pago['cliente_cedula']) ?></td>
                        <td>Blq <?= $pago['torre'] ?> - <?= $pago['apartamento'] ?></td>
                        <td class="text-right"><?= number_format($pago['monto_usd'], 2) ?></td>
                        <td class="text-right"><?= number_format($pago['monto_bs'], 2) ?></td>
                        <td><?= ucfirst(str_replace('_', ' ', $pago['moneda_pago'])) ?></td>
                        <td><?= ucfirst($pago['estado_comprobante']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="footer">
        Documento generado automáticamente por el Sistema de Control de Pagos.
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Imprimir Nuevamente</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer;">Cerrar</button>
    </div>

</body>
</html>
