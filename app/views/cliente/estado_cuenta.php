<?php
$pageTitle = 'Estado de Cuenta';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('cliente/dashboard')],
    ['label' => 'Estado de Cuenta', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <!-- Resumen de Estado de Cuenta -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center p-4 border-end">
                            <h6 class="text-muted mb-2">Total Mensualidades Pendientes</h6>
                            <h2 class="text-danger mb-0"><?= $deudaInfo['total_vencidas'] ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-4 border-end">
                            <h6 class="text-muted mb-2">Deuda Total</h6>
                            <h2 class="text-danger mb-0"><?= formatUSD($deudaInfo['deuda_total_usd']) ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-4">
                            <h6 class="text-muted mb-2">Total Pagado</h6>
                            <?php
                            $totalPagado = 0;
                            foreach ($pagos as $pago) {
                                if ($pago->estado_comprobante === 'aprobado') {
                                    $totalPagado += $pago->monto_usd;
                                }
                            }
                            ?>
                            <h2 class="text-success mb-0"><?= formatUSD($totalPagado) ?></h2>
                        </div>
                    </div>
                </div>

                <?php if ($deudaInfo['total_vencidas'] > 0): ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Atención:</strong>
                        <?php if ($deudaInfo['total_vencidas'] >= MESES_BLOQUEO): ?>
                            Tus controles están en riesgo de bloqueo. Por favor, regulariza tu situación lo antes posible.
                        <?php else: ?>
                            Tienes <?= $deudaInfo['total_vencidas'] ?> mensualidad(es) pendiente(s).
                        <?php endif; ?>
                        <a href="<?= url('cliente/registrar-pago') ?>" class="btn btn-sm btn-warning ms-3">
                            Registrar Pago Ahora
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Mensualidades -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bi bi-calendar3"></i> Historial de Mensualidades
                        </h6>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="filtroEstado" id="filtroTodas" checked>
                            <label class="btn btn-outline-primary" for="filtroTodas" onclick="filtrarMensualidades('todas')">Todas</label>

                            <input type="radio" class="btn-check" name="filtroEstado" id="filtroPendientes">
                            <label class="btn btn-outline-warning" for="filtroPendientes" onclick="filtrarMensualidades('pendiente')">Pendientes</label>

                            <input type="radio" class="btn-check" name="filtroEstado" id="filtroPagadas">
                            <label class="btn btn-outline-success" for="filtroPagadas" onclick="filtrarMensualidades('pagada')">Pagadas</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="tablaMensualidades">
                                <thead>
                                    <tr>
                                        <th>Mes/Año</th>
                                        <th>Monto USD</th>
                                        <th>Estado</th>
                                        <th>Fecha Generación</th>
                                        <th>Fecha Vencimiento</th>
                                        <th>Fecha Pago</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mensualidades as $mensualidad): ?>
                                        <?php
                                        // Determinar el estado real basado en pagos aprobados y fecha de vencimiento
                                        $estadoReal = $mensualidad['estado'];
                                        $tienePagoAprobado = !empty($mensualidad['fecha_pago']);
                                        $fechaVencimiento = strtotime($mensualidad['fecha_vencimiento']);
                                        $estaVencida = $fechaVencimiento < time();

                                        // Si tiene pago aprobado, mostrar como pagada
                                        if ($tienePagoAprobado) {
                                            $estadoReal = 'pagada';
                                        }
                                        // Si no tiene pago aprobado pero está vencida, mostrar como vencida
                                        elseif ($estaVencida) {
                                            $estadoReal = 'vencida';
                                        }
                                        ?>
                                        <tr data-estado="<?= $estadoReal ?>">
                                            <td>
                                                <strong><?= formatearMesAnio($mensualidad['mes'], $mensualidad['anio']) ?></strong>
                                            </td>
                                            <td><?= formatUSD($mensualidad['monto_usd']) ?></td>
                                            <td>
                                                <?php if ($estadoReal === 'pagada'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle"></i> Pagada
                                                    </span>
                                                <?php elseif ($estadoReal === 'vencida'): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-exclamation-triangle"></i> Vencida
                                                    </span>
                                                <?php else: ?>
                                                    <?php
                                                    $mesActual = date('Y-m');
                                                    $mesVencimiento = date('Y-m', $fechaVencimiento);
                                                    if ($mesVencimiento === $mesActual): ?>
                                                        <span class="badge bg-warning">
                                                            <i class="bi bi-clock"></i> Pendiente
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">
                                                            <i class="bi bi-calendar"></i> Pendiente
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($mensualidad['fecha_generacion'])) ?></td>
                                            <td><?= date('d/m/Y', strtotime($mensualidad['fecha_vencimiento'])) ?></td>
                                            <td>
                                                <?php if ($mensualidad['fecha_pago']): ?>
                                                    <?= date('d/m/Y', strtotime($mensualidad['fecha_pago'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar - Historial de Pagos -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bi bi-cash-stack"></i> Pagos Realizados
                        </h6>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                        <?php if (empty($pagos)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 48px;"></i>
                                <p class="mt-2">No hay pagos registrados</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pagos as $pago): ?>
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong>
                                                <?php 
                                                $isUsd = strpos($pago->moneda_pago, 'usd') !== false;
                                                $montoMostrar = $isUsd ? $pago->monto_usd : $pago->monto_bs;
                                                echo $isUsd ? formatUSD($montoMostrar) : formatBs($montoMostrar);
                                                ?>
                                            </strong>
                                            <br>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($pago->fecha_pago)) ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php if ($pago->estado_comprobante === 'aprobado'): ?>
                                                <span class="badge bg-success">Aprobado</span>
                                            <?php elseif ($pago->estado_comprobante === 'rechazado'): ?>
                                                <span class="badge bg-danger">Rechazado</span>
                                            <?php elseif ($pago->estado_comprobante === 'no_aplica'): ?>
                                                <span class="badge bg-info">Aprobado Automáticamente</span>
                                            <?php elseif ($pago->estado_comprobante === 'pendiente'): ?>
                                                <span class="badge bg-warning">Pendiente de Aprobación</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= ucfirst($pago->estado_comprobante) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted">Método:</small>
                                        <strong><?= ucfirst($pago->moneda_pago ?? '') ?></strong>
                                    </div>

                                    <?php if (!empty($pago->referencia ?? null)): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">Referencia:</small>
                                            <code><?= htmlspecialchars($pago->referencia ?? '') ?></code>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($pago->estado_comprobante === 'rechazado' && $pago->motivo_rechazo): ?>
                                        <div class="alert alert-danger alert-sm mb-2">
                                            <small><strong>Motivo rechazo:</strong> <?= htmlspecialchars($pago->motivo_rechazo) ?></small>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex gap-2">
                                        <a href="<?= url('cliente/ver-pago?id=' . $pago->id) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Ver
                                        </a>
                                        <?php if ($pago->estado_comprobante === 'aprobado' && $pago->numero_recibo): ?>
                                            <a href="<?= url('cliente/descargar-recibo?id=' . $pago->id) ?>" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-download"></i> Recibo
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (count($pagos) > 0): ?>
                            <a href="<?= url('cliente/historial-pagos') ?>" class="btn btn-outline-primary w-100 mt-3">
                                Ver Historial Completo
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<'JS'
<script>
function filtrarMensualidades(estado) {
    const tabla = document.getElementById('tablaMensualidades');
    const filas = tabla.querySelectorAll('tbody tr');

    filas.forEach(fila => {
        const estadoFila = fila.getAttribute('data-estado');

        if (estado === 'todas') {
            fila.style.display = '';
        } else {
            fila.style.display = estadoFila === estado ? '' : 'none';
        }
    });
}
</script>
JS;

require_once __DIR__ . '/../layouts/footer.php';
?>
