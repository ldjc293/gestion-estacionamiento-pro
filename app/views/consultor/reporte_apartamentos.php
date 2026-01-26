<?php
$pageTitle = 'Reporte de Apartamentos';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('consultor/dashboard')],
    ['label' => 'Reporte de Apartamentos', 'url' => '#']
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
                    <i class="bi bi-building"></i> Reporte de Apartamentos y Residentes
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
                        <label class="form-label">Bloque</label>
                        <select name="torre" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach (($bloques ?? []) as $t): ?>
                                <option value="<?= $t ?>" <?= ($_GET['torre'] ?? '') == $t ? 'selected' : '' ?>>Bloque <?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Estado Residente</label>
                        <select name="estado_residente" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="activo" <?= ($_GET['estado_residente'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= ($_GET['estado_residente'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Con Morosidad</label>
                        <select name="con_morosidad" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="si" <?= ($_GET['con_morosidad'] ?? '') === 'si' ? 'selected' : '' ?>>Sí</option>
                            <option value="no" <?= ($_GET['con_morosidad'] ?? '') === 'no' ? 'selected' : '' ?>>No</option>
                        </select>
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
                                <i class="bi bi-building"></i>
                            </div>
                            <div class="value"><?= $estadisticas['total_apartamentos'] ?? 0 ?></div>
                            <div class="label">Total Apartamentos</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="value"><?= $estadisticas['con_residentes'] ?? 0 ?></div>
                            <div class="label">Con Residentes</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="value"><?= $estadisticas['con_morosidad'] ?? 0 ?></div>
                            <div class="label">Con Morosidad</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(156, 163, 175, 0.1); color: #9ca3af;">
                                <i class="bi bi-inbox"></i>
                            </div>
                            <div class="value"><?= $estadisticas['sin_residentes'] ?? 0 ?></div>
                            <div class="label">Sin Residentes</div>
                        </div>
                    </div>
                </div>

                <!-- Resumen por Torre -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Resumen por Torre</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Bloque</th>
                                        <th>Total Aptos</th>
                                        <th>Con Residentes</th>
                                        <th>Controles Asignados</th>
                                        <th>Con Morosidad</th>
                                        <th>Ocupación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resumenTorres ?? [] as $torre): ?>
                                        <tr>
                                            <td><strong>Bloque <?= $torre['torre'] ?></strong></td>
                                            <td><?= $torre['total_aptos'] ?></td>
                                            <td><span class="badge bg-success"><?= $torre['con_residentes'] ?></span></td>
                                            <td><span class="badge bg-info"><?= $torre['controles_asignados'] ?></span></td>
                                            <td><span class="badge bg-warning"><?= $torre['con_morosidad'] ?></span></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-primary"
                                                         style="width: <?= $torre['porcentaje_ocupacion'] ?>%">
                                                        <?= $torre['porcentaje_ocupacion'] ?>%
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

                <!-- Tabla de Apartamentos -->
                <?php if (empty($apartamentos)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 80px;"></i>
                        <h5 class="mt-3">No hay apartamentos</h5>
                        <p class="text-muted">No se encontraron apartamentos con los filtros seleccionados</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="tableApartamentos">
                            <thead>
                                <tr>
                                    <th>Bloque</th>
                                    <th>Escalera</th>
                                    <th>Apartamento</th>
                                    <th>Residente Principal</th>
                                    <th>Contacto</th>
                                    <th>Controles</th>
                                    <th>Mensualidades Vencidas</th>
                                    <th>Deuda</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apartamentos as $apto): ?>
                                    <tr>
                                        <td><strong>Bloque <?= $apto['torre'] ?></strong></td>
                                        <td><span class="badge bg-light text-dark"><?= $apto['escalera'] ?></span></td>
                                        <td><span class="badge bg-secondary"><?= $apto['numero_apartamento'] ?></span></td>
                                        <td>
                                            <?php if ($apto['residente_nombre']): ?>
                                                <strong><?= htmlspecialchars($apto['residente_nombre'] ?? '') ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($apto['cedula'] ?? '') ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Sin residente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($apto['email']): ?>
                                                <small>
                                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($apto['email'] ?? '') ?><br>
                                                    <?php if ($apto['telefono']): ?>
                                                        <i class="bi bi-phone"></i> <?= htmlspecialchars($apto['telefono'] ?? '') ?>
                                                    <?php endif; ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $apto['total_controles'] ?? 0 ?> controles</span>
                                        </td>
                                        <td>
                                            <?php if ($apto['mensualidades_vencidas'] > 0): ?>
                                                <span class="badge bg-warning"><?= $apto['mensualidades_vencidas'] ?> meses</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Al día</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($apto['deuda_total'] > 0): ?>
                                                <strong class="text-danger"><?= formatUSD($apto['deuda_total']) ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">$0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($apto['usuario_activo']): ?>
                                                <?php if ($apto['mensualidades_vencidas'] >= MESES_BLOQUEO): ?>
                                                    <span class="badge bg-danger">Bloqueado</span>
                                                <?php elseif ($apto['mensualidades_vencidas'] > 0): ?>
                                                    <span class="badge bg-warning">Moroso</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
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
    window.location.href = URL_BASE + '/consultor/export-apartamentos?' + params.toString();
}

function exportarPDF() {
    const params = new URLSearchParams(window.location.search);
    params.append('formato', 'pdf');
    window.location.href = URL_BASE + '/consultor/export-apartamentos?' + params.toString();
}
</script>
JS;
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
