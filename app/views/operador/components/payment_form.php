<!-- Payment Form Component -->
<form action="<?= url('operador/process-registrar-pago-presencial') ?>" method="POST" enctype="multipart/form-data" id="formPago">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="cliente_id" value="<?= $cliente->id ?>">

    <!-- Monthly Payments Selection -->
    <div class="mb-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 gap-2">
            <h5 class="mb-0">
                <i class="bi bi-list-check text-primary me-2"></i>
                Seleccionar Mensualidades
            </h5>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-primary btn-sm" id="seleccionar-todos" onclick="seleccionarTodos()" title="Seleccionar Todos">
                    <i class="bi bi-check-all me-1"></i><span class="d-none d-sm-inline">Seleccionar Todos</span><span class="d-inline d-sm-none">Todos</span>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="deseleccionar-todos" onclick="deseleccionarTodos()" title="Deseleccionar">
                    <i class="bi bi-x-square me-1"></i><span class="d-none d-sm-inline">Deseleccionar</span><span class="d-inline d-sm-none">Ninguno</span>
                </button>
            </div>
        </div>

        <?php if (!is_array($mensualidadesPendientes) || count($mensualidadesPendientes) === 0): ?>
            <!-- No pending payments -->
            <div class="text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <h6 class="text-muted mt-3">No hay mensualidades pendientes</h6>
                <p class="text-muted small">Este cliente no tiene pagos pendientes de meses anteriores.</p>

                <!-- Generate future payments option -->
                <div class="card border-info mt-4">
                    <div class="card-body">
                        <h6 class="text-info mb-3">
                            <i class="bi bi-calendar-plus me-2"></i>
                            Generar Mensualidades Futuras
                        </h6>
                        <p class="mb-3 small text-muted">Puedes registrar pagos adelantados generando mensualidades futuras:</p>
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-info w-100" data-generar-meses="3">
                                    <i class="bi bi-calendar3 me-1"></i>3 Meses
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-info w-100" data-generar-meses="6">
                                    <i class="bi bi-calendar3 me-1"></i>6 Meses
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-info w-100" data-generar-meses="12">
                                    <i class="bi bi-calendar3 me-1"></i>12 Meses
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-info w-100" data-generar-meses="24">
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
                    <div class="row g-1">
                        <?php
                        $mesActual = date('n');
                        $anioActual = date('Y');
                        $contadores = ['pendientes' => 0, 'futuras' => 0];

                        foreach ($mensualidadesPendientes as $m) {
                            $fechaVencimiento = new DateTime($m->fecha_vencimiento);
                            $hoy = new DateTime();
                            if ($fechaVencimiento > $hoy) {
                                $contadores['futuras']++;
                            } else {
                                $contadores['pendientes']++;
                            }
                        }
                        ?>

                        <?php if ($contadores['pendientes'] > 0): ?>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-warning btn-sm" id="seleccionar-pendientes" onclick="seleccionarPorTipo('pendiente')">
                                <i class="bi bi-exclamation-triangle me-1"></i>Pendientes (<?= $contadores['pendientes'] ?>)
                            </button>
                        </div>
                        <?php endif; ?>

                        <?php if ($contadores['futuras'] > 0): ?>
                        <div class="col-auto">
                            <button type="button" class="btn btn-info btn-sm" id="seleccionar-futuros" onclick="seleccionarPorTipo('futuro')">
                                <i class="bi bi-calendar-plus me-1"></i>Futuros (<?= $contadores['futuras'] ?>)
                            </button>
                        </div>
                        <?php endif; ?>

                        <div class="col-auto">
                            <button type="button" class="btn btn-success btn-sm" id="seleccionar-3-meses" onclick="seleccionarSiguientesMeses(3)">
                                <i class="bi bi-calendar-check me-1"></i>Próximos 3
                            </button>
                        </div>

                        <?php if ($_GET['modo'] ?? '' === 'adelantado'): ?>
                        <div class="col-auto">
                            <button type="button" class="btn btn-info btn-sm" id="seleccionar-6-meses" onclick="seleccionarProximosMeses(6)">
                                <i class="bi bi-calendar-plus me-1"></i>Próximos 6
                            </button>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-info btn-sm" id="seleccionar-rango-4-11" onclick="seleccionarPorRango(3, 11)">
                                <i class="bi bi-calendar-range me-1"></i>Meses 4-11
                            </button>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-success btn-sm" id="seleccionar-ultimos-6" onclick="seleccionarUltimosMeses(6)">
                                <i class="bi bi-calendar-last me-1"></i>Últimos 6
                            </button>
                        </div>
                        <?php endif; ?>
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
                            Los montos se calculan automáticamente según la tarifa actual configurada
                        </small>
                    </div>
                </div>

                <div class="row g-2">
                    <?php
                    // Usar datos de tarifa pasados desde el controlador
                    $tarifaActual = $tarifaActual ?? null;
                    $montoPorControl = $tarifaActual ? $tarifaActual->monto_mensual_usd : 1.00;
                    $cantidadControles = $cantidadControles ?? 0;

                    foreach (is_array($mensualidadesPendientes) ? $mensualidadesPendientes : [] as $index => $mensualidad):
                        $fechaVencimiento = new DateTime($mensualidad->fecha_vencimiento);
                        $hoy = new DateTime();
                        
                        $esMesActual = ((int)$mensualidad->mes === (int)date('n') && (int)$mensualidad->anio === (int)date('Y'));
                        $esFutura = ($fechaVencimiento > $hoy) && !$esMesActual;
                        $esVencido = $mensualidad->estado === 'vencido';
                        $mesesDiff = ($fechaVencimiento->format('Y') - $hoy->format('Y')) * 12 +
                                   ($fechaVencimiento->format('n') - $hoy->format('n'));

                        // Respetar el monto registrado de la mensualidad si proviene de la BD
                        $cantControlesMes = $mensualidad->cantidad_controles ?? $cantidadControles;
                        $montoDinamicoUSD = isset($mensualidad->monto_usd) && (float)$mensualidad->monto_usd > 0 ? (float)$mensualidad->monto_usd : ($montoPorControl * $cantControlesMes);
                        $montoDinamicoBS = $montoDinamicoUSD * ($tasaBCV ?: 36.40);
                        ?>

                        <div class="col-md-6 col-lg-4">
                            <div class="form-check p-3 border rounded position-relative
                                 <?= $esMesActual ? 'border-primary bg-light shadow-sm' : '' ?>
                                 <?= $esFutura ? 'border-info bg-light' : '' ?>
                                 <?= $esVencido ? 'border-danger bg-light' : '' ?>
                                 <?= !$esFutura && !$esVencido && !$esMesActual ? 'border-success' : '' ?>
                                 mensualidad-item"
                                 data-monto="<?= $montoDinamicoUSD ?>"
                                 data-monto-bs="<?= $montoDinamicoBS ?>"
                                 data-tipo="<?= $esFutura ? 'futuro' : 'pendiente' ?>"
                                 data-mes="<?= date('Y-m', strtotime($mensualidad->mes_correspondiente)) ?>"
                                 data-meses-diferencia="<?= $mesesDiff ?>">

                                <!-- Status Badge -->
                                <div class="position-absolute top-0 end-0 p-1">
                                    <?php if ($esMesActual): ?>
                                        <span class="badge bg-primary small">
                                            <i class="bi bi-calendar-event"></i> Mes Actual
                                        </span>
                                    <?php elseif ($esFutura): ?>
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
                                           data-monto="<?= $montoDinamicoUSD ?>"
                                           data-monto-bs="<?= $montoDinamicoBS ?>">
                                    <label class="form-check-label w-100" for="mens_<?= $mensualidad->id ?>">
                                        <div class="ms-2">
                                            <div class="fw-bold">
                                                <?= formatearMesAnio($mensualidad->mes_correspondiente) ?>
                                            </div>
                                            <div class="text-primary small">
                                                $<?= number_format($montoDinamicoUSD, 2) ?>
                                                <?php if ($montoDinamicoUSD != $mensualidad->monto_usd): ?>
                                                    <span class="text-warning small">
                                                        (Original: $<?= number_format($mensualidad->monto_usd, 2) ?>)
                                                    </span>
                                                <?php endif; ?>
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
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fw-bold text-primary">Mensualidades seleccionadas</div>
                        <div class="h4 mb-0" id="mensualidadesCount">0</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-success">Total USD</div>
                        <div class="h4 mb-0 text-success" id="totalUSD">$0.00</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-info">Total Bs</div>
                        <div class="h4 mb-0 text-info" id="totalBS">0.00 Bs</div>
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
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold" id="label-referencia">Referencia</label>
            <input type="text"
                   class="form-control"
                   name="referencia"
                   id="referencia"
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
            <small class="mb-0">Los pagos presenciales se aprueban automáticamente y se genera el recibo de inmediato.</small>
        </div>
    </div>

    <!-- File Upload -->
    <div class="mb-4">
        <label class="form-label fw-bold" for="comprobante">
            Comprobante (PDF/JPG) *
        </label>
        <input type="file" class="form-control" name="comprobante" id="comprobante" accept=".pdf,.jpg,.png" required>
        <small class="text-muted">Formatos permitidos: PDF, JPG, PNG. Tamaño máximo: 5MB</small>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex gap-3 justify-content-end">
        <a href="<?= url('operador/dashboard') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-2"></i>Cancelar
        </a>
        <button type="submit" class="btn btn-success btn-lg" id="btnSubmit">
            <i class="bi bi-check-circle me-2"></i>Registrar y Aprobar Pago
        </button>
    </div>
</form>

<style>
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

/* Estilos para meses seleccionados bloqueados (para no poder deseleccionar si hay meses posteriores seleccionados) */
.locked-month {
    background-color: #e8f5e9 !important;
    border-color: #a5d6a7 !important;
    position: relative;
    box-shadow: inset 0 0 0 1px #81c784;
    pointer-events: none !important;
    user-select: none !important;
}

.locked-month * {
    pointer-events: none !important;
    cursor: not-allowed !important;
}

.locked-month::after {
    content: '🔒';
    position: absolute;
    top: 5px;
    right: 5px;
    font-size: 14px;
    opacity: 0.85;
}

.locked-month .form-check-input.locked-checkbox {
    pointer-events: none !important;
    cursor: not-allowed !important;
    background-color: #198754 !important;
    border-color: #198754 !important;
}

.locked-month .form-check-label {
    cursor: not-allowed !important;
    pointer-events: none !important;
}

.locked-month:hover {
    transform: none !important;
    cursor: not-allowed !important;
}

/* Mensualidad normal con hover */
.mensualidad-item:not(.disabled-month):not(.locked-month):hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}
</style>