<?php
$pageTitle = 'Reporte Financiero';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('consultor/dashboard')],
    ['label' => 'Reporte Financiero', 'url' => '#']
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
                    <i class="bi bi-graph-up"></i> Reporte Financiero
                </h6>
                <div class="btn-group btn-group-sm">
                    <button onclick="exportarExcel()" class="btn btn-success">
                        <i class="bi bi-file-excel"></i> Excel
                    </button>
                    <button onclick="exportarPDF()" class="btn btn-danger">
                        <i class="bi bi-file-pdf"></i> PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros de Período -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Período</label>
                        <select name="periodo" class="form-select form-select-sm" id="selectPeriodo">
                            <option value="mes_actual" <?= ($_GET['periodo'] ?? 'mes_actual') === 'mes_actual' ? 'selected' : '' ?>>Mes Actual</option>
                            <option value="mes_anterior" <?= ($_GET['periodo'] ?? '') === 'mes_anterior' ? 'selected' : '' ?>>Mes Anterior</option>
                            <option value="trimestre" <?= ($_GET['periodo'] ?? '') === 'trimestre' ? 'selected' : '' ?>>Último Trimestre</option>
                            <option value="semestre" <?= ($_GET['periodo'] ?? '') === 'semestre' ? 'selected' : '' ?>>Último Semestre</option>
                            <option value="anio" <?= ($_GET['periodo'] ?? '') === 'anio' ? 'selected' : '' ?>>Año Actual</option>
                            <option value="personalizado" <?= ($_GET['periodo'] ?? '') === 'personalizado' ? 'selected' : '' ?>>Personalizado</option>
                        </select>
                    </div>
                    <div class="col-md-2" id="divFechaInicio" style="display: <?= ($_GET['periodo'] ?? '') === 'personalizado' ? 'block' : 'none' ?>">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="<?= $_GET['fecha_inicio'] ?? '' ?>">
                    </div>
                    <div class="col-md-2" id="divFechaFin" style="display: <?= ($_GET['periodo'] ?? '') === 'personalizado' ? 'block' : 'none' ?>">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control form-control-sm" value="<?= $_GET['fecha_fin'] ?? '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                    </div>
                </form>

                <!-- Resumen Financiero -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div class="value"><?= formatUSD($finanzas['ingresos_usd'] ?? 0) ?></div>
                            <div class="label">Ingresos USD</div>
                            <div class="change positive">
                                <i class="bi bi-arrow-up"></i> <?= $finanzas['total_pagos'] ?? 0 ?> pagos
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <i class="bi bi-cash"></i>
                            </div>
                            <div class="value"><?= formatBs($finanzas['ingresos_bs'] ?? 0) ?></div>
                            <div class="label">Ingresos Bs</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="value"><?= formatUSD($finanzas['deuda_pendiente'] ?? 0) ?></div>
                            <div class="label">Deuda Pendiente</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="value"><?= $finanzas['mensualidades_pagadas'] ?? 0 ?></div>
                            <div class="label">Mensualidades Pagadas</div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Ingresos por Mes</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartIngresosMensuales"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Métodos de Pago</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartMetodosPago"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Desglose por Método de Pago -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Desglose por Método de Pago</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Método</th>
                                        <th>Cantidad de Pagos</th>
                                        <th>Total USD</th>
                                        <th>Total Bs</th>
                                        <th>Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($desgloseMetodos ?? [] as $metodo): ?>
                                        <tr>
                                            <td><strong><?= ucfirst(str_replace('_', ' ', $metodo['metodo'])) ?></strong></td>
                                            <td><?= $metodo['cantidad'] ?></td>
                                            <td><?= formatUSD($metodo['total_usd']) ?></td>
                                            <td><?= formatBs($metodo['total_bs']) ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar"
                                                         style="width: <?= $metodo['porcentaje'] ?>%">
                                                        <?= $metodo['porcentaje'] ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tasa de Cobro -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Tasa de Cobro</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center mb-3">
                                    <h5 class="mb-0">Mensualidades Generadas</h5>
                                    <h2 class="text-primary"><?= $tasaCobro['generadas'] ?? 0 ?></h2>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center mb-3">
                                    <h5 class="mb-0">Mensualidades Pagadas</h5>
                                    <h2 class="text-success"><?= $tasaCobro['pagadas'] ?? 0 ?></h2>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center mb-3">
                                    <h5 class="mb-0">Tasa de Cobro</h5>
                                    <h2 class="text-info"><?= $tasaCobro['porcentaje'] ?? 0 ?>%</h2>
                                </div>
                            </div>
                        </div>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar bg-success"
                                 style="width: <?= $tasaCobro['porcentaje'] ?? 0 ?>%">
                                <?= $tasaCobro['porcentaje'] ?? 0 ?>% cobrado
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 10 Clientes por Pagos -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Top 10 Clientes con Más Pagos</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Cliente</th>
                                        <th>Apartamento</th>
                                        <th>Total Pagos</th>
                                        <th>Monto Total USD</th>
                                        <th>Último Pago</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topClientes ?? [] as $index => $cliente): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><strong><?= htmlspecialchars($cliente['nombre_completo']) ?></strong></td>
                                            <td><span class="badge bg-secondary">T<?= $cliente['torre'] ?>-<?= $cliente['apartamento'] ?></span></td>
                                            <td><span class="badge bg-primary"><?= $cliente['total_pagos'] ?> pagos</span></td>
                                            <td><strong class="text-success"><?= formatUSD($cliente['monto_total']) ?></strong></td>
                                            <td><small><?= date('d/m/Y', strtotime($cliente['ultimo_pago'])) ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<JS
<script>
// Show/hide custom date fields
document.getElementById('selectPeriodo').addEventListener('change', function() {
    const personalizado = this.value === 'personalizado';
    document.getElementById('divFechaInicio').style.display = personalizado ? 'block' : 'none';
    document.getElementById('divFechaFin').style.display = personalizado ? 'block' : 'none';
});

function exportarExcel() {
    const params = new URLSearchParams(window.location.search);
    params.append('formato', 'excel');
    window.location.href = URL_BASE + '/consultor/export-financiero?' + params.toString();
}

function exportarPDF() {
    const params = new URLSearchParams(window.location.search);
    params.append('formato', 'pdf');
    window.location.href = URL_BASE + '/consultor/export-financiero?' + params.toString();
}

// Placeholder para gráficos (requiere Chart.js)
// TODO: Implementar con Chart.js cuando se incluya la librería
</script>
JS;
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
