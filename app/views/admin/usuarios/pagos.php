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
                                                <button type="button" class="btn btn-sm btn-outline-info me-1"
                                                        onclick="verDetalles(<?= $pago['id'] ?>)"
                                                        title="Ver detalles de la operación">
                                                    <i class="bi bi-info-circle me-1"></i> Detalles
                                                </button>
                                                <?php if(!empty($pago['comprobante_ruta'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                            onclick="verComprobanteUniversal('<?= url($pago['comprobante_ruta']) ?>', 'Comprobante de Pago')"
                                                            title="Ver Comprobante">
                                                        <i class="bi bi-eye-fill me-1"></i> Comprobante
                                                    </button>
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

<!-- Modal Visor de Comprobante Universal -->
<div class="modal fade" id="modalComprobanteUniversal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalComprobanteUniversalLabel"><i class="bi bi-file-earmark-image me-2"></i>Comprobante de Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-light p-3">
                <div id="wrapperImg" class="d-none">
                    <img id="imgComprobanteUniversal" src="" class="img-fluid rounded shadow-sm" style="max-height: 75vh;">
                </div>
                <div id="wrapperPdf" class="d-none" style="height: 75vh;">
                    <iframe id="pdfComprobanteUniversal" src="" style="width: 100%; height: 100%; border: none;" class="rounded shadow-sm"></iframe>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <a id="btnDescargarUniversal" href="#" target="_blank" download class="btn btn-primary btn-sm">
                    <i class="bi bi-download me-1"></i> Abrir / Descargar
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function verComprobanteUniversal(src, title) {
    const modalEl = document.getElementById('modalComprobanteUniversal');
    if (!modalEl) return;

    const modal = new bootstrap.Modal(modalEl);
    const imgWrapper = document.getElementById('wrapperImg');
    const pdfWrapper = document.getElementById('wrapperPdf');
    const img = document.getElementById('imgComprobanteUniversal');
    const pdf = document.getElementById('pdfComprobanteUniversal');
    const label = document.getElementById('modalComprobanteUniversalLabel');
    const btnDescargar = document.getElementById('btnDescargarUniversal');

    label.textContent = title || 'Comprobante de Pago';
    btnDescargar.href = src;

    const cleanSrc = src.split('?')[0].toLowerCase();
    const isPDF = cleanSrc.endsWith('.pdf');

    if (isPDF) {
        imgWrapper.classList.add('d-none');
        img.src = '';
        pdf.src = src;
        pdfWrapper.classList.remove('d-none');
    } else {
        pdfWrapper.classList.add('d-none');
        pdf.src = '';
        img.src = src;
        imgWrapper.classList.remove('d-none');
    }

    modal.show();
}

function verDetalles(pagoId) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetallesOperacion'));
    const contenido = document.getElementById('detalles-operacion-contenido');

    contenido.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 text-muted">Cargando detalles de la operación...</p>
        </div>
    `;

    modal.show();

    fetch('<?= url('api/get-detalle-pago') ?>?id=' + pagoId)
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.pago) {
                contenido.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        ${data.message || 'No se pudieron cargar los detalles del pago.'}
                    </div>
                `;
                return;
            }

            const p = data.pago;
            
            let estadoBadge = '<span class="badge bg-secondary">Desconocido</span>';
            if (p.estado_comprobante === 'aprobado' || p.estado_comprobante === 'no_aplica') {
                estadoBadge = '<span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i> Aprobado</span>';
            } else if (p.estado_comprobante === 'pendiente') {
                estadoBadge = '<span class="badge bg-warning text-dark fs-6"><i class="bi bi-hourglass-split me-1"></i> Pendiente</span>';
            } else if (p.estado_comprobante === 'rechazado') {
                estadoBadge = '<span class="badge bg-danger fs-6"><i class="bi bi-x-circle me-1"></i> Rechazado</span>';
            }

            let mesesHtml = '<span class="text-muted">No especificado</span>';
            if (p.mensualidades && p.mensualidades.length > 0) {
                mesesHtml = '<div class="d-flex flex-wrap gap-1">';
                p.mensualidades.forEach(m => {
                    mesesHtml += `<span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-calendar-check text-success me-1"></i>${m.texto} ($${parseFloat(m.monto_aplicado_usd).toFixed(2)})</span>`;
                });
                mesesHtml += '</div>';
            }

            let comprobanteBtnHtml = '';
            if (p.comprobante_ruta) {
                const appUrl = '<?= url('') ?>'.replace(/\/$/, '');
                const rutaClean = p.comprobante_ruta.startsWith('/') ? p.comprobante_ruta : '/' + p.comprobante_ruta;
                const fullUrl = appUrl + rutaClean;
                comprobanteBtnHtml = `
                    <div class="mt-3 pt-3 border-top">
                        <strong>Comprobante adjunto:</strong>
                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="verComprobanteUniversal('${fullUrl}', 'Comprobante')">
                                <i class="bi bi-file-earmark-image me-1"></i> Ver / Previsualizar Comprobante
                            </button>
                        </div>
                    </div>
                `;
            }

            let motivoRechazoHtml = '';
            if (p.estado_comprobante === 'rechazado' && p.motivo_rechazo) {
                motivoRechazoHtml = `
                    <div class="alert alert-danger mt-3 mb-0">
                        <strong><i class="bi bi-exclamation-triangle me-1"></i> Motivo del rechazo:</strong>
                        <p class="mb-0 mt-1">${p.motivo_rechazo}</p>
                    </div>
                `;
            }

            contenido.innerHTML = `
                <div class="card border-0 shadow-sm mb-3 bg-light">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="text-primary mb-0 fw-bold">
                                    <i class="bi bi-receipt me-1"></i> Recibo: ${p.numero_recibo ? p.numero_recibo : '#' + String(p.id).padStart(5, '0')}
                                </h6>
                                <small class="text-muted">ID Transacción: #${p.id}</small>
                            </div>
                            <div>${estadoBadge}</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100 bg-white">
                            <h6 class="fw-bold border-bottom pb-2 mb-2 text-secondary">
                                <i class="bi bi-person me-1"></i> Datos del Cliente
                            </h6>
                            <p class="mb-1"><strong>Nombre:</strong> ${p.cliente_nombre || 'N/A'}</p>
                            <p class="mb-1"><strong>Cédula:</strong> ${p.cliente_cedula || 'N/A'}</p>
                            <p class="mb-0"><strong>Ubicación:</strong> ${p.apartamento || 'N/A'}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100 bg-white">
                            <h6 class="fw-bold border-bottom pb-2 mb-2 text-secondary">
                                <i class="bi bi-cash-stack me-1"></i> Montos y Método
                            </h6>
                            <p class="mb-1"><strong>Método:</strong> ${p.metodo_pago_label}</p>
                            <p class="mb-1"><strong>Monto en USD:</strong> <span class="text-success fw-bold">$${parseFloat(p.monto_usd).toFixed(2)}</span></p>
                            <p class="mb-1"><strong>Monto en Bs:</strong> <span class="fw-bold">${parseFloat(p.monto_bs || 0).toLocaleString('es-VE', {minimumFractionDigits:2})} Bs.</span></p>
                            ${p.tasa_cambio_valor ? `<p class="mb-0 text-muted small"><strong>Tasa BCV:</strong> ${parseFloat(p.tasa_cambio_valor).toFixed(2)} Bs/$</p>` : ''}
                        </div>
                    </div>
                </div>

                <div class="border rounded p-3 mt-3 bg-white">
                    <h6 class="fw-bold border-bottom pb-2 mb-2 text-secondary">
                        <i class="bi bi-info-circle me-1"></i> Detalles Operativos
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Fecha de Pago:</strong> ${p.fecha_pago_formateada}</p>
                            <p class="mb-1"><strong>Referencia / Notas:</strong> ${p.notas ? `<code>${p.notas}</code>` : '<span class="text-muted">Sin referencia</span>'}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Registrado por:</strong> ${p.registrado_por_nombre || 'Cliente'}</p>
                            ${p.aprobado_por_nombre ? `<p class="mb-1"><strong>Procesado por:</strong> ${p.aprobado_por_nombre} (${p.fecha_aprobacion_formateada || ''})</p>` : ''}
                        </div>
                    </div>
                </div>

                <div class="border rounded p-3 mt-3 bg-white">
                    <h6 class="fw-bold border-bottom pb-2 mb-2 text-secondary">
                        <i class="bi bi-calendar-range me-1"></i> Meses Abonados / Pagados
                    </h6>
                    ${mesesHtml}
                </div>

                ${motivoRechazoHtml}
                ${comprobanteBtnHtml}
            `;
        })
        .catch(err => {
            console.error('Error al obtener detalles del pago:', err);
            contenido.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Ocurrió un error al intentar consultar la información del pago.
                </div>
            `;
        });
}
</script>

<!-- Modal Detalles Operación -->
<div class="modal fade" id="modalDetallesOperacion" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Detalles de la Operación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalles-operacion-contenido">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

