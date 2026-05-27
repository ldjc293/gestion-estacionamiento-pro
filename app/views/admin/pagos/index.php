<?php
$pageTitle = 'Gestión de Pagos';
require_once __DIR__ . '/../../layouts/header.php';
?>

<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../../layouts/alerts.php'; ?>

        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Gestión de Pagos</h4>
                    <p class="text-muted mb-0">Historial y administración de pagos recibidos</p>
                </div>
                <a href="<?= url('admin/registrar-pago') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Registrar Nuevo Pago
                </a>
            </div>

            <!-- Filtros -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="pendiente" <?= ($_GET['estado'] ?? '') == 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                                <option value="aprobado" <?= ($_GET['estado'] ?? '') == 'aprobado' ? 'selected' : '' ?>>Aprobados</option>
                                <option value="rechazado" <?= ($_GET['estado'] ?? '') == 'rechazado' ? 'selected' : '' ?>>Rechazados</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mes</label>
                            <select name="mes" class="form-select">
                                <option value="">Todos</option>
                                <?php for($i=1; $i<=12; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($_GET['mes'] ?? '') == $i ? 'selected' : '' ?>>
                                        <?= getNombreMes($i) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cliente</label>
                            <input type="text" name="cliente" class="form-control" placeholder="Nombre..." value="<?= htmlspecialchars($_GET['cliente'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-secondary w-100">
                                <i class="bi bi-filter"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Pagos -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Recibo</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Apartamento</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pagos)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            No se encontraron pagos
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pagos as $pago): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">
                                                #<?= $pago['numero_recibo'] ?>
                                            </td>
                                            <td>
                                                <?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?>
                                                <small class="d-block text-muted"><?= date('H:i', strtotime($pago['fecha_pago'])) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($pago['cliente_nombre'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($pago['apartamento'] ?? 'N/A') ?></td>
                                            <td>
                                                <div class="fw-bold text-dark">$<?= number_format($pago['monto_usd'], 2) ?></div>
                                                <small class="text-muted"><?= number_format($pago['monto_bs'], 2) ?> Bs</small>
                                            </td>
                                            <td>
                                                <?php
                                                    $metodos = [
                                                        'bs_pago_movil' => '<span class="badge bg-purple bg-opacity-10 text-purple">Pago Móvil</span>',
                                                        'bs_transferencia' => '<span class="badge bg-info bg-opacity-10 text-info">Transferencia</span>',
                                                        'bs_efectivo' => '<span class="badge bg-secondary bg-opacity-10 text-secondary">Efectivo Bs</span>',
                                                        'usd_efectivo' => '<span class="badge bg-success bg-opacity-10 text-success">Efectivo USD</span>'
                                                    ];
                                                    echo $metodos[$pago['moneda_pago']] ?? $pago['moneda_pago'];
                                                ?>
                                            </td>
                                            <td>
                                                <?php if($pago['estado_comprobante'] == 'aprobado'): ?>
                                                    <span class="badge bg-success">Aprobado</span>
                                                <?php elseif($pago['estado_comprobante'] == 'pendiente'): ?>
                                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                                <?php elseif($pago['estado_comprobante'] == 'rechazado'): ?>
                                                    <span class="badge bg-danger">Rechazado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= $pago['estado_comprobante'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="<?= url('admin/usuarios/pagos?id=' . ($pago['usuario_id'] ?? 0)) ?>">
                                                                <i class="bi bi-person me-2"></i> Ver Usuario
                                                            </a>
                                                        </li>
                                                        <?php if($pago['comprobante_ruta']): ?>
                                                            <li>
                                                                <a class="dropdown-item" href="<?= url($pago['comprobante_ruta']) ?>" target="_blank">
                                                                    <i class="bi bi-file-image me-2"></i> Ver Comprobante
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        
                                                        <?php if($pago['estado_comprobante'] == 'pendiente'): ?>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form action="<?= url('admin/pagos/aprobar') ?>" method="POST" style="display:inline;">
                                                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                                    <input type="hidden" name="pago_id" value="<?= $pago['id'] ?>">
                                                                    <button type="submit" class="dropdown-item text-success">
                                                                        <i class="bi bi-check-circle me-2"></i> Aprobar
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item text-danger" onclick="rechazarPago(<?= $pago['id'] ?>)">
                                                                    <i class="bi bi-x-circle me-2"></i> Rechazar
                                                                </button>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Paginación si fuera necesaria -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Rechazo -->
<div class="modal fade" id="modalRechazo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('admin/pagos/rechazar') ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Rechazar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="pago_id" id="pagoIdRechazo">
                    <div class="mb-3">
                        <label class="form-label">Motivo del rechazo</label>
                        <textarea name="motivo_rechazo" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rechazar Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function rechazarPago(id) {
    document.getElementById('pagoIdRechazo').value = id;
    new bootstrap.Modal(document.getElementById('modalRechazo')).show();
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

