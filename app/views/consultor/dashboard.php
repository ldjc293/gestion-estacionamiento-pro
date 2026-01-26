<?php
$pageTitle = 'Dashboard Consultor';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('consultor/dashboard')],
    ['label' => 'Dashboard', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <!-- Estadísticas Generales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="value"><?= $estadisticasGenerales['total_clientes'] ?? 0 ?></div>
                    <div class="label">Clientes Activos</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="bi bi-controller"></i>
                    </div>
                    <div class="value"><?= $estadisticasControles['activos'] ?? 0 ?></div>
                    <div class="label">Controles Activos</div>
                    <div class="change">
                        de <?= $estadisticasControles['total'] ?? 500 ?> totales
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="value"><?= $morosidad['total_morosos'] ?? 0 ?></div>
                    <div class="label">Clientes Morosos</div>
                    <div class="change negative">
                        <i class="bi bi-arrow-up"></i> Deuda: <?= formatUSD($morosidad['deuda_total'] ?? 0) ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="value"><?= $estadisticasGenerales['pagos_pendientes'] ?? 0 ?></div>
                    <div class="label">Pagos Pendientes</div>
                </div>
            </div>
        </div>

        <!-- Estadísticas del Mes -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-graph-up"></i> Estadísticas del Mes Actual
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <h6 class="text-muted">Total Pagos</h6>
                        <h3><?= $estadisticasMes['total_pagos'] ?? 0 ?></h3>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted">Ingresos USD</h6>
                        <h3 class="text-success"><?= formatUSD($estadisticasMes['total_usd'] ?? 0) ?></h3>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted">Ingresos Bs</h6>
                        <h3 class="text-success"><?= formatBs($estadisticasMes['total_bs'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accesos Rápidos a Reportes -->
        <div class="row">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-exclamation-triangle"></i> Reporte de Morosidad
                        </h6>
                    </div>
                    <div class="card-body">
                        <p>Consulta clientes con pagos vencidos y montos adeudados</p>
                        <a href="<?= url('consultor/reporte-morosidad') ?>" class="btn btn-danger">
                            <i class="bi bi-file-text"></i> Ver Reporte Completo
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-cash-stack"></i> Reporte de Pagos
                        </h6>
                    </div>
                    <div class="card-body">
                        <p>Visualiza todos los pagos con filtros por período y estado</p>
                        <a href="<?= url('consultor/reporte-pagos') ?>" class="btn btn-success">
                            <i class="bi bi-file-text"></i> Ver Reporte Completo
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mt-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-controller"></i> Reporte de Controles
                        </h6>
                    </div>
                    <div class="card-body">
                        <p>Estado de los 500 controles de estacionamiento</p>
                        <a href="<?= url('consultor/reporte-controles') ?>" class="btn btn-primary">
                            <i class="bi bi-file-text"></i> Ver Reporte Completo
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mt-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-graph-up"></i> Reporte Financiero
                        </h6>
                    </div>
                    <div class="card-body">
                        <p>Análisis financiero mensual con gráficos e indicadores</p>
                        <a href="<?= url('consultor/reporte-financiero') ?>" class="btn btn-info">
                            <i class="bi bi-file-text"></i> Ver Reporte Completo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
