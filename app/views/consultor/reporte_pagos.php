<?php
$pageTitle = 'Reporte de Pagos';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('consultor/dashboard')],
    ['label' => 'Reporte de Pagos', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-cash-stack"></i> Reporte de Pagos
                </h6>
                <div class="btn-group btn-group-sm">
                    <button onclick="exportarExcel()" class="btn btn-success">
                        <i class="bi bi-file-excel"></i> Excel
                    </button>
                    <button onclick="exportarPDF()" class="btn btn-danger">
                        <i class="bi bi-file-pdf"></i> PDF
                    </button>
                    <button onclick="window.print()" class="btn btn-outline-primary">
                        <i class="bi bi-printer"></i> Imprimir
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-2">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date"
                               name="fecha_inicio"
                               class="form-control form-control-sm"
                               value="<?= $_GET['fecha_inicio'] ?? date('Y-m-01') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date"
                               name="fecha_fin"
                               class="form-control form-control-sm"
                               value="<?= $_GET['fecha_fin'] ?? date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="aprobado" <?= ($_GET['estado'] ?? '') === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                            <option value="pendiente" <?= ($_GET['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="rechazado" <?= ($_GET['estado'] ?? '') === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Moneda</label>
                        <select name="moneda" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <option value="USD" <?= ($_GET['moneda'] ?? '') === 'USD' ? 'selected' : '' ?>>USD</option>
                            <option value="Bs" <?= ($_GET['moneda'] ?? '') === 'Bs' ? 'selected' : '' ?>>Bs</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bloque</label>
                        <select name="torre" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach (($bloques ?? []) as $t): ?>
                                <option value="<?= $t ?>" <?= ($_GET['torre'] ?? '') == $t ? 'selected' : '' ?>>Bloque <?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                    </div>
                </form>

                <!-- Resumen de Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="value"><?= $estadisticas['total_pagos'] ?? 0 ?></div>
                            <div class="label">Total Pagos</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="value"><?= formatUSD($estadisticas['total_usd'] ?? 0) ?></div>
                            <div class="label">Total en USD</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <i class="bi bi-cash"></i>
                            </div>
                            <div class="value"><?= formatBs($estadisticas['total_bs'] ?? 0) ?></div>
                            <div class="label">Total en Bs</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <div class="value"><?= $estadisticas['pendientes'] ?? 0 ?></div>
                            <div class="label">Pendientes</div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Pagos por Método</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartMetodos"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Pagos por Estado</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartEstados"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Pagos -->
                <?php if (empty($pagos)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 80px;"></i>
                        <h5 class="mt-3">No hay pagos</h5>
                        <p class="text-muted">No se encontraron pagos con los filtros seleccionados</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="tablePagos">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Apartamento</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Referencia</th>
                                    <th>Estado</th>
                                    <th>Aprobado Por</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagos as $pago): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary">#<?= str_pad($pago['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                                        <td><small><?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?></small></td>
                                        <td><strong><?= htmlspecialchars($pago['cliente_nombre']) ?></strong></td>
                                        <td><span class="badge bg-info">Blq<?= $pago['torre'] ?>-<?= $pago['apartamento'] ?></span></td>
                                        <td>
                                            <strong>
                                                <?php if ($pago['moneda_pago'] === 'usd_efectivo'): ?>
                                                    <?= formatUSD($pago['monto_usd']) ?>
                                                <?php else: ?>
                                                    <?= formatBs($pago['monto_bs']) ?>
                                                <?php endif; ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <small><?= ucfirst(str_replace('_', ' ', $pago['moneda_pago'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($pago['notas']): ?>
                                                <code><?= htmlspecialchars(substr($pago['notas'], 0, 15)) ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($pago['estado_comprobante'] === 'aprobado'): ?>
                                                <span class="badge bg-success">Aprobado</span>
                                            <?php elseif ($pago['estado_comprobante'] === 'rechazado'): ?>
                                                <span class="badge bg-danger">Rechazado</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($pago['aprobado_por']): ?>
                                                <small><i class="bi bi-person-check"></i> <?= htmlspecialchars($pago['operador_nombre'] ?? 'Operador #' . $pago['aprobado_por']) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<JS
<script>
function exportarExcel() {
    const params = new URLSearchParams(window.location.search);
    params.append('formato', 'excel');
    window.location.href = URL_BASE + '/consultor/export-pagos?' + params.toString();
}

function exportarPDF() {
    const params = new URLSearchParams(window.location.search);
    params.append('formato', 'pdf');
    window.location.href = URL_BASE + '/consultor/export-pagos?' + params.toString();
}

// Placeholder para gráficos (requiere Chart.js)
// TODO: Implementar con Chart.js cuando se incluya la librería
</script>
JS;
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
