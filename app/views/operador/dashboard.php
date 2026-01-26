<?php
$pageTitle = 'Dashboard Operador';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('operador/dashboard')],
    ['label' => 'Dashboard', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <!-- Estadísticas del Día -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="value"><?= is_array($pagosPendientes) ? count($pagosPendientes) : 0 ?></div>
                    <div class="label">Pagos Pendientes</div>
                    <?php if (is_array($pagosPendientes) && count($pagosPendientes) > 0): ?>
                        <a href="<?= url('operador/pagos-pendientes') ?>" class="btn btn-sm btn-warning mt-2 w-100">
                            Revisar Ahora
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="value"><?= $estadisticasHoy['aprobados_hoy'] ?? 0 ?></div>
                    <div class="label">Aprobados Hoy</div>
                    <div class="change positive">
                        <i class="bi bi-arrow-up"></i> <?= formatUSD($estadisticasHoy['total_usd'] ?? 0) ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div class="value"><?= $estadisticasHoy['rechazados_hoy'] ?? 0 ?></div>
                    <div class="label">Rechazados Hoy</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="value"><?= $estadisticasHoy['total_pagos'] ?? 0 ?></div>
                    <div class="label">Total Pagos Hoy</div>
                </div>
            </div>
        </div>

        <!-- Estadísticas de Morosidad -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="value"><?= $estadisticasMorosidad['total_morosos'] ?? 0 ?></div>
                    <div class="label">Clientes Morosos</div>
                    <div class="change negative">
                        <i class="bi bi-arrow-up"></i> Requiere atención
                    </div>
                </div>
            </div>
        </div>

        <!-- Segunda fila de estadísticas -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="value"><?= count($solicitudesPendientes) ?></div>
                    <div class="label">Solicitudes Pendientes</div>
                    <?php if (count($solicitudesPendientes) > 0): ?>
                        <a href="<?= url('operador/solicitudes') ?>" class="btn btn-sm btn-warning mt-2 w-100">
                            Revisar Ahora
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pagos Pendientes de Aprobación -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bi bi-hourglass-split"></i> Pagos Pendientes de Aprobación
                        </h6>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= url('operador/pagos-pendientes') ?>" class="btn btn-outline-primary">
                                Ver Todos
                            </a>
                            <a href="<?= url('operador/registrar-pago-presencial') ?>" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Registrar Pago
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!is_array($pagosPendientes) || count($pagosPendientes) === 0): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-check-circle text-success" style="font-size: 64px;"></i>
                                <p class="text-muted mt-3">¡Todo al día! No hay pagos pendientes de revisión</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Apartamento</th>
                                            <th>Fecha Pago</th>
                                            <th>Monto</th>
                                            <th>Método</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (is_array($pagosPendientes) ? array_slice($pagosPendientes, 0, 10) : [] as $pago): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($pago['cliente_nombre']) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= $pago['apartamento'] ?></span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?></td>
                                                <td>
                                                    <strong>
                                                        <?php if ($pago['moneda_pago'] === 'usd_efectivo'): ?>
                                                            <?= formatUSD($pago['monto_usd']) ?>
                                                        <?php else: ?>
                                                            <?= formatBs($pago['monto_bs']) ?>
                                                        <?php endif; ?>
                                                    </strong>
                                                </td>
                                                <td><?= ucfirst(str_replace('_', ' ', $pago['moneda_pago'])) ?></td>
                                                <td>
                                                    <a href="<?= url('operador/revisar-pago?id=' . $pago['id']) ?>"
                                                       class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye"></i> Revisar
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if (is_array($pagosPendientes) && count($pagosPendientes) > 10): ?>
                                <div class="alert alert-info mb-0 mt-3">
                                    <i class="bi bi-info-circle"></i>
                                    Mostrando 10 de <?= count($pagosPendientes) ?> pagos pendientes.
                                    <a href="<?= url('operador/pagos-pendientes') ?>">Ver todos →</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Acciones Rápidas -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-lightning"></i> Acciones Rápidas
                        </h6>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <a href="<?= url('operador/registrar-pago-presencial') ?>" class="btn btn-primary">
                            <i class="bi bi-cash-coin"></i> Registrar Pago Presencial
                        </a>
                        <a href="<?= url('operador/registrar-pago-presencial?modo=adelantado') ?>" class="btn btn-success">
                            <i class="bi bi-calendar-plus"></i> Pagos Adelantados
                        </a>
                        <a href="<?= url('operador/pagos-pendientes') ?>" class="btn btn-warning">
                            <i class="bi bi-hourglass-split"></i> Ver Pagos Pendientes
                        </a>
                        <a href="<?= url('operador/solicitudes') ?>" class="btn btn-outline-warning">
                            <i class="bi bi-exclamation-triangle"></i> Ver Solicitudes
                            <?php if (count($solicitudesPendientes) > 0): ?>
                                <span class="badge bg-danger ms-1"><?= count($solicitudesPendientes) ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?= url('operador/historial-pagos') ?>" class="btn btn-outline-primary">
                            <i class="bi bi-clock-history"></i> Historial de Pagos
                        </a>
                        <a href="<?= url('operador/clientes-controles') ?>" class="btn btn-outline-info">
                            <i class="bi bi-people"></i> Clientes y Controles
                        </a>
                        <a href="<?= url('operador/vista-controles') ?>" class="btn btn-outline-info">
                            <i class="bi bi-grid"></i> Vista de Controles
                        </a>
                        <a href="<?= url('admin/reporteMorosidad') ?>" class="btn btn-outline-danger">
                            <i class="bi bi-exclamation-triangle"></i> Reporte Morosidad
                        </a>
                    </div>
                </div>

                <!-- Resumen de Ingresos del Día -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-graph-up"></i> Ingresos de Hoy
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 pb-3 border-bottom">
                            <small class="text-muted">Total USD</small>
                            <h4 class="mb-0 text-success"><?= formatUSD($estadisticasHoy['total_usd'] ?? 0) ?></h4>
                        </div>
                        <div class="mb-3 pb-3 border-bottom">
                            <small class="text-muted">Total Bs</small>
                            <h4 class="mb-0 text-success"><?= formatBs($estadisticasHoy['total_bs'] ?? 0) ?></h4>
                        </div>
                        <div class="mb-0">
                            <small class="text-muted">Pagos Procesados</small>
                            <h4 class="mb-0"><?= $estadisticasHoy['total_pagos'] ?? 0 ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actividad Reciente -->
        <?php if (is_array($ultimasActividades) && count($ultimasActividades) > 0): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-activity"></i> Actividad Reciente del Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Tipo</th>
                                    <th>Descripción</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (is_array($ultimasActividades) ? $ultimasActividades : [] as $actividad): ?>
                                    <tr>
                                        <td>
                                            <small><?= date('d/m/Y H:i', strtotime($actividad['fecha_hora'])) ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            // Determinar el nivel según el tipo de acción
                                            $accion = strtolower($actividad['accion'] ?? '');
                                            $nivel = 'info';
                                            $badgeClass = 'bg-info';

                                            if (strpos($accion, 'error') !== false || strpos($accion, 'fall') !== false) {
                                                $nivel = 'error';
                                                $badgeClass = 'bg-danger';
                                            } elseif (strpos($accion, 'delete') !== false || strpos($accion, 'elimin') !== false) {
                                                $nivel = 'warning';
                                                $badgeClass = 'bg-warning';
                                            }
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($nivel) ?></span>
                                        </td>
                                        <td><small><?= htmlspecialchars($actividad['accion'] ?? 'Desconocido') ?></small></td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($actividad['usuario_nombre'] ?? 'Sistema') ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Auto-refresh dashboard every 30 seconds to sync pending payments
    setInterval(function() {
        // Only refresh if page is visible and no modal is open
        if (!document.hidden && !document.querySelector('.modal.show')) {
            location.reload();
        }
    }, 30000);

    // Also refresh when page becomes visible again
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            location.reload();
        }
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
