<?php
$pageTitle = 'Historial de Gastos';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url($usuarioRol . '/dashboard')],
    ['label' => 'Historial de Gastos', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="container-fluid">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border-start border-4 border-danger">
                        <div>
                            <h4 class="mb-1 text-dark fw-bold">
                                <i class="bi bi-clock-history text-danger"></i> Historial de Gastos
                            </h4>
                            <p class="text-muted mb-0 small">Consulta y filtra todos los egresos registrados.</p>
                        </div>
                        <a href="<?= url($usuarioRol . '/registrar-gasto') ?>" class="btn btn-danger btn-sm shadow-sm">
                            <i class="bi bi-plus-circle"></i> Registrar Gasto
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card shadow-sm border-0 mb-4 rounded-3">
                <div class="card-body p-3">
                    <form method="GET" action="<?= url($usuarioRol . '/historial-gastos') ?>" class="row g-2">
                        <!-- Buscar -->
                        <div class="col-md-3">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre o descripción..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
                        </div>

                        <!-- Moneda -->
                        <div class="col-md-2">
                            <select name="moneda" class="form-select">
                                <option value="">Todas las Monedas</option>
                                <option value="USD" <?= ($_GET['moneda'] ?? '') === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                                <option value="Bs" <?= ($_GET['moneda'] ?? '') === 'Bs' ? 'selected' : '' ?>>Bs (Bolívares)</option>
                            </select>
                        </div>

                        <!-- Método de Pago -->
                        <div class="col-md-2">
                            <select name="metodo_pago" class="form-select">
                                <option value="">Todos los Métodos</option>
                                <option value="efectivo" <?= ($_GET['metodo_pago'] ?? '') === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                                <option value="transferencia" <?= ($_GET['metodo_pago'] ?? '') === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                            </select>
                        </div>

                        <!-- Mes/Año -->
                        <div class="col-md-2">
                            <select name="mes" class="form-select">
                                <option value="">Todos los Meses</option>
                                <?php
                                $mesesNombres = [
                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                ];
                                foreach ($mesesNombres as $num => $nombre):
                                ?>
                                    <option value="<?= $num ?>" <?= (isset($_GET['mes']) && $_GET['mes'] !== '' && intval($_GET['mes']) === $num) ? 'selected' : '' ?>><?= $nombre ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-1">
                            <select name="anio" class="form-select">
                                <option value="">Todos los Años</option>
                                <?php
                                $anioActual = intval(date('Y'));
                                for ($a = $anioActual; $a >= 2024; $a--):
                                ?>
                                    <option value="<?= $a ?>" <?= (isset($_GET['anio']) && $_GET['anio'] !== '' && intval($_GET['anio']) === $a) ? 'selected' : '' ?>><?= $a ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Acciones del Filtro -->
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filtrar</button>
                            <a href="<?= url($usuarioRol . '/historial-gastos') ?>" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise"></i> Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table Card -->
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-0">
                    <?php if (empty($gastos)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-cash-stack text-muted" style="font-size: 64px;"></i>
                            <p class="text-muted mt-3 mb-0">No se encontraron gastos con los filtros aplicados.</p>
                            <p class="small text-muted">Intenta cambiar los filtros o registra un gasto nuevo.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Fecha</th>
                                        <th>Nombre del Gasto</th>
                                        <th>Monto</th>
                                        <th>Método</th>
                                        <th>Registrado Por</th>
                                        <th class="text-center">Comprobante</th>
                                        <th class="text-center">Recibo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gastos as $gasto): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold"><?= date('d/m/Y', strtotime($gasto->fecha_gasto)) ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($gasto->nombre) ?></div>
                                                <?php if (!empty($gasto->descripcion)): ?>
                                                    <span class="text-muted small d-block" style="max-width: 350px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($gasto->descripcion) ?>">
                                                        <?= htmlspecialchars($gasto->descripcion) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong class="<?= $gasto->moneda === 'USD' ? 'text-success' : 'text-primary' ?>">
                                                    <?= $gasto->moneda === 'USD' ? formatUSD($gasto->monto) : formatBs($gasto->monto) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?= ucfirst($gasto->metodo_pago) ?></span>
                                            </td>
                                            <td>
                                                <span class="small text-muted"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($gasto->registrado_por_nombre) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($gasto->comprobante_ruta): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-view-image" data-src="<?= url($gasto->comprobante_ruta) ?>" data-title="Comprobante de Pago - <?= htmlspecialchars($gasto->nombre) ?>">
                                                        <i class="bi bi-file-earmark-image"></i> Ver Comprobante
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small">No posee</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($gasto->recibo_ruta): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-view-image" data-src="<?= url($gasto->recibo_ruta) ?>" data-title="Recibo de Pago - <?= htmlspecialchars($gasto->nombre) ?>">
                                                        <i class="bi bi-file-earmark-image"></i> Ver Recibo
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small">N/A</span>
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
</div>

<!-- Modal Visor de Imagen/Documento -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="imageModalLabel"><i class="bi bi-file-earmark-image me-2"></i>Visor de Comprobante</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-light p-3">
                <div id="modalImgWrapper">
                    <img src="" id="modalImg" class="img-fluid rounded shadow-sm" style="max-height: 70vh;">
                </div>
                <div id="modalPdfWrapper" class="d-none" style="height: 70vh;">
                    <iframe id="modalPdf" src="" style="width:100%; height:100%; border:none;" class="rounded shadow-sm"></iframe>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <a href="" id="btnDownloadImage" target="_blank" download class="btn btn-primary btn-sm"><i class="bi bi-download"></i> Abrir / Descargar</a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('imageModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const modalImg = document.getElementById('modalImg');
    const modalPdf = document.getElementById('modalPdf');
    const modalImgWrapper = document.getElementById('modalImgWrapper');
    const modalPdfWrapper = document.getElementById('modalPdfWrapper');
    const modalLabel = document.getElementById('imageModalLabel');
    const downloadBtn = document.getElementById('btnDownloadImage');

    document.querySelectorAll('.btn-view-image').forEach(button => {
        button.addEventListener('click', function() {
            const src = this.getAttribute('data-src');
            const title = this.getAttribute('data-title');
            
            modalLabel.textContent = title;
            downloadBtn.href = src;

            const cleanSrc = src.split('?')[0].toLowerCase();
            const isPDF = cleanSrc.endsWith('.pdf') || src.startsWith('data:application/pdf');

            if (isPDF) {
                modalImgWrapper.classList.add('d-none');
                modalImg.src = '';
                modalPdf.src = src;
                modalPdfWrapper.classList.remove('d-none');
            } else {
                modalPdfWrapper.classList.add('d-none');
                modalPdf.src = '';
                modalImg.src = src;
                modalImgWrapper.classList.remove('d-none');
            }
            
            modal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
