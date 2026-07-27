<?php
$pageTitle = 'Pago Registrado';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('operador/dashboard')],
    ['label' => 'Registro de Pago', 'url' => url('operador/registrar-pago-presencial')],
    ['label' => 'Pago Exitoso', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <!-- Success Card -->
                    <div class="card shadow border-0 rounded-3 text-center p-4">
                        <div class="card-body">
                            <!-- Visual Checkmark -->
                            <div class="mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 80px; height: 80px;">
                                    <i class="bi bi-check-circle-fill" style="font-size: 48px;"></i>
                                </div>
                            </div>

                            <h3 class="fw-bold text-success mb-2">¡Pago Registrado Exitosamente!</h3>
                            <p class="text-muted small">El pago ha sido registrado y aprobado en el sistema de manera automática.</p>

                            <!-- Detalle del Pago -->
                            <div class="bg-light rounded-3 p-3 text-start my-4 border border-light">
                                <div class="row g-2">
                                    <div class="col-6 text-muted small">Nº de Recibo:</div>
                                    <div class="col-6 fw-bold text-end">#<?= htmlspecialchars($pago->numero_recibo) ?></div>

                                    <div class="col-6 text-muted small">Cliente:</div>
                                    <div class="col-6 fw-bold text-end"><?= htmlspecialchars($cliente['nombre_completo'] ?? 'N/A') ?></div>

                                    <div class="col-6 text-muted small">Apartamento:</div>
                                    <div class="col-6 fw-bold text-end"><?= htmlspecialchars($aptoInfo ?? 'N/A') ?></div>

                                    <div class="col-6 text-muted small">Fecha:</div>
                                    <div class="col-6 text-end"><?= date('d/m/Y H:i', strtotime($pago->fecha_pago)) ?></div>

                                    <div class="col-6 text-muted small">Moneda y Método:</div>
                                    <div class="col-6 text-end">
                                        <?php
                                        $metodos = [
                                            'usd_efectivo' => 'USD Efectivo',
                                            'bs_transferencia' => 'Transferencia Bs',
                                            'bs_efectivo' => 'Bs Efectivo',
                                            'bs_pago_movil' => 'Pago Móvil Bs'
                                        ];
                                        echo $metodos[$pago->moneda_pago] ?? ucfirst(str_replace('_', ' ', $pago->moneda_pago));
                                        ?>
                                    </div>

                                    <div class="col-12"><hr class="my-2 text-muted opacity-25"></div>

                                    <div class="col-6 fw-bold text-dark">Monto Pagado:</div>
                                    <div class="col-6 text-end">
                                        <h4 class="mb-0 fw-bold text-primary">
                                            <?php if ($pago->moneda_pago === 'usd_efectivo'): ?>
                                                <?= formatUSD($pago->monto_usd) ?>
                                            <?php else: ?>
                                                <?= formatBs($pago->monto_bs) ?>
                                            <?php endif; ?>
                                        </h4>
                                    </div>
                                </div>
                            </div>

                            <!-- Acciones del Recibo -->
                            <div class="d-grid gap-2 mb-4">
                                <!-- Botón Descargar Recibo PDF -->
                                <a href="<?= url('operador/descargar-recibo?id=' . $pago->id) ?>" class="btn btn-success btn-lg py-3 fw-bold">
                                    <i class="bi bi-file-earmark-pdf"></i> Descargar Recibo Oficial (PDF)
                                </a>

                                <!-- Botón Ver Comprobante si Existe -->
                                <?php if (!empty($pago->comprobante_ruta)): ?>
                                    <button type="button" class="btn btn-outline-primary py-2" id="btnVerComprobante">
                                        <i class="bi bi-file-image"></i> Ver Comprobante de Operación
                                    </button>
                                <?php endif; ?>
                            </div>

                            <!-- Botones Secundarios -->
                            <div class="d-flex justify-content-between gap-2 border-top pt-3">
                                <a href="<?= url('operador/dashboard') ?>" class="btn btn-outline-secondary w-50">
                                    <i class="bi bi-house"></i> Volver a Inicio
                                </a>
                                <a href="<?= url('operador/registrar-pago-presencial') ?>" class="btn btn-primary w-50">
                                    <i class="bi bi-plus-circle"></i> Registrar Otro Pago
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Previsualización Comprobante -->
<?php if (!empty($pago->comprobante_ruta)): ?>
<div class="modal fade" id="modalComprobante" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-image"></i> Comprobante de Pago
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0 bg-light">
                <img src="<?= url($pago->comprobante_ruta) ?>" class="img-fluid" style="max-height: 80vh;" alt="Comprobante">
            </div>
            <div class="modal-footer">
                <a href="<?= url($pago->comprobante_ruta) ?>" download class="btn btn-primary">
                    <i class="bi bi-download"></i> Descargar
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalComprobante'));
    const btnVer = document.getElementById('btnVerComprobante');
    if (btnVer) {
        btnVer.addEventListener('click', function() {
            modal.show();
        });
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
