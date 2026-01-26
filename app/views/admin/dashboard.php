<?php
$pageTitle = 'Dashboard Administrador';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Dashboard', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <!-- Estadísticas Principales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="value"><?= $estadisticas['usuarios']['total'] ?? 0 ?></div>
                    <div class="label">Total Usuarios</div>
                    <div class="change">
                        <?= $estadisticas['usuarios']['activos'] ?? 0 ?> activos
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="value"><?= formatUSD($estadisticas['pagos']['total_usd'] ?? 0) ?></div>
                    <div class="label">Ingresos Totales</div>
                    <div class="change positive">
                        <i class="bi bi-arrow-up"></i> <?= $estadisticas['pagos']['aprobados'] ?? 0 ?> pagos
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="bi bi-controller"></i>
                    </div>
                    <div class="value"><?= $estadisticas['controles']['activos'] ?? 0 ?></div>
                    <div class="label">Controles Activos</div>
                    <div class="change">
                        de <?= $estadisticas['controles']['total'] ?? 500 ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <a href="<?= url('admin/reporteMorosidad') ?>" class="text-decoration-none">
                    <div class="stat-card" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="value"><?= $estadisticas['morosidad']['total_morosos'] ?? 0 ?></div>
                        <div class="label">Clientes Morosos</div>
                        <div class="change negative">
                            <i class="bi bi-arrow-up"></i> Requiere atención
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Solicitudes Pendientes -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(168, 85, 247, 0.1); color: #a855f7;">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <?php
                    $solicitudesPendientes = 0;
                    try {
                        $sql = "SELECT COUNT(*) as total FROM solicitudes_cambios WHERE estado = 'pendiente'";
                        $result = Database::fetchOne($sql);
                        $solicitudesPendientes = $result ? $result['total'] : 0;
                    } catch (Exception $e) {
                        $solicitudesPendientes = 0;
                    }
                    ?>
                    <div class="value"><?= $solicitudesPendientes ?></div>
                    <div class="label">Solicitudes Pendientes</div>
                    <?php if ($solicitudesPendientes > 0): ?>
                        <a href="<?= url('admin/solicitudes') ?>" class="btn btn-warning mt-2">
                            <i class="bi bi-eye"></i> Revisar Solicitudes
                        </a>
                    <?php else: ?>
                        <div class="change">
                            Todo al día
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Gestión Rápida -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-lightning"></i> Acciones Rápidas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <a href="<?= url('admin/crearUsuario') ?>" class="btn btn-primary w-100 mb-2">
                                    <i class="bi bi-person-plus"></i> Crear Usuario
                                </a>
                            </div>
                            <div class="col-md-2">
                                <a href="<?= url('admin/crearApartamento') ?>" class="btn btn-info w-100 mb-2">
                                    <i class="bi bi-building-add"></i> Crear Apartamento
                                </a>
                            </div>
                            <div class="col-md-2">
                                <a href="<?= url('admin/controles') ?>" class="btn btn-warning w-100 mb-2">
                                    <i class="bi bi-controller"></i> Gestionar Controles
                                </a>
                            </div>
                            <div class="col-md-2">
                                <a href="<?= url('admin/tarifas') ?>" class="btn btn-success w-100 mb-2">
                                    <i class="bi bi-cash-coin"></i> Gestionar Tarifas
                                </a>
                            </div>
                            <div class="col-md-2">
                                <a href="<?= url('admin/configuracion') ?>" class="btn btn-secondary w-100 mb-2">
                                    <i class="bi bi-gear"></i> Configuración
                                </a>
                            </div>
                            <div class="col-md-2">
                                <a href="<?= url('admin/logs') ?>" class="btn btn-outline-primary w-100 mb-2">
                                    <i class="bi bi-journal-text"></i> Ver Logs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desglose por Rol y Actividad Reciente -->
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-pie-chart"></i> Usuarios por Rol
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-person text-primary"></i> Clientes</span>
                                <strong><?= $estadisticas['usuarios']['clientes'] ?? 0 ?></strong>
                            </div>
                        </div>
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-person-badge text-success"></i> Operadores</span>
                                <strong><?= $estadisticas['usuarios']['operadores'] ?? 0 ?></strong>
                            </div>
                        </div>
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-graph-up text-info"></i> Consultores</span>
                                <strong><?= $estadisticas['usuarios']['consultores'] ?? 0 ?></strong>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-shield text-danger"></i> Administradores</span>
                                <strong><?= $estadisticas['usuarios']['administradores'] ?? 0 ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-activity"></i> Actividad Reciente del Sistema
                        </h6>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($actividadReciente)): ?>
                            <p class="text-muted text-center">No hay actividad reciente</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fecha/Hora</th>
                                            <th>Nivel</th>
                                            <th>Descripción</th>
                                            <th>Usuario</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($actividadReciente as $actividad): ?>
                                            <tr>
                                                <td>
                                                    <small><?= date('d/m H:i', strtotime($actividad['fecha_hora'])) ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $nivel = $actividad['nivel'] ?? 'info';
                                                    if ($nivel === 'info'): ?>
                                                        <span class="badge bg-info">Info</span>
                                                    <?php elseif ($nivel === 'warning'): ?>
                                                        <span class="badge bg-warning">Warning</span>
                                                    <?php elseif ($nivel === 'error'): ?>
                                                        <span class="badge bg-danger">Error</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= htmlspecialchars($nivel) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><small><?= htmlspecialchars($actividad['descripcion'] ?? $actividad['accion'] ?? '') ?></small></td>
                                                <td>
                                                    <small class="text-muted"><?= htmlspecialchars($actividad['usuario'] ?? 'Sistema') ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        <a href="<?= url('admin/logs') ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">
                            Ver Todos los Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
