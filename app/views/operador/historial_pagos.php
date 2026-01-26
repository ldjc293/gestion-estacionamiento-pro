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
                                                    <?php 
                                                    $ext = strtolower(pathinfo($pago['comprobante_ruta'], PATHINFO_EXTENSION));
                                                    if ($ext === 'pdf'): 
                                                    ?>
                                                        <a href="<?= url($pago['comprobante_ruta']) ?>" target="_blank"
                                                           class="btn btn-outline-primary" title="Ver comprobante PDF">
                                                            <i class="bi bi-file-pdf"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-outline-primary" 
                                                                onclick="verComprobante('<?= url($pago['comprobante_ruta']) ?>')"
                                                                title="Ver comprobante">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                    <?php endif; ?>
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
                <img id="imgComprobante" src="" class="img-fluid" style="max-height: 80vh;" alt="Comprobante">
            </div>
            <div class="modal-footer">
                <a id="btnDescargarComprobante" href="#" download class="btn btn-primary">
                    <i class="bi bi-download"></i> Descargar
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function verDetalles(pagoId) {
    // Mostrar modal con spinner
    const modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
    const contenido = document.getElementById('detalles-contenido');

    contenido.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando detalles...</p>
        </div>
    `;

    modal.show();

    // Aquí podrías hacer una llamada AJAX para obtener los detalles completos
    // Por ahora, mostraremos un mensaje informativo
    setTimeout(() => {
        contenido.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>ID del Pago:</strong> #${pagoId}
            </div>
            <p>Para ver los detalles completos, contacta al administrador del sistema.</p>
        `;
    }, 500);
}

function verComprobante(url) {
    const modalElement = document.getElementById('modalComprobante');
    const modal = new bootstrap.Modal(modalElement);
    const img = document.getElementById('imgComprobante');
    const btnDescargar = document.getElementById('btnDescargarComprobante');
    
    img.src = url;
    btnDescargar.href = url;
    
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>