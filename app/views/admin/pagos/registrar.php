<?php
$pageTitle = 'Registrar Pago';
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
                <a href="<?= url('admin/pagos') ?>" class="text-decoration-none text-muted mb-2 d-inline-block">
                    <i class="bi bi-arrow-left"></i> Volver al historial
                </a>
                <h4>Registrar Nuevo Pago</h4>
            </div>

            <div class="row">
                <!-- Columna Izquierda: Búsqueda y Resultados -->
                <div class="col-lg-8">
                    <!-- Buscador de Cliente -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h6 class="card-title mb-3">1. Seleccionar Cliente</h6>
                            <form action="" method="GET" class="d-flex gap-2">
                                <input type="text" name="buscar" class="form-control" placeholder="Cédula, correo o número de apartamento..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>" required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if ($cliente): ?>
                        <!-- Formulario de Pago (Solo si hay cliente) -->
                        <form action="<?= url('admin/pagos/registrar') ?>" method="POST" id="formPago">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="cliente_id" value="<?= $cliente->id ?>">

                            <!-- Selección de Mensualidades -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white py-3">
                                    <h6 class="mb-0">2. Seleccionar Mensualidades</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($mensualidadesPendientes)): ?>
                                        <div class="alert alert-success">
                                            <i class="bi bi-check-circle me-2"></i> El cliente está al día con sus pagos.
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info py-2">
                                            <small><i class="bi bi-info-circle"></i> Seleccione las mensualidades a pagar en orden cronológico.</small>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 40px">
                                                            <input type="checkbox" class="form-check-input" id="checkAll" onchange="toggleAll(this)">
                                                        </th>
                                                        <th>Mes</th>
                                                        <th>Año</th>
                                                        <th>Monto</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($mensualidadesPendientes as $m): ?>
                                                        <tr class="mensualidad-row" data-monto="<?= $tarifaActual->monto_mensual_usd * $cantidadControles ?>">
                                                            <td>
                                                                <input type="checkbox" name="mensualidades[]" value="<?= $m['id'] ?>" class="form-check-input mensualidad-check" onchange="calcularTotal()">
                                                            </td>
                                                            <td><?= getNombreMes($m['mes']) ?></td>
                                                            <td><?= $m['anio'] ?></td>
                                                            <td>$<?= number_format($tarifaActual->monto_mensual_usd * $cantidadControles, 2) ?></td>
                                                            <td>
                                                                <?php if($m['mes'] < DATE('n') && $m['anio'] <= DATE('Y') || $m['anio'] < DATE('Y')): ?>
                                                                    <span class="badge bg-danger">Vencida</span>
                                                                <?php elseif($m['mes'] == DATE('n') && $m['anio'] == DATE('Y')): ?>
                                                                    <span class="badge bg-warning text-dark">Actual</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-primary">Adelantada</span>
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

                            <!-- Detalles del Pago -->
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-3">
                                    <h6 class="mb-0">3. Detalles del Pago</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Moneda de Pago</label>
                                            <select name="moneda" id="moneda" class="form-select" onchange="actualizarMoneda()">
                                                <option value="USD">USD (Dólares)</option>
                                                <option value="Bs">Bs (Bolívares)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Método</label>
                                            <select name="metodo_pago" id="metodo_pago" class="form-select">
                                                <option value="efectivo">Efectivo</option>
                                                <option value="transferencia">Transferencia</option>
                                                <option value="pago_movil">Pago Móvil</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Monto Recibido</label>
                                            <div class="input-group">
                                                <span class="input-group-text" id="simboloMoneda">$</span>
                                                <input type="number" step="0.01" name="monto" id="montoInput" class="form-control" required>
                                            </div>
                                            <div class="form-text text-end" id="conversionText"></div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Referencia / Notas</label>
                                            <textarea name="referencia" class="form-control" rows="2" placeholder="Opcional: Número de referencia o nota..."></textarea>
                                        </div>
                                    </div>

                                    <div class="mt-4 d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit" disabled>
                                            <i class="bi bi-save me-2"></i> Registrar Pago
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Columna Derecha: Información del Cliente -->
                <div class="col-lg-4">
                    <?php if (isset($cliente) && $cliente): ?>
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body text-center py-4">
                                <div class="mb-3">
                                    <div class="avatar-circle bg-primary text-white mx-auto d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; font-size: 24px; border-radius: 50%;">
                                        <?= strtoupper(substr($cliente->nombre_completo, 0, 1)) ?>
                                    </div>
                                </div>
                                <h5 class="mb-1"><?= htmlspecialchars($cliente->nombre_completo) ?></h5>
                                <p class="text-muted mb-3"><?= htmlspecialchars($cliente->email) ?></p>
                                <span class="badge bg-secondary mb-3"><?= ucfirst($cliente->rol) ?></span>
                                
                                <div class="text-start border-top pt-3 mt-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Cédula:</span>
                                        <span class="fw-medium"><?= htmlspecialchars($cliente->cedula) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Teléfono:</span>
                                        <span class="fw-medium"><?= htmlspecialchars($cliente->telefono ?? 'N/A') ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Controles:</span>
                                        <span class="fw-medium"><?= $cantidadControles ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen de Pago -->
                        <div class="card border-0 shadow-sm bg-primary text-white">
                            <div class="card-body">
                                <h6 class="card-title text-white-50">Resumen a Pagar</h6>
                                <div class="d-flex justify-content-between align-items-end mb-2">
                                    <span class="text-white-50">Total USD:</span>
                                    <span class="h4 mb-0" id="resumenTotalUSD">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-end">
                                    <span class="text-white-50">Total Bs:</span>
                                    <span class="h5 mb-0" id="resumenTotalBs">0.00 Bs</span>
                                </div>
                                <div class="mt-3 small text-white-50 border-top border-white-50 pt-2">
                                    Tasa BCV: <?= number_format($tasaBCV, 2) ?> Bs/$
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light text-center">
                            <i class="bi bi-search fs-1 text-muted d-block mb-3"></i>
                            <p class="mb-0 text-muted">Busque un cliente para ver su información y registrar pagos.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($cliente)): ?>
