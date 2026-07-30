<?php
$pageTitle = 'Registrar Gasto';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url($usuarioRol . '/dashboard')],
    ['label' => 'Registrar Gasto', 'url' => '#']
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
                                <i class="bi bi-cash-stack text-danger"></i> Registrar Gasto Operativo
                            </h4>
                            <p class="text-muted mb-0 small">Registra egresos de la junta de administración del estacionamiento.</p>
                        </div>
                        <a href="<?= url($usuarioRol . '/historial-gastos') ?>" class="btn btn-outline-secondary btn-sm shadow-sm">
                            <i class="bi bi-clock-history"></i> Ver Historial
                        </a>
                    </div>
                </div>
            </div>

            <!-- Form Section -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body p-4">
                            <form action="<?= $postUrl ?>" method="POST" enctype="multipart/form-data" id="formRegistrarGasto" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                                <div class="row g-3">
                                    <!-- Nombre del Gasto -->
                                    <div class="col-md-12">
                                        <label for="nombre" class="form-label fw-bold">Nombre del Gasto <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-tag-fill text-muted"></i></span>
                                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej: Mantenimiento de motor de portón eléctrico" required max="255">
                                        </div>
                                        <div class="invalid-feedback">Por favor, introduce el nombre del gasto.</div>
                                    </div>

                                    <!-- Descripción -->
                                    <div class="col-md-12">
                                        <label for="descripcion" class="form-label fw-bold">Descripción Corta</label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Detalles del gasto (repuestos comprados, servicio realizado, etc.)" max="1000"></textarea>
                                    </div>

                                    <!-- Monto -->
                                    <div class="col-md-4">
                                        <label for="monto" class="form-label fw-bold">Monto del Pago <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text" id="monto-simbolo">$</span>
                                            <input type="number" step="0.01" class="form-control" id="monto" name="monto" placeholder="0.00" required min="0.01">
                                        </div>
                                        <div class="invalid-feedback">Ingresa un monto válido mayor a 0.</div>
                                    </div>

                                    <!-- Moneda de Pago -->
                                    <div class="col-md-4">
                                        <label for="moneda" class="form-label fw-bold">Moneda <span class="text-danger">*</span></label>
                                        <select class="form-select" id="moneda" name="moneda" required>
                                            <option value="USD">USD ($) - Dólares</option>
                                            <option value="Bs">Bs - Bolívares</option>
                                        </select>
                                    </div>

                                    <!-- Método de Pago -->
                                    <div class="col-md-4">
                                        <label for="metodo_pago" class="form-label fw-bold">Método de Pago <span class="text-danger">*</span></label>
                                        <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                                            <option value="efectivo">Efectivo</option>
                                            <option value="transferencia">Transferencia / Digital</option>
                                        </select>
                                    </div>

                                    <!-- Fecha del Gasto -->
                                    <div class="col-md-6">
                                        <label for="fecha_gasto" class="form-label fw-bold">Fecha del Gasto <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-calendar-event text-muted"></i></span>
                                            <input type="date" class="form-control" id="fecha_gasto" name="fecha_gasto" value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6"></div>

                                    <!-- Foto del Comprobante -->
                                    <div class="col-md-6">
                                        <label for="comprobante" class="form-label fw-bold">Foto o Comprobante <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="comprobante" name="comprobante" accept="image/*,application/pdf,.heic,.heif,.webp" required>
                                            <label class="btn btn-outline-secondary" for="comprobante" title="Seleccionar de galería / Tomar foto">
                                                <i class="bi bi-image"></i>
                                            </label>
                                        </div>
                                        <div class="form-text small text-muted">Selecciona una foto de tu galería, archivo o toma una directamente.</div>
                                        <div class="invalid-feedback">Sube o toma una foto del comprobante.</div>
                                        <div id="preview-comprobante" class="mt-2 text-center d-none">
                                            <img src="" class="img-thumbnail img-fluid d-none" style="max-height: 200px;">
                                            <div class="pdf-preview alert alert-info py-2 d-none mb-0"><i class="bi bi-file-pdf fs-4 me-2"></i> <span class="file-name"></span></div>
                                        </div>
                                    </div>

                                    <!-- Foto del Recibo (Opcional) -->
                                    <div class="col-md-6">
                                        <label for="recibo" class="form-label fw-bold">Foto del Recibo (Opcional)</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="recibo" name="recibo" accept="image/*,application/pdf,.heic,.heif,.webp">
                                            <label class="btn btn-outline-secondary" for="recibo" title="Seleccionar de galería / Tomar foto">
                                                <i class="bi bi-image"></i>
                                            </label>
                                        </div>
                                        <div class="form-text small text-muted">Selecciona un archivo de tu galería o foto del recibo firmado/sellado.</div>
                                        <div id="preview-recibo" class="mt-2 text-center d-none">
                                            <img src="" class="img-thumbnail img-fluid d-none" style="max-height: 200px;">
                                            <div class="pdf-preview alert alert-info py-2 d-none mb-0"><i class="bi bi-file-pdf fs-4 me-2"></i> <span class="file-name"></span></div>
                                        </div>
                                    </div>

                                    <!-- Botones -->
                                    <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                                        <a href="<?= url($usuarioRol . '/dashboard') ?>" class="btn btn-outline-secondary">Cancelar</a>
                                        <button type="submit" class="btn btn-danger" id="btnSubmitGasto">
                                            <span class="spinner-border spinner-border-sm d-none" id="spinnerSubmitGasto" role="status" aria-hidden="true"></span>
                                            <span id="textSubmitGasto"><i class="bi bi-check-circle"></i> Registrar Gasto</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validaciones de bootstrap
    const form = document.getElementById('formRegistrarGasto');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
        } else {
            // Mostrar spinner y deshabilitar botón para evitar envíos dobles
            const button = document.getElementById('btnSubmitGasto');
            const spinner = document.getElementById('spinnerSubmitGasto');
            const text = document.getElementById('textSubmitGasto');
            
            button.setAttribute('disabled', 'true');
            spinner.classList.remove('d-none');
            text.innerHTML = " Guardando Gasto...";
        }
    }, false);

    // Previsualizar comprobante
    const inputComprobante = document.getElementById('comprobante');
    const previewCompContainer = document.getElementById('preview-comprobante');
    inputComprobante.addEventListener('change', function() {
        mostrarPrevisualizacion(this, previewCompContainer);
    });

    // Previsualizar recibo
    const inputRecibo = document.getElementById('recibo');
    const previewRecContainer = document.getElementById('preview-recibo');
    inputRecibo.addEventListener('change', function() {
        mostrarPrevisualizacion(this, previewRecContainer);
    });

    function mostrarPrevisualizacion(input, container) {
        const file = input.files[0];
        const imgEl = container.querySelector('img');
        const pdfEl = container.querySelector('.pdf-preview');
        const pdfName = container.querySelector('.file-name');

        if (file) {
            container.classList.remove('d-none');
            const ext = file.name.split('.').pop().toLowerCase();

            if (ext === 'pdf') {
                if (imgEl) imgEl.classList.add('d-none');
                if (pdfEl) {
                    pdfEl.classList.remove('d-none');
                    if (pdfName) pdfName.textContent = file.name;
                }
            } else {
                if (pdfEl) pdfEl.classList.add('d-none');
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (imgEl) {
                        imgEl.src = e.target.result;
                        imgEl.classList.remove('d-none');
                    }
                }
                reader.readAsDataURL(file);
            }
        } else {
            container.classList.add('d-none');
            if (imgEl) imgEl.classList.add('d-none');
            if (pdfEl) pdfEl.classList.add('d-none');
        }
    }
    // Cambiar símbolo de moneda dinámicamente
    const selectMoneda = document.getElementById('moneda');
    const montoSimbolo = document.getElementById('monto-simbolo');
    
    selectMoneda.addEventListener('change', function() {
        if (this.value === 'USD') {
            montoSimbolo.textContent = '$';
        } else {
            montoSimbolo.textContent = 'Bs';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
