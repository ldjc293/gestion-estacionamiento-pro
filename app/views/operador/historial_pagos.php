<?php
$pageTitle = 'Historial de Pagos';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('operador/dashboard')],
    ['label' => 'Historial de Pagos', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-funnel"></i> Filtros de Búsqueda
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="aprobado" <?= ($_GET['estado'] ?? '') === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                            <option value="pendiente" <?= ($_GET['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="rechazado" <?= ($_GET['estado'] ?? '') === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mes</label>
                        <select name="mes" class="form-select">
                            <option value="">Todos los meses</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= ($_GET['mes'] ?? '') == $m ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Año</label>
                        <select name="anio" class="form-select">
                            <option value="">Todos los años</option>
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= ($_GET['anio'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cliente</label>
                        <input type="text" name="cliente" class="form-control"
                               value="<?= htmlspecialchars($_GET['cliente'] ?? '') ?>"
                               placeholder="Buscar por nombre...">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Aplicar Filtros
                        </button>
                        <a href="<?= url('operador/historial-pagos') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Pagos -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-clock-history"></i> Historial de Pagos
                </h6>
                <span class="badge bg-primary" style="font-size: 14px;">
                    <?= is_array($pagos) ? count($pagos) : 0 ?> pagos encontrados
                </span>
            </div>
            <div class="card-body">
                <?php if (!is_array($pagos) || count($pagos) === 0): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-clock-history text-muted" style="font-size: 80px;"></i>
                        <h5 class="mt-3">No se encontraron pagos</h5>
                        <p class="text-muted">Intenta cambiar los filtros de búsqueda</p>
                    </div>
                <?php else: ?>
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
                                    <th>Estado</th>
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
                                            <strong><?= htmlspecialchars($pago['cliente_nombre'] ?? 'N/A') ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= htmlspecialchars($pago['apartamento'] ?? 'N/A') ?></span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($pago['fecha_pago'])) ?></td>
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
                                                'bs_efectivo' => 'Bs Efectivo'
                                            ];
                                            echo $metodos[$pago['moneda_pago']] ?? ucfirst(str_replace('_', ' ', $pago['moneda_pago'] ?? 'Desconocido'));
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $estados = [
                                                'aprobado' => '<span class="badge bg-success">Aprobado</span>',
                                                'pendiente' => '<span class="badge bg-warning">Pendiente de Aprobación</span>',
                                                'rechazado' => '<span class="badge bg-danger">Rechazado</span>',
                                                'no_aplica' => '<span class="badge bg-info">Aprobado Automáticamente</span>'
                                            ];
                                            echo $estados[$pago['estado_comprobante']] ?? '<span class="badge bg-secondary">' . htmlspecialchars($pago['estado_comprobante'] ?? 'Desconocido') . '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($pago['estado_comprobante'] === 'aprobado'): ?>
                                                    <a href="<?= url('operador/descargar-recibo?id=' . $pago['id']) ?>"
                                                       class="btn btn-outline-success" title="Descargar Recibo">
                                                        <i class="bi bi-file-earmark-pdf"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($pago['comprobante_ruta']): ?>
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="verComprobante('<?= url($pago['comprobante_ruta']) ?>')"
                                                            title="Ver comprobante">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($pago['estado_comprobante'] === 'pendiente' || $pago['estado_comprobante'] === 'no_aplica'): ?>
                                                    <a href="<?= url('operador/revisar-pago?id=' . $pago['id']) ?>"
                                                       class="btn btn-primary" title="Revisar pago">
                                                        <i class="bi bi-search"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <button type="button" class="btn btn-outline-info"
                                                        onclick="verDetalles(<?= $pago['id'] ?>)"
                                                        title="Ver detalles">
                                                    <i class="bi bi-info-circle"></i>
                                                </button>
                                            </div>
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

<!-- Modal de Detalles -->
<div class="modal fade" id="modalDetalles" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalles-contenido">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Previsualización Comprobante -->
<div class="modal fade" id="modalComprobante" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    <i class="bi bi-file-earmark-image me-2"></i> Comprobante de Pago
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3 bg-light">
                <div id="wrapperImgH">
                    <img id="imgComprobante" src="" class="img-fluid rounded shadow-sm" style="max-height: 75vh;" alt="Comprobante">
                </div>
                <div id="wrapperPdfH" class="d-none" style="height: 75vh;">
                    <iframe id="pdfComprobante" src="" style="width:100%; height:100%; border:none;" class="rounded shadow-sm"></iframe>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <a id="btnDescargarComprobante" href="#" target="_blank" download class="btn btn-primary btn-sm">
                    <i class="bi bi-download"></i> Abrir / Descargar
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function verDetalles(pagoId) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
    const contenido = document.getElementById('detalles-contenido');

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
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="verComprobante('${fullUrl}')">
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
    const imgWrapper = document.getElementById('wrapperImgH');
    const pdfWrapper = document.getElementById('wrapperPdfH');
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