<script>
const tasaBCV = <?= $tasaBCV ?>;
let totalUSD = 0;

function toggleAll(source) {
    document.querySelectorAll('.mensualidad-check').forEach(c => {
        c.checked = source.checked;
    });
    calcularTotal();
}

function calcularTotal() {
    totalUSD = 0;
    const checks = document.querySelectorAll('.mensualidad-check:checked');
    
    // Obtener el monto de la fila (data-monto)
    checks.forEach(c => {
        const row = c.closest('tr');
        totalUSD += parseFloat(row.dataset.monto);
    });

    // Actualizar resumen
    document.getElementById('resumenTotalUSD').textContent = '$' + totalUSD.toFixed(2);
    document.getElementById('resumenTotalBs').textContent = (totalUSD * tasaBCV).toFixed(2) + ' Bs';

    // Auto-llenar el input de monto según la moneda seleccionada
    actualizarInputMonto();

    // Habilitar/Deshabilitar botón submit
    document.getElementById('btnSubmit').disabled = totalUSD === 0;
}

function actualizarMoneda() {
    const moneda = document.getElementById('moneda').value;
    const simbolo = moneda === 'USD' ? '$' : 'Bs';
    document.getElementById('simboloMoneda').textContent = simbolo;
    actualizarInputMonto();
}

function actualizarInputMonto() {
    const moneda = document.getElementById('moneda').value;
    const input = document.getElementById('montoInput');
    
    if (moneda === 'USD') {
        input.value = totalUSD.toFixed(2);
    } else {
        input.value = (totalUSD * tasaBCV).toFixed(2);
    }
}

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    calcularTotal();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

