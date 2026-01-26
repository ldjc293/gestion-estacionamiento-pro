<?php
$pageTitle = 'Registrar Pago';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('cliente/dashboard')],
    ['label' => 'Registrar Pago', 'url' => '#']
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">
                                <i class="bi bi-cash-coin text-primary"></i>
                                Registrar Nuevo Pago
                            </h4>
                            <p class="text-muted mb-0">Registra tu pago para las mensualidades del estacionamiento</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <form action="<?= url('cliente/process-registrar-pago') ?>" method="POST" enctype="multipart/form-data" id="formPago">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                                <!-- Monthly Payments Selection -->
                                <div class="mb-4">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
                                        <h5 class="mb-0 mb-md-0">
                                            <i class="bi bi-list-check text-primary me-2"></i>
                                            Seleccionar Mensualidades
                                        </h5>
                                        <div class="btn-group btn-group-sm w-100 w-md-auto" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="seleccionar-todos" onclick="seleccionarTodos()">
                                                <i class="bi bi-check-all me-1 d-none d-sm-inline"></i>
                                                <span class="d-inline d-sm-none">Todos</span>
                                                <span class="d-none d-sm-inline">Seleccionar Todos</span>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="deseleccionar-todos" onclick="deseleccionarTodos()">
                                                <i class="bi bi-x-square me-1 d-none d-sm-inline"></i>
                                                <span class="d-inline d-sm-none">Ninguno</span>
                                                <span class="d-none d-sm-inline">Deseleccionar</span>
                                            </button>
                                        </div>
                                    </div>

                                    <?php if (empty($mensualidadesPendientes)): ?>
                                        <!-- No pending payments -->
                                        <div class="text-center py-5">
                                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                            <h6 class="text-muted mt-3">No hay mensualidades pendientes</h6>
                                            <p class="text-muted small">¡Excelente! Todas tus mensualidades están al día.</p>

                                            <!-- Generate future payments option -->
                                            <div class="card border-info mt-4">
                                                <div class="card-body">
                                                    <h6 class="text-info mb-3">
                                                        <i class="bi bi-calendar-plus me-2"></i>
                                                        Pagar Mensualidades por Adelantado
                                                    </h6>
                                                    <p class="mb-3 small text-muted">Puedes registrar pagos de meses futuros para mantener tu cuenta al día:</p>
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <button type="button" class="btn btn-info w-100" onclick="generarMensualidadesFuturas(3)">
                                                                <i class="bi bi-calendar3 me-1"></i>3 Meses
                                                            </button>
                                                        </div>
                                                        <div class="col-6">
                                                            <button type="button" class="btn btn-info w-100" onclick="generarMensualidadesFuturas(6)">
                                                                <i class="bi bi-calendar3 me-1"></i>6 Meses
                                                            </button>
                                                        </div>
                                                        <div class="col-6">
                                                            <button type="button" class="btn btn-outline-info w-100" onclick="generarMensualidadesFuturas(12)">
                                                                <i class="bi bi-calendar3 me-1"></i>12 Meses
                                                            </button>
                                                        </div>
                                                        <div class="col-6">
                                                            <button type="button" class="btn btn-outline-info w-100" onclick="generarMensualidadesFuturas(24)">
                                                                <i class="bi bi-calendar3 me-1"></i>24 Meses
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div id="generando-mensuales" class="mt-3 text-center" style="display: none;">
                                                        <div class="spinner-border spinner-border-sm text-info" role="status">
                                                            <span class="visually-hidden">Generando...</span>
                                                        </div>
                                                        <small class="text-muted ms-2">Generando mensualidades futuras...</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Quick Selection -->
                                        <div class="card bg-light border-0 mb-3">
                                            <div class="card-body py-3">
                                                <small class="text-muted mb-2 d-block fw-bold">Selección rápida:</small>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php
                                                    $contadores = ['pendientes' => 0, 'futuras' => 0];
                                                    $hoy = new DateTime();
                                                    $mesActual = (int)$hoy->format('n');
                                                    $anioActual = (int)$hoy->format('Y');

                                                    foreach ($mensualidadesPendientes as $m) {
                                                        $fechaVencimiento = new DateTime($m->fecha_vencimiento);
                                                        $mesVencimiento = (int)$fechaVencimiento->format('n');
                                                        $anioVencimiento = (int)$fechaVencimiento->format('Y');

                                                        if ($anioVencimiento > $anioActual || ($anioVencimiento === $anioActual && $mesVencimiento > $mesActual)) {
                                                            // Mes futuro
                                                            $contadores['futuras']++;
                                                        } else {
                                                            // Mes actual o anterior (pendiente/vencido)
                                                            $contadores['pendientes']++;
                                                        }
                                                    }
                                                    ?>

                                                    <?php if ($contadores['pendientes'] > 0): ?>
                                                    <button type="button" class="btn btn-outline-warning btn-sm flex-fill flex-md-auto" id="seleccionar-pendientes" onclick="seleccionarPorTipo('pendiente')">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                                        <span class="d-inline d-md-none">Vencidas</span>
                                                        <span class="d-none d-md-inline">Pendientes (<?= $contadores['pendientes'] ?>)</span>
                                                    </button>
                                                    <?php endif; ?>

                                                    <?php if ($contadores['futuras'] > 0): ?>
                                                    <button type="button" class="btn btn-info btn-sm flex-fill flex-md-auto" id="seleccionar-futuros" onclick="seleccionarPorTipo('futuro')">
                                                        <i class="bi bi-calendar-plus me-1"></i>
                                                        <span class="d-inline d-md-none">Futuras</span>
                                                        <span class="d-none d-md-inline">Futuras (<?= $contadores['futuras'] ?>)</span>
                                                    </button>
                                                    <?php endif; ?>

                                                    <button type="button" class="btn btn-success btn-sm flex-fill flex-md-auto" id="seleccionar-3-meses" onclick="seleccionarSiguientesMeses(3)">
                                                        <i class="bi bi-calendar-check me-1"></i>
                                                        <span class="d-inline d-md-none">3 Meses</span>
                                                        <span class="d-none d-md-inline">Próximos 3</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Monthly Payments List -->
                                        <div class="border rounded p-3 bg-white" style="max-height: 400px; overflow-y: auto;">
                                            <div class="text-muted small mb-3">
                                                <i class="bi bi-info-circle me-1"></i>
                                                <strong>Selección Manual:</strong> Haz clic en los meses que deseas pagar
                                                <div class="mt-1">
                                                    <small class="text-info">
                                                        <i class="bi bi-lightning me-1"></i>
                                                        Los montos se calculan automáticamente según la tarifa actual
                                                    </small>
                                                </div>
                                            </div>

                                            <div class="row g-2">
                                                <?php foreach ($mensualidadesPendientes as $index => $mensualidad):
                                                    $fechaVencimiento = new DateTime($mensualidad->fecha_vencimiento);
                                                    $hoy = new DateTime();

                                                    // Determinar el tipo basado en el mes, no solo la fecha
                                                    $mesVencimiento = (int)$fechaVencimiento->format('n');
                                                    $anioVencimiento = (int)$fechaVencimiento->format('Y');
                                                    $mesActual = (int)$hoy->format('n');
                                                    $anioActual = (int)$hoy->format('Y');

                                                    if ($anioVencimiento < $anioActual || ($anioVencimiento === $anioActual && $mesVencimiento < $mesActual)) {
                                                        // Mes anterior al actual
                                                        $esFutura = false;
                                                        $esVencido = $fechaVencimiento < $hoy; // Solo vencido si fecha específica ya pasó
                                                    } elseif ($anioVencimiento === $anioActual && $mesVencimiento === $mesActual) {
                                                        // Mes actual - siempre pendiente
                                                        $esFutura = false;
                                                        $esVencido = false;
                                                    } else {
                                                        // Mes futuro
                                                        $esFutura = true;
                                                        $esVencido = false;
                                                    }
                                                ?>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="form-check p-3 border rounded position-relative
                                                             <?= $esFutura ? 'border-info bg-light' : '' ?>
                                                             <?= $esVencido ? 'border-danger bg-light' : '' ?>
                                                             <?= !$esFutura && !$esVencido ? 'border-success' : '' ?>
                                                             mensualidad-item"
                                                             data-monto="<?= $mensualidad->monto_usd ?>"
                                                             data-tipo="<?= $esFutura ? 'futuro' : ($esVencido ? 'vencido' : 'pendiente') ?>"
                                                             data-mes="<?= date('Y-m', strtotime($mensualidad->mes_correspondiente)) ?>">

                                                            <!-- Status Badge -->
                                                            <div class="position-absolute top-0 end-0 p-1">
                                                                <?php if ($esFutura): ?>
                                                                    <span class="badge bg-info small">
                                                                        <i class="bi bi-calendar-plus"></i> Futuro
                                                                    </span>
                                                                <?php elseif ($esVencido): ?>
                                                                    <span class="badge bg-danger small">
                                                                        <i class="bi bi-exclamation-triangle"></i> Vencido
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-success small">
                                                                        <i class="bi bi-clock"></i> Pendiente
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>

                                                            <!-- Checkbox -->
                                                            <div class="form-check">
                                                                <input class="form-check-input mensualidad-checkbox"
                                                                       type="checkbox"
                                                                       name="mensualidades[]"
                                                                       value="<?= $mensualidad->id ?>"
                                                                       id="mens_<?= $mensualidad->id ?>"
                                                                       data-monto="<?= $mensualidad->monto_usd ?>">
                                                                <label class="form-check-label w-100" for="mens_<?= $mensualidad->id ?>">
                                                                    <div class="ms-2">
                                                                        <div class="fw-bold">
                                                                            <?= formatearMesAnio($mensualidad->mes_correspondiente) ?>
                                                                        </div>
                                                                        <div class="text-primary small">
                                                                            $<?= number_format($mensualidad->monto_usd, 2) ?>
                                                                        </div>
                                                                        <div class="text-muted small">
                                                                            Vence: <?= date('d/m/Y', strtotime($mensualidad->fecha_vencimiento)) ?>
                                                                        </div>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <!-- Summary -->
                                        <div class="mt-3 p-3 bg-light rounded">
                                            <div class="row text-center g-2">
                                                <div class="col-6 col-md-4">
                                                    <div class="fw-bold text-primary small d-md-none">Seleccionadas</div>
                                                    <div class="fw-bold text-primary d-none d-md-block">Mensualidades seleccionadas</div>
                                                    <div class="h4 mb-0" id="mensualidadesCount">0</div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="fw-bold text-success small d-md-none">Total USD</div>
                                                    <div class="fw-bold text-success d-none d-md-block">Total USD</div>
                                                    <div class="h5 mb-0 text-success" id="totalUSD">$0.00</div>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <div class="fw-bold text-info small d-md-none">Total Bs</div>
                                                    <div class="fw-bold text-info d-none d-md-block">Total Bs</div>
                                                    <div class="h5 mb-0 text-info" id="totalBS">0.00 Bs</div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Payment Details -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Moneda de Pago *</label>
                                        <select class="form-select" name="moneda" id="moneda" required>
                                            <option value="USD">Dólares (USD)</option>
                                            <option value="Bs">Bolívares (Bs)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Monto Pagado *</label>
                                        <div class="input-group">
                                            <span class="input-group-text" id="moneda-symbol">USD</span>
                                            <input type="number"
                                                   class="form-control"
                                                   name="monto"
                                                   id="monto"
                                                   step="0.01"
                                                   min="0.01"
                                                   required
                                                   placeholder="0.00">
                                        </div>
                                        <small class="text-muted" id="montoConversion"></small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Método de Pago *</label>
                                        <select class="form-select" name="metodo_pago" required>
                                            <option value="">Seleccione...</option>
                                            <option value="transferencia">Transferencia Bancaria</option>
                                            <option value="pago_movil">Pago Móvil</option>
                                            <option value="efectivo">Efectivo</option>
                                            <option value="otro">Otro</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Referencia</label>
                                        <input type="text"
                                               class="form-control"
                                               name="referencia"
                                               placeholder="Ej: 123456789">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Fecha del Pago *</label>
                                    <input type="date"
                                           class="form-control"
                                           name="fecha_pago"
                                           value="<?= date('Y-m-d') ?>"
                                           max="<?= date('Y-m-d') ?>"
                                           required>
                                </div>

                                <div class="alert alert-info border-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-info-circle text-info me-2"></i>
                                        <small class="mb-0">Tu pago será revisado por un operador. Recibirás una notificación cuando sea aprobado.</small>
                                    </div>
                                </div>

                                <!-- File Upload -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold" for="comprobante">
                                        Comprobante de Pago *
                                    </label>
                                    <input type="file" class="form-control" name="comprobante" id="comprobante" accept=".pdf,.jpg,.png" required>
                                    <small class="text-muted">Formatos permitidos: PDF, JPG, PNG. Tamaño máximo: 5MB</small>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex flex-column flex-sm-row gap-2 gap-sm-3 justify-content-end">
                                    <a href="<?= url('cliente/dashboard') ?>" class="btn btn-outline-secondary order-2 order-sm-1">
                                        <i class="bi bi-x-circle me-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary order-1 order-sm-2" id="btnSubmit">
                                        <i class="bi bi-upload me-2"></i>
                                        <span class="d-inline d-sm-none">Registrar</span>
                                        <span class="d-none d-sm-inline">Registrar Pago</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Exchange Rate Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="bi bi-currency-exchange me-2"></i>
                                Tasa de Cambio BCV
                            </h6>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <small class="text-muted">1 USD =</small>
                            </div>
                            <h2 class="mb-0 text-primary fw-bold" id="tasaBCV">
                                <?= number_format($tasaBCV, 2) ?> Bs
                            </h2>
                            <small class="text-muted">Actualizado hoy</small>
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
                                    <small>Selecciona las mensualidades que deseas pagar</small>
                                </div>

                                <div class="step mb-3">
                                    <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 12px;">
                                        2
                                    </div>
                                    <small>Ingresa el monto exacto que pagaste</small>
                                </div>

                                <div class="step mb-3">
                                    <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 12px;">
                                        3
                                    </div>
                                    <small>Selecciona método de pago y sube comprobante</small>
                                </div>

                                <div class="step">
                                    <div class="step-number bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 12px;">
                                        ⏳
                                    </div>
                                    <small class="fw-bold text-warning">Espera aprobación del operador</small>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="alert alert-light border-0 p-3">
                                <h6 class="text-muted mb-2">
                                    <i class="bi bi-lightbulb text-warning me-1"></i>
                                    Consejos
                                </h6>
                                <ul class="list-unstyled mb-0 small">
                                    <li class="mb-1">• Usa los botones de selección rápida</li>
                                    <li class="mb-1">• Verifica el monto antes de enviar</li>
                                    <li class="mb-1">• Sube una foto clara del comprobante</li>
                                    <li>• Recibirás notificación cuando sea aprobado</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="bi bi-graph-up me-2"></i>
                                Resumen
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

                    <!-- Banking Info -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-bank me-2"></i> Datos Bancarios
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Transferencia Bancaria -->
                            <div class="mb-4">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-arrow-left-right me-2"></i>Transferencia Bancaria
                                </h6>
                                <div class="border rounded p-3 bg-light">
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <small class="text-muted d-block">Banco</small>
                                            <div class="fw-bold small">Banco Venezolano de Crédito</div>
                                        </div>
                                        <div class="col-sm-6">
                                            <small class="text-muted d-block">Titular</small>
                                            <div class="fw-bold small">CI: 14020305</div>
                                        </div>
                                        <div class="col-sm-6">
                                            <small class="text-muted d-block">Tipo de Cuenta</small>
                                            <div class="fw-bold small">Cuenta Corriente</div>
                                        </div>
                                        <div class="col-sm-6">
                                            <small class="text-muted d-block">Número de Cuenta</small>
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <code class="text-break small flex-grow-1">01040108990108045159</code>
                                                <button class="btn btn-sm btn-link p-0 flex-shrink-0" onclick="copyToClipboard('01040108990108045159')" title="Copiar">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pago Móvil -->
                            <div class="mb-3">
                                <h6 class="text-success mb-3">
                                    <i class="bi bi-phone me-2"></i>Pago Móvil (Bs)
                                </h6>
                                <div class="border rounded p-3 bg-light">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <small class="text-muted d-block">Banco</small>
                                            <div class="fw-bold small">Banco Venezolano de Crédito (0104)</div>
                                        </div>
                                        <div class="col-sm-6">
                                            <small class="text-muted d-block">C.I.</small>
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <code class="text-break small flex-grow-1">14020305</code>
                                                <button class="btn btn-sm btn-link p-0 flex-shrink-0" onclick="copyToClipboard('14020305')" title="Copiar">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <small class="text-muted d-block">Teléfono</small>
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <code class="text-break small flex-grow-1">04242543661</code>
                                                <button class="btn btn-sm btn-link p-0 flex-shrink-0" onclick="copyToClipboard('04242543661')" title="Copiar">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const baseUrl = URL_BASE;
