<?php
$pageTitle = 'Pagos del Usuario';
require_once __DIR__ . '/../../layouts/header.php';
?>

<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../../layouts/alerts.php'; ?>

        <div class="container-fluid">
            <!-- Header -->
            <div class="mb-4">
                <a href="<?= url('admin/usuarios') ?>" class="text-decoration-none text-muted mb-2 d-inline-block">
                    <i class="bi bi-arrow-left"></i> Volver a usuarios
                </a>
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h4 class="mb-1">Estado de Cuenta</h4>
                        <p class="text-muted mb-0">Usuario: <?= htmlspecialchars($cliente->nombre_completo) ?></p>
                    </div>
                    <a href="<?= url('admin/pagos/registrar?buscar=' . $cliente->cedula) ?>" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Registrar Pago
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Tarjetas de Resumen -->
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Deuda Total</h6>
                            <h2 class="mb-0">$<?= number_format($deuda['total_usd'] ?? 0, 2) ?></h2>
                            <p class="mb-0 mt-2 small text-white-50">
                                <?= $deuda['meses_pendientes'] ?? 0 ?> meses pendientes
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8 mb-4">
                     <!-- Detalle de Deuda -->
                     <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">Mensualidades Pendientes</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Mes/Año</th>
                                            <th class="text-end">Monto</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($pendientes)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-3 text-success">
                                                    <i class="bi bi-check-circle me-1"></i> Al día
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach($pendientes as $p): ?>
                                                <tr>
                                                    <td><?= getNombreMes($p['mes']) ?>/<?= $p['anio'] ?></td>
                                                    <td class="text-end">$<?= number_format($p['monto_usd'], 2) ?></td>
                                                    <td><span class="badge bg-danger">Pendiente</span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                     </div>
                </div>
            </div>

            <!-- Historial de Pagos -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Historial de Pagos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Recibo</th>
                                    <th>Fecha</th>
                                    <th>Monto USD</th>
                                    <th>Monto Bs</th>
                                    <th>Tasa</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pagos)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            No hay pagos registrados
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pagos as $pago): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">#<?= $pago['numero_recibo'] ?></td>
                                            <td><?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?></td>
                                            <td class="fw-bold text-success">$<?= number_format($pago['monto_usd'], 2) ?></td>
                                            <td><?= number_format($pago['monto_bs'], 2) ?> Bs</td>
                                            <td><?= number_format($pago['tasa_usd_bs'] ?? 0, 2) ?></td>
                                            <td>
                                                <?php if($pago['estado_comprobante'] == 'aprobado'): ?>
                                                    <span class="badge bg-success">Aprobado</span>
                                                <?php elseif($pago['estado_comprobante'] == 'pendiente'): ?>
                                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= $pago['estado_comprobante'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($pago['comprobante_ruta']): ?>
                                                    <a href="<?= url($pago['comprobante_ruta']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

