<?php
$pageTitle = 'Reporte de Controles';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('consultor/dashboard')],
    ['label' => 'Reporte de Controles', 'url' => '#']
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
                    <i class="bi bi-tag"></i> Reporte de Controles de Estacionamiento
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
                <!-- Filtros -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="disponible" <?= ($_GET['estado'] ?? '') === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                            <option value="asignado" <?= ($_GET['estado'] ?? '') === 'asignado' ? 'selected' : '' ?>>Asignado</option>
                            <option value="bloqueado" <?= ($_GET['estado'] ?? '') === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bloque</label>
                        <select name="torre" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach (($bloques ?? []) as $t): ?>
                                <option value="<?= $t ?>" <?= ($_GET['torre'] ?? '') == $t ? 'selected' : '' ?>>Bloque <?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Posición</label>
                        <input type="number"
                               name="posicion"
                               class="form-control form-control-sm"
                               placeholder="1-250"
                               min="1"
                               max="250"
                               value="<?= $_GET['posicion'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                    </div>
                </form>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                                <i class="bi bi-tag"></i>
                            </div>
                            <div class="value"><?= $estadisticas['total'] ?? 500 ?></div>
                            <div class="label">Total Controles</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="value"><?= $estadisticas['asignados'] ?? 0 ?></div>
                            <div class="label">Asignados</div>
                            <div class="change positive">
                                <?= $estadisticas['porcentaje_asignados'] ?? 0 ?>%
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                                <i class="bi bi-lock"></i>
                            </div>
                            <div class="value"><?= $estadisticas['bloqueados'] ?? 0 ?></div>
                            <div class="label">Bloqueados</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(156, 163, 175, 0.1); color: #9ca3af;">
                                <i class="bi bi-inbox"></i>
                            </div>
                            <div class="value"><?= $estadisticas['disponibles'] ?? 0 ?></div>
                            <div class="label">Disponibles</div>
                        </div>
                    </div>
                </div>

                <!-- Distribución por Torre -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Distribución por Bloque</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Bloque</th>
                                        <th>Total</th>
                                        <th>Asignados</th>
                                        <th>Bloqueados</th>
                                        <th>Disponibles</th>
                                        <th>Ocupación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($distribucionTorres ?? [] as $torre): ?>
                                        <tr>
                                            <td><strong>Bloque <?= $torre['torre'] ?></strong></td>
                                            <td><?= $torre['total'] ?></td>
                                            <td><span class="badge bg-success"><?= $torre['asignados'] ?></span></td>
                                            <td><span class="badge bg-danger"><?= $torre['bloqueados'] ?></span></td>
                                            <td><span class="badge bg-secondary"><?= $torre['disponibles'] ?></span></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success"
                                                         style="width: <?= $torre['porcentaje'] ?>%">
                                                        <?= $torre['porcentaje'] ?>%
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

                <!-- Tabla de Controles -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm" id="tableControles">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Posición</th>
                                <th>Receptor</th>
                                <th>Código</th>
                                <th>Estado</th>
                                <th>Apartamento</th>
                                <th>Residente</th>
                                <th>Fecha Asignación</th>
                                <th>Motivo Bloqueo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($controles ?? [] as $control): ?>
                                <tr>
                                    <td><small><?= $control['id'] ?></small></td>
                                    <td><strong><?= $control['posicion_numero'] ?></strong></td>
                                    <td><span class="badge bg-info"><?= $control['receptor'] ?></span></td>
                                    <td><code><?= $control['codigo_control'] ?></code></td>
                                    <td>
                                        <?php if ($control['estado'] === 'asignado'): ?>
                                            <span class="badge bg-success">Asignado</span>
                                        <?php elseif ($control['estado'] === 'bloqueado'): ?>
                                            <span class="badge bg-danger">Bloqueado</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Disponible</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($control['apartamento']): ?>
                                            <span class="badge bg-dark">Blq<?= $control['torre'] ?>-<?= $control['apartamento'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($control['residente_nombre']): ?>
                                            <small><?= htmlspecialchars($control['residente_nombre'] ?? '') ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($control['fecha_asignacion']): ?>
                                            <small><?= date('d/m/Y', strtotime($control['fecha_asignacion'])) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($control['motivo_bloqueo']): ?>
                                            <small class="text-danger"><?= htmlspecialchars($control['motivo_bloqueo'] ?? '') ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
    window.location.href = URL_BASE + '/consultor/export-controles?' + params.toString();
}

function exportarPDF() {
    const params = new URLSearchParams(window.location.search);
    params.append('formato', 'pdf');
    window.location.href = URL_BASE + '/consultor/export-controles?' + params.toString();
}
</script>
JS;
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