const tasaBCV = <?= $tasaBCV ?>;

// Clase simplificada para inicialización inmediata
class RegistrarPagoCliente {
    constructor() {
        this.tasaBCV = tasaBCV;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.updateContador();
        // Validar selección secuencial al cargar la página
        setTimeout(() => this.validarSeleccionSecuencial(), 100);
    }

    setupEventListeners() {
        // Configurar event listeners básicos
        this.setupTotalCalculation();
        this.setupCurrencyConversion();
        this.setupFormValidation();
        this.setupSelectionButtons();
    }

    setupTotalCalculation() {
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('mensualidad-checkbox')) {
                this.validarSeleccionSecuencial();
                this.updateContador();
                this.calcularTotal();
            }
        });
    }

    validarSeleccionSecuencial() {
        const items = Array.from(document.querySelectorAll('.mensualidad-item'));

        if (items.length === 0) return;

        // Ordenar por fecha (usando el atributo data-mes en formato YYYY-MM)
        items.sort((a, b) => {
            const fechaA = new Date(a.dataset.mes + '-01');
            const fechaB = new Date(b.dataset.mes + '-01');
            return fechaA - fechaB;
        });

        // Encontrar el primer mes no seleccionado
        let primerMesNoSeleccionado = -1;

        items.forEach((item, index) => {
            const checkbox = item.querySelector('.mensualidad-checkbox');

            if (!checkbox.checked && primerMesNoSeleccionado === -1) {
                primerMesNoSeleccionado = index;
            }
        });

        // Deshabilitar todos los meses después del primer mes no seleccionado
        items.forEach((item, index) => {
            const checkbox = item.querySelector('.mensualidad-checkbox');

            // Si hay un mes no seleccionado y este mes está después de él
            if (primerMesNoSeleccionado !== -1 && index > primerMesNoSeleccionado && !checkbox.checked) {
                checkbox.disabled = true;
                item.classList.add('disabled-month');
            } else {
                // Solo habilitar si no está ya seleccionado
                if (!checkbox.checked) {
                    checkbox.disabled = false;
                }
                item.classList.remove('disabled-month');
            }
        });
    }

    setupCurrencyConversion() {
        const monedaSelect = document.getElementById('moneda');
        const montoInput = document.getElementById('monto');

        if (monedaSelect && montoInput) {
            // Función para actualizar el símbolo de moneda
            const actualizarSimboloMoneda = () => {
                const symbol = document.getElementById('moneda-symbol');
                if (symbol) {
                    symbol.textContent = monedaSelect.value === 'USD' ? '$' : 'Bs';
                }
            };

            // Actualizar símbolo inmediatamente
            actualizarSimboloMoneda();

            // Actualizar símbolo cuando cambie la moneda
            monedaSelect.addEventListener('change', actualizarSimboloMoneda);

            // Actualizar conversión cuando cambien los valores
            [monedaSelect, montoInput].forEach(element => {
                element.addEventListener('change', () => this.actualizarConversion());
                element.addEventListener('input', () => this.actualizarConversion());
            });
        }
    }

    setupFormValidation() {
        const form = document.getElementById('formPago');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            const checkboxes = document.querySelectorAll('.mensualidad-checkbox:checked');

            if (checkboxes.length === 0) {
                e.preventDefault();
                this.showAlert('Debes seleccionar al menos una mensualidad', 'danger');
                return false;
            }

            const btn = document.getElementById('btnSubmit');
            if (btn) {
                this.setButtonLoading(btn, true);
            }
        });
    }

    setupSelectionButtons() {
        // Botones de selección rápida
        const buttons = {
            'seleccionar-todos': () => this.seleccionarTodos(),
            'deseleccionar-todos': () => this.deseleccionarTodos(),
            'seleccionar-pendientes': () => this.seleccionarPorTipo('pendiente'),
            'seleccionar-futuros': () => this.seleccionarPorTipo('futuro'),
            'seleccionar-3-meses': () => this.seleccionarSiguientesMeses(3)
        };

        Object.keys(buttons).forEach(id => {
            const button = document.getElementById(id);
            if (button) {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    buttons[id]();
                });
            }
        });
    }

    seleccionarTodos() {
        const checkboxes = document.querySelectorAll('.mensualidad-checkbox');
        checkboxes.forEach(checkbox => {
            if (!checkbox.disabled) {
                checkbox.checked = true;
            }
        });
        this.validarSeleccionSecuencial();
        this.updateContador();
    }

    deseleccionarTodos() {
        const checkboxes = document.querySelectorAll('.mensualidad-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        this.validarSeleccionSecuencial();
        this.updateContador();
    }

    seleccionarPorTipo(tipo) {
        document.querySelectorAll('.mensualidad-checkbox').forEach(checkbox => {
            const item = checkbox.closest('.mensualidad-item');
            if (item && item.dataset.tipo === tipo && !checkbox.disabled) {
                checkbox.checked = true;
            } else {
                checkbox.checked = false;
            }
        });
        this.validarSeleccionSecuencial();
        this.updateContador();
    }

    seleccionarSiguientesMeses(cantidad) {
        const items = Array.from(document.querySelectorAll('.mensualidad-item'));
        this.deseleccionarTodos();

        items.slice(0, cantidad).forEach(item => {
            const checkbox = item.querySelector('.mensualidad-checkbox');
            if (checkbox && !checkbox.disabled) {
                checkbox.checked = true;
            }
        });
        this.validarSeleccionSecuencial();
        this.updateContador();
    }

    updateContador() {
        const checkboxes = document.querySelectorAll('.mensualidad-checkbox:checked');
        const count = checkboxes.length;

        const countElement = document.getElementById('mensualidadesCount');
        if (countElement) {
            countElement.textContent = count;
        }

        const sidebarCount = document.getElementById('sidebar-mensualidades');
        if (sidebarCount) {
            sidebarCount.textContent = count;
        }

        this.calcularTotal();
    }

    calcularTotal() {
        const checkboxes = document.querySelectorAll('.mensualidad-checkbox:checked');
        let totalUSD = 0;

        checkboxes.forEach(checkbox => {
            const item = checkbox.closest('.mensualidad-item');
            if (item) {
                totalUSD += parseFloat(item.dataset.monto || 0);
            }
        });

        const totalBS = totalUSD * this.tasaBCV;

        const totalUSDElement = document.getElementById('totalUSD');
        const totalBSElement = document.getElementById('totalBS');
        const montoInput = document.getElementById('monto');

        if (totalUSDElement) {
            totalUSDElement.textContent = this.formatUSD(totalUSD);
        }

        if (totalBSElement) {
            totalBSElement.textContent = this.formatBs(totalBS);
        }

        const sidebarTotal = document.getElementById('sidebar-total');
        if (sidebarTotal) {
            sidebarTotal.textContent = this.formatUSD(totalUSD);
        }

        if (montoInput) {
            montoInput.value = totalUSD.toFixed(2);
        }

        this.actualizarConversion();
    }

    actualizarConversion() {
        const moneda = document.getElementById('moneda')?.value;
        const monto = parseFloat(document.getElementById('monto')?.value) || 0;
        const divConversion = document.getElementById('montoConversion');

        if (!divConversion) return;

        if (monto > 0) {
            if (moneda === 'USD') {
                const montoBs = monto * this.tasaBCV;
                divConversion.textContent = `Equivalente: ${this.formatBs(montoBs)}`;
            } else {
                const montoUSD = monto / this.tasaBCV;
                divConversion.textContent = `Equivalente: ${this.formatUSD(montoUSD)}`;
            }
        } else {
            divConversion.textContent = '';
        }
    }

    setButtonLoading(button, loading) {
        if (loading) {
            button.disabled = true;
            button.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
        } else {
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-upload me-2"></i>Registrar Pago';
        }
    }

    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alertDiv);

        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    formatUSD(amount) {
        return '$' + amount.toFixed(2);
    }

    formatBs(amount) {
        return amount.toFixed(2) + ' Bs';
    }
}

