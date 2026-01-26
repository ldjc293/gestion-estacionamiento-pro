<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Apartamentos - Impresión</title>
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
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>Reporte de Apartamentos</h1>
        <p>Control de Pagos de Estacionamiento</p>
        <p>Generado el <?= date('d/m/Y H:i') ?></p>
    </div>
    <div class="info">
        <strong>Filtros aplicados:</strong><br>
        <?= $torre ? "Bloque: " . $torre . "<br>" : "" ?>
        <?= ($estadoResidente) ? "Estado Residente: " . ucfirst($estadoResidente) . "<br>" : "" ?>
        <?= ($conMorosidad) ? "Con Morosidad: " . ucfirst($conMorosidad) : "" ?>
    </div>
    <?php if (empty($apartamentos)): ?>
        <p style="text-align: center;">No se encontraron registros.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Bloque</th>
                    <th>Escalera</th>
                    <th>Apto</th>
                    <th>Residente</th>
                    <th>Contacto</th>
                    <th>Controles</th>
                    <th>Deuda</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($apartamentos as $apto): ?>
                    <tr>
                        <td><?= $apto['torre'] ?></td>
                        <td><?= $apto['escalera'] ?></td>
                        <td><?= $apto['numero_apartamento'] ?></td>
                        <td><?= htmlspecialchars($apto['residente_nombre'] ?? 'Sin Residente') ?></td>
                        <td>
                            <?= htmlspecialchars($apto['telefono'] ?? '-') ?><br>
                            <?= htmlspecialchars($apto['email'] ?? '') ?>
                        </td>
                        <td><?= $apto['total_controles'] ?></td>
                        <td class="text-right"><?= number_format($apto['deuda_total'], 2) ?> USD</td>
                        <td>
                            <?php if (!$apto['usuario_activo']): ?>
                                Inactivo
                            <?php elseif ($apto['mensualidades_vencidas'] > 0): ?>
                                Moroso (<?= $apto['mensualidades_vencidas'] ?>m)
                            <?php else: ?>
                                Al día
                            <?php endif; ?>
                        </td>
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
