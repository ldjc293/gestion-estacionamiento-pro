<?php
$pageTitle = 'Revisar Pago';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('operador/dashboard')],
    ['label' => 'Pagos Pendientes', 'url' => url('operador/pagos-pendientes')],
    ['label' => 'Revisar', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="row">
            <div class="col-md-8">
                <!-- Información del Pago -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-receipt"></i> Detalles del Pago #<?= str_pad($pago->id, 5, '0', STR_PAD_LEFT) ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted">Cliente</small>
                                <h5><?= htmlspecialchars($cliente->nombre_completo) ?></h5>
                                <p class="mb-0">
                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($cliente->email) ?><br>
                                    <?php if ($cliente->telefono): ?>
                                        <i class="bi bi-phone"></i> <?= htmlspecialchars($cliente->telefono) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Fecha de Pago</small>
                                <h5><?= date('d/m/Y', strtotime($pago->fecha_pago)) ?></h5>
                                <p class="text-muted mb-0">
                                    Registrado: <?= date('d/m/Y H:i', strtotime($pago->fecha_pago)) ?>
                                </p>
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <small class="text-muted">Monto Pagado</small>
                                <h3 class="text-primary mb-0">
                                    <?php if ($pago->moneda_pago === 'usd_efectivo'): ?>
                                        <?= formatUSD($pago->monto_usd) ?>
                                    <?php else: ?>
                                        <?= formatBs($pago->monto_bs) ?>
                                    <?php endif; ?>
                                </h3>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Método de Pago</small>
                                <div>
                                    <?php
                                    $metodos = [
                                        'usd_efectivo' => ['label' => 'USD Efectivo', 'icon' => 'cash'],
                                        'bs_transferencia' => ['label' => 'Transferencia Bs', 'icon' => 'bank'],
                                        'bs_pago_movil' => ['label' => 'Pago Móvil', 'icon' => 'phone'],
                                        'bs_efectivo' => ['label' => 'Bs Efectivo', 'icon' => 'cash']
                                    ];
                                    $metodo = $metodos[$pago->moneda_pago] ?? ['label' => $pago->moneda_pago, 'icon' => 'credit-card'];
                                    ?>
                                    <strong>
                                        <i class="bi bi-<?= $metodo['icon'] ?>"></i>
                                        <?= $metodo['label'] ?>
                                    </strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Referencia</small>
                                <div>
                                    <?php if ($pago->notas): ?>
                                        <code><?= htmlspecialchars($pago->notas) ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">Sin referencia</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Mensualidades Cubiertas -->
                        <h6 class="mb-3">
                            <i class="bi bi-calendar-check"></i> Mensualidades a Pagar
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Mes/Año</th>
                                        <th>Monto USD</th>
                                        <th>Monto Aplicado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mensualidades as $mens): ?>
                                        <tr>
                                            <td><?= date('F Y', mktime(0, 0, 0, $mens['mes'], 1, $mens['anio'])) ?></td>
                                            <td><?= formatUSD($mens['monto_usd']) ?></td>
                                            <td><strong><?= formatUSD($mens['monto_aplicado_usd']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Comprobante -->
                <?php if ($pago->comprobante_ruta): ?>
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-file-earmark-image"></i> Comprobante de Pago
                            </h6>
                        </div>
                        <div class="card-body text-center">
                            <?php
                            $extension = pathinfo($pago->comprobante_ruta, PATHINFO_EXTENSION);
                            $isPDF = strtolower($extension) === 'pdf';
                            ?>

                            <?php if ($isPDF): ?>
                                <a href="<?= url($pago->comprobante_ruta) ?>" target="_blank" class="btn btn-lg btn-primary">
                                    <i class="bi bi-file-pdf"></i> Ver Comprobante PDF
                                </a>
                            <?php else: ?>
                                <img src="<?= url($pago->comprobante_ruta) ?>"
                                     alt="Comprobante"
                                     class="img-fluid rounded"
                                     style="max-height: 600px;">
                                <div class="mt-3">
                                    <a href="<?= url($pago->comprobante_ruta) ?>" target="_blank" class="btn btn-primary">
                                        <i class="bi bi-arrows-fullscreen"></i> Ver en Tamaño Completo
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar - Acciones -->
            <div class="col-md-4">
                <!-- Decisión -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="bi bi-hand-thumbs-up"></i> Decisión
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Aprobar -->
                        <div id="approve-form-container">
                            <form action="<?= url('operador/aprobar-pago') ?>" method="POST" id="approve-form" class="no-auto-disable" style="display: none;">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="pago_id" value="<?= $pago->id ?>">
                            </form>
                            <button type="button" class="btn btn-success w-100 mb-3" data-bs-toggle="modal" data-bs-target="#modalAprobar">
                                <i class="bi bi-check-circle"></i> Aprobar Pago
                            </button>
                        </div>


                        <!-- Rechazar -->
                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#modalRechazar">
                            <i class="bi bi-x-circle"></i> Rechazar Pago
                        </button>
                    </div>
                </div>

                <!-- Historial del Cliente -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-clock-history"></i> Historial del Cliente
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($otrosPagos)): ?>
                            <p class="text-muted mb-0">Primer pago del cliente</p>
                        <?php else: ?>
                            <small class="text-muted">Últimos pagos:</small>
                            <?php foreach (array_slice($otrosPagos, 0, 5) as $otroPago): ?>
                                <div class="border-bottom py-2">
                                    <div class="d-flex justify-content-between">
                                        <small><?= date('d/m/Y', strtotime($otroPago->fecha_pago)) ?></small>
                                        <?php if ($otroPago->estado_comprobante === 'aprobado'): ?>
                                            <span class="badge bg-success">Aprobado</span>
                                        <?php elseif ($otroPago->estado_comprobante === 'rechazado'): ?>
                                            <span class="badge bg-danger">Rechazado</span>
                                        <?php elseif ($otroPago->estado_comprobante === 'no_aplica'): ?>
                                            <span class="badge bg-info">Aprobado Automáticamente</span>
                                        <?php elseif ($otroPago->estado_comprobante === 'pendiente'): ?>
                                            <span class="badge bg-warning">Pendiente de Aprobación</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= ucfirst($otroPago->estado_comprobante) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong><?= formatUSD($otroPago->monto_usd) ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aprobar -->