// Initialize the payment system immediately
window.registrarPagoCliente = new RegistrarPagoCliente();

// Global functions for immediate availability
function seleccionarTodos() {
    if (window.registrarPagoCliente && window.registrarPagoCliente.seleccionarTodos) {
        window.registrarPagoCliente.seleccionarTodos();
    } else {
        // Fallback básico
        document.querySelectorAll('.mensualidad-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
        actualizarContador();
    }
}

function deseleccionarTodos() {
    if (window.registrarPagoCliente && window.registrarPagoCliente.deseleccionarTodos) {
        window.registrarPagoCliente.deseleccionarTodos();
    } else {
        // Fallback básico
        document.querySelectorAll('.mensualidad-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        actualizarContador();
    }
}

function seleccionarPorTipo(tipo) {
    if (window.registrarPagoCliente && window.registrarPagoCliente.seleccionarPorTipo) {
        window.registrarPagoCliente.seleccionarPorTipo(tipo);
    }
}

function seleccionarSiguientesMeses(cantidad) {
    if (window.registrarPagoCliente && window.registrarPagoCliente.seleccionarSiguientesMeses) {
        window.registrarPagoCliente.seleccionarSiguientesMeses(cantidad);
    }
}

function actualizarContador() {
    if (window.registrarPagoCliente && window.registrarPagoCliente.updateContador) {
        window.registrarPagoCliente.updateContador();
    } else {
        // Fallback básico
        const checkboxes = document.querySelectorAll('.mensualidad-checkbox:checked');
        const count = checkboxes.length;
        const countElement = document.getElementById('mensualidadesCount');
        if (countElement) {
            countElement.textContent = count;
        }
    }
}

function calcularTotal() {
    if (window.registrarPagoCliente && window.registrarPagoCliente.calcularTotal) {
        window.registrarPagoCliente.calcularTotal();
    }
}

function actualizarConversion() {
    if (window.registrarPagoCliente && window.registrarPagoCliente.actualizarConversion) {
        window.registrarPagoCliente.actualizarConversion();
    }
}

// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Simple feedback
        const btn = event.target;
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i>';
        setTimeout(() => {
            btn.innerHTML = originalIcon;
        }, 1000);
    }).catch(function(err) {
        console.error('Error copying text: ', err);
    });
}

