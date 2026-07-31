<?php
$pageTitle = 'Pagos Pendientes';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('operador/dashboard')],
    ['label' => 'Pagos Pendientes', 'url' => '#']
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
                    <i class="bi bi-hourglass-split"></i> Pagos Pendientes de Aprobación
                </h6>
                <span class="badge bg-warning" style="font-size: 14px;">
                    <?= is_array($pagos) ? count($pagos) : 0 ?> pendientes
                </span>
            </div>
            <div class="card-body">
                <?php if (!is_array($pagos) || count($pagos) === 0): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-check-circle text-success" style="font-size: 80px;"></i>
                        <h5 class="mt-3">¡Todo al día!</h5>
                        <p class="text-muted">No hay pagos pendientes de aprobación</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i>
                        <strong>Importante:</strong> Revisa cada comprobante cuidadosamente antes de aprobar o rechazar.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Apartamento</th>
                                    <th>Fecha Pago</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Referencia</th>
                                    <th>Comprobante</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (is_array($pagos) ? $pagos : [] as $pago): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">#<?= str_pad($pago['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($pago['cliente_nombre']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $pago['apartamento'] ?></span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?></td>
                                        <td>
                                            <strong class="text-primary">
                                                <?php if ($pago['moneda_pago'] === 'usd_efectivo'): ?>
                                                    <?= formatUSD($pago['monto_usd']) ?>
                                                <?php else: ?>
                                                    <?= formatBs($pago['monto_bs']) ?>
                                                <?php endif; ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php
                                            $metodos = [
                                                'usd_efectivo' => 'USD Efectivo',
                                                'bs_transferencia' => 'Transferencia Bs',
                                                'bs_pago_movil' => 'Pago Móvil',
                                                'bs_efectivo' => 'Bs Efectivo'
                                            ];
                                            ?>
                                            <?= $metodos[$pago['moneda_pago']] ?? ucfirst($pago['moneda_pago']) ?>
                                        </td>
                                        <td>
                                            <?php if ($pago['notas']): ?>
                                                <code><?= htmlspecialchars($pago['notas']) ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($pago['comprobante_ruta']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="verComprobante('<?= url($pago['comprobante_ruta']) ?>')">
                                                    <i class="bi bi-eye"></i> Ver
                                                </button>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin comprobante</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= url('operador/revisar-pago?id=' . $pago['id']) ?>"
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-search"></i> Revisar
                                            </a>
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

<!-- Modal para ver comprobante -->
<div class="modal fade" id="modalComprobante" tabindex="-1" aria-labelledby="modalComprobanteTitle">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalComprobanteTitle">Comprobante de Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3 bg-light">
                <div id="wrapperImgOp">
                    <img id="imgComprobante" src="" class="img-fluid rounded" style="max-height: 75vh;" alt="Comprobante">
                </div>
                <div id="wrapperPdfOp" class="d-none" style="height: 75vh;">
                    <iframe id="pdfComprobante" src="" style="width:100%; height:100%; border:none;" class="rounded"></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <a id="btnDescargarComprobante" href="#" target="_blank" download class="btn btn-primary btn-sm">
                    <i class="bi bi-download"></i> Abrir / Descargar
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function verComprobante(url) {
    const modalElement = document.getElementById('modalComprobante');
    if (!modalElement) return;

    if (!modalElement.dataset.blurListenerAdded) {
        modalElement.addEventListener('hide.bs.modal', function () {
            if (document.activeElement && modalElement.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });
        modalElement.dataset.blurListenerAdded = 'true';
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const imgWrapper = document.getElementById('wrapperImgOp');
    const pdfWrapper = document.getElementById('wrapperPdfOp');
    const img = document.getElementById('imgComprobante');
    const pdf = document.getElementById('pdfComprobante');
    const btnDescargar = document.getElementById('btnDescargarComprobante');
    
    btnDescargar.href = url;

    const cleanUrl = url.split('?')[0].toLowerCase();
    const isPDF = cleanUrl.endsWith('.pdf');

    if (isPDF) {
        imgWrapper.classList.add('d-none');
        img.src = '';
        pdf.src = url;
        pdfWrapper.classList.remove('d-none');
    } else {
        pdfWrapper.classList.add('d-none');
        pdf.src = '';
        img.src = url;
        imgWrapper.classList.remove('d-none');
    }
    
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
