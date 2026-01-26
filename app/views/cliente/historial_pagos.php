<?php
$pageTitle = 'Historial de Pagos';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('cliente/dashboard')],
    ['label' => 'Historial de Pagos', 'url' => '#']
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
                    <i class="bi bi-clock-history"></i> Historial de Pagos
                </h6>
                <a href="<?= url('cliente/registrar-pago') ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Nuevo Pago
                </a>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="pendiente" <?= ($_GET['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="aprobado" <?= ($_GET['estado'] ?? '') === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                            <option value="rechazado" <?= ($_GET['estado'] ?? '') === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mes</label>
                        <select name="mes" class="form-select">
                            <option value="">Todos</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= ($_GET['mes'] ?? '') == $i ? 'selected' : '' ?>>
                                    <?= getNombreMesEspanol($i) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Año</label>
                        <select name="anio" class="form-select">
                            <option value="">Todos</option>
                            <?php for ($year = date('Y'); $year >= date('Y') - 3; $year--): ?>
                                <option value="<?= $year ?>" <?= ($_GET['anio'] ?? '') == $year ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="<?= url('cliente/historial-pagos') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x"></i> Limpiar
                        </a>
                    </div>
                </form>

                <!-- Tabla de pagos -->
                <?php if (empty($pagos)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 64px; color: #cbd5e1;"></i>
                        <p class="text-muted mt-3">No hay pagos registrados</p>
                        <a href="<?= url('cliente/registrar-pago') ?>" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Registrar Primer Pago
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha Pago</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Referencia</th>
                                    <th>Estado</th>
                                    <th>Recibo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagos as $pago): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">#<?= str_pad($pago->id, 5, '0', STR_PAD_LEFT) ?></span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($pago->fecha_pago)) ?></td>
                                        <td>
                                            <strong>
                                                <?php 
                                                $isUsd = strpos($pago->moneda_pago, 'usd') !== false;
                                                $montoMostrar = $isUsd ? $pago->monto_usd : $pago->monto_bs;
                                                echo $isUsd ? formatUSD($montoMostrar) : formatBs($montoMostrar);
                                                ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php 
                                            // Formatear método de pago desde moneda_pago (ej. usd_zelle -> USD Zelle)
                                            $parts = explode('_', $pago->moneda_pago);
                                            if (count($parts) >= 2) {
                                                echo strtoupper($parts[0]) . ' ' . ucfirst($parts[1]);
                                            } else {
                                                echo ucfirst($pago->moneda_pago);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (isset($pago->referencia) && $pago->referencia): ?>
                                                <code><?= htmlspecialchars($pago->referencia) ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($pago->estado_comprobante === 'aprobado'): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Aprobado
                                                </span>
                                            <?php elseif ($pago->estado_comprobante === 'rechazado'): ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-x-circle"></i> Rechazado
                                                </span>
                                            <?php elseif ($pago->estado_comprobante === 'no_aplica'): ?>
                                                <span class="badge bg-info">
                                                    <i class="bi bi-check-circle"></i> Aprobado Automáticamente
                                                </span>
                                            <?php elseif ($pago->estado_comprobante === 'pendiente'): ?>
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-hourglass-split"></i> Pendiente de Aprobación
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-question-circle"></i> <?= ucfirst($pago->estado_comprobante) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($pago->numero_recibo): ?>
                                                <span class="badge bg-info"><?= $pago->numero_recibo ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('cliente/ver-pago?id=' . $pago->id) ?>"
                                                   class="btn btn-outline-primary"
                                                   title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($pago->estado_comprobante === 'aprobado' && $pago->numero_recibo): ?>
                                                    <a href="<?= url('cliente/descargar-recibo?id=' . $pago->id) ?>"
                                                       class="btn btn-outline-success"
                                                       title="Descargar recibo">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Estadísticas del historial -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-2">Total Pagos</h6>
                                <h4><?= count($pagos) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-2">Aprobados</h6>
                                <h4 class="text-success">
                                    <?= count(array_filter($pagos, fn($p) => $p->estado_comprobante === 'aprobado' || $p->estado_comprobante === 'no_aplica')) ?>
                                </h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-2">Pendientes</h6>
                                <h4 class="text-warning">
                                    <?= count(array_filter($pagos, fn($p) => $p->estado_comprobante === 'pendiente')) ?>
                                </h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-muted mb-2">Rechazados</h6>
                                <h4 class="text-danger">
                                    <?= count(array_filter($pagos, fn($p) => $p->estado_comprobante === 'rechazado')) ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