function generarMensualidadesFuturas(meses) {
    if (window.registrarPagoCliente && window.registrarPagoCliente.generarMensualidadesFuturas) {
        window.registrarPagoCliente.generarMensualidadesFuturas(meses);
    } else {
        // Fallback: redirect to generate future payments
        const currentUrl = window.location.href.split('?')[0];
        window.location.href = currentUrl + '?generar_futuras=' + meses;
    }
}
</script>

<style>
/* Estilos para mensualidad items */
.mensualidad-item:not(.disabled-month):hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}

/* Estilos para meses deshabilitados en selección secuencial */
.disabled-month {
    opacity: 0.5;
    cursor: not-allowed !important;
    background-color: #f8f9fa !important;
    border-color: #dee2e6 !important;
    position: relative;
}

.disabled-month::after {
    content: '🔒';
    position: absolute;
    top: 5px;
    right: 5px;
    font-size: 12px;
    opacity: 0.6;
}

.disabled-month .form-check-input:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.disabled-month .form-check-label {
    cursor: not-allowed;
    color: #6c757d !important;
}

/* Efecto hover para indicar que no se puede seleccionar */
.disabled-month:hover {
    background-color: #e9ecef !important;
    transform: none !important;
}

/* Step indicator styles */
.step-indicator .step {
    display: flex;
    align-items: flex-start;
}

.step-indicator .step-number {
    flex-shrink: 0;
    margin-top: 2px;
}

/* Mejoras para móviles */
@media (max-width: 576px) {
    /* Espaciado más cómodo en móviles */
    .card-body {
        padding: 1rem !important;
    }

    /* Botones más pequeños en móviles */
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    /* Mejor espaciado en la información bancaria */
    .bg-light {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }

    /* Ajustes en los títulos */
    h5 {
        font-size: 1.1rem;
    }

    h6 {
        font-size: 1rem;
    }

    /* Mejor legibilidad en los códigos */
    code {
        word-break: break-all;
        font-size: 0.8rem;
    }

    /* Ajustes en el resumen */
    #totalUSD, #totalBS {
        font-size: 1.1rem !important;
    }
}

/* Ajustes para tablets y móviles grandes */
@media (max-width: 768px) {
    /* Mejor distribución de botones */
    .btn-group {
        flex-wrap: wrap;
    }

    .btn-group .btn {
        flex: 1 1 auto;
        margin-bottom: 0.25rem;
    }

    /* Mejor espaciado en columnas */
    .col-md-6 {
        margin-bottom: 1rem;
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
