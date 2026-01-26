<!-- Payment Sidebar Component -->

<!-- Exchange Rate Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="bi bi-currency-exchange me-2"></i>
            Tasa de Cambio BCV
        </h6>
        <button type="button" class="btn btn-light btn-sm" id="btnActualizarTasa" title="Actualizar tasa BCV" onclick="actualizarTasaBCV()">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>
    <div class="card-body text-center">
        <div class="mb-2">
            <small class="text-muted">1 USD =</small>
        </div>
        <h2 class="mb-0 text-primary fw-bold" id="tasaBCV">
            <?= number_format($tasaBCV, 2) ?> Bs
        </h2>
        <small class="text-muted" id="tasaFecha">Actualizado hoy</small>
        <div id="actualizandoTasa" class="mt-2" style="display: none;">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Actualizando...</span>
            </div>
            <small class="text-muted ms-1">Actualizando tasa...</small>
        </div>
    </div>
</div>

<!-- Instructions Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-light">
        <h6 class="mb-0">
            <i class="bi bi-info-circle text-primary me-2"></i>
            Instrucciones
        </h6>
    </div>
    <div class="card-body">
        <div class="step-indicator">
            <div class="step mb-3">
                <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 12px;">
                    1
                </div>
                <small>Busca al cliente por nombre, email o apartamento</small>
            </div>

            <div class="step mb-3">
                <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 12px;">
                    2
                </div>
                <small>Selecciona las mensualidades a pagar</small>
            </div>

            <div class="step mb-3">
                <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 12px;">
                    3
                </div>
                <small>Ingresa el monto exacto recibido</small>
            </div>

            <div class="step mb-3">
                <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 12px;">
                    4
                </div>
                <small>Selecciona método de pago y sube comprobante</small>
            </div>

            <div class="step">
                <div class="step-number bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 12px;">
                    ✓
                </div>
                <small class="fw-bold text-success">El pago se aprueba automáticamente</small>
            </div>
        </div>

        <hr class="my-3">

        <div class="alert alert-light border-0 p-3">
            <h6 class="text-muted mb-2">
                <i class="bi bi-lightbulb text-warning me-1"></i>
                Consejos
            </h6>
            <ul class="list-unstyled mb-0 small">
                <li class="mb-1">• Usa la búsqueda para encontrar clientes rápidamente</li>
                <li class="mb-1">• Los botones de selección rápida facilitan el trabajo</li>
                <li class="mb-1">• Verifica el monto antes de procesar</li>
                <li>• Imprime el recibo para el cliente</li>
            </ul>
        </div>
    </div>
</div>

<!-- Quick Stats (if client is selected) -->
<?php if (isset($cliente)): ?>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-info text-white">
        <h6 class="mb-0">
            <i class="bi bi-graph-up me-2"></i>
            Resumen del Cliente
        </h6>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-6">
                <div class="border-end">
                    <div class="h4 mb-0 text-info" id="sidebar-mensualidades">0</div>
                    <small class="text-muted">Seleccionadas</small>
                </div>
            </div>
            <div class="col-6">
                <div class="h4 mb-0 text-success" id="sidebar-total">$0.00</div>
                <small class="text-muted">Total</small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.step-indicator .step {
    display: flex;
    align-items: flex-start;
}

.step-indicator .step-number {
    flex-shrink: 0;
    margin-top: 2px;
}
</style>