<div class="modal fade" id="modalAprobar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle"></i> Aprobar Pago
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Confirmación:</strong> ¿Estás seguro de aprobar este pago?
                </div>

                <div class="mb-3">
                    <strong>Detalles del pago:</strong>
                    <ul class="list-unstyled mt-2">
                        <li><i class="bi bi-person"></i> Cliente: <?= htmlspecialchars($cliente->nombre_completo) ?></li>
                        <li><i class="bi bi-cash"></i> Monto: <?php if ($pago->moneda_pago === 'usd_efectivo'): ?><?= formatUSD($pago->monto_usd) ?><?php else: ?><?= formatBs($pago->monto_bs) ?><?php endif; ?></li>
                        <li><i class="bi bi-calendar"></i> Fecha: <?= date('d/m/Y', strtotime($pago->fecha_pago)) ?></li>
                    </ul>
                </div>

                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    Al aprobar, se generará el recibo automáticamente y se notificará al cliente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="aprobarPagoConfirmado()">
                    <i class="bi bi-check-circle"></i> Sí, Aprobar Pago
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rechazar -->
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('operador/rechazar-pago') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="pago_id" value="<?= $pago->id ?>">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-x-circle"></i> Rechazar Pago
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Importante:</strong> El cliente será notificado del rechazo.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Motivo del Rechazo *</label>
                        <textarea class="form-control"
                                  name="motivo_rechazo"
                                  rows="4"
                                  required
                                  placeholder="Explica claramente por qué se rechaza el pago"></textarea>
                        <small class="text-muted">El cliente verá este mensaje</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> Rechazar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function aprobarPagoConfirmado() {
    console.log('Aprobando pago confirmado');

    // Cerrar el modal
    var modal = bootstrap.Modal.getInstance(document.getElementById('modalAprobar'));
    modal.hide();

    // Enviar el formulario
    document.getElementById('approve-form').submit();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
