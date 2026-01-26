<?php
$pageTitle = 'Dashboard';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('cliente/dashboard')],
    ['label' => 'Dashboard', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <!-- Estado de Cuenta - Alerta si hay deuda -->
        <?php if ($deudaInfo['total_vencidas'] > 0): ?>
            <div class="alert alert-warning alert-permanent" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-1">Tienes mensualidades pendientes</h5>
                        <p class="mb-2">
                            Debes <strong><?= $deudaInfo['total_vencidas'] ?> mensualidades</strong>
                            por un total de <strong><?= formatUSD($deudaInfo['deuda_total_usd']) ?></strong>
                        </p>
                        <?php if ($deudaInfo['total_vencidas'] >= 2): ?>
                            <div class="alert alert-danger mb-0">
                                <i class="bi bi-shield-x"></i>
                                <strong>¡Atención!</strong> Tus controles están en riesgo de bloqueo.
                            </div>
                        <?php endif; ?>
                    </div>
                    <a href="<?= url('cliente/registrar-pago') ?>" class="btn btn-warning">
                        <i class="bi bi-upload"></i> Registrar Pago
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-success alert-permanent" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>¡Al día!</strong> No tienes mensualidades pendientes.
            </div>
        <?php endif; ?>

        <!-- DEBUG: Información detallada de mensualidades -->
        <?php if (isset($_GET['debug']) && $_GET['debug'] === 'mensualidades'): ?>
            <div class="alert alert-info">
                <h5>🔍 DEBUG: Información detallada de mensualidades</h5>
                <div class="row">
                    <div class="col-md-6">
                        <h6>📊 Todas las mensualidades del usuario:</h6>
                        <?php
                        $sqlTodas = "SELECT m.id, m.mes, m.anio, m.estado, m.fecha_vencimiento,
                                    CONCAT(m.anio, '-', LPAD(m.mes, 2, '0'), '-01') as mes_correspondiente
                             FROM mensualidades m
                             JOIN apartamento_usuario au ON au.id = m.apartamento_usuario_id
                             WHERE au.usuario_id = ? AND au.activo = TRUE
                             ORDER BY m.fecha_vencimiento";

                        $todasMensualidades = Database::fetchAll($sqlTodas, [$_SESSION['user_id']]);
                        ?>
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>ID</th><th>Mes/Año</th><th>Estado</th><th>Vencida</th><th>¿Pago?</th></tr></thead>
                            <tbody>
                                <?php foreach ($todasMensualidades as $m): ?>
                                    <?php
                                    $fechaVencimiento = strtotime($m['fecha_vencimiento']);
                                    $estaVencida = $fechaVencimiento < time();

                                    $sqlPago = "SELECT COUNT(DISTINCT p.id) as tiene_pago
                                                FROM pago_mensualidad pm
                                                JOIN pagos p ON p.id = pm.pago_id
                                                WHERE pm.mensualidad_id = ?
                                                  AND p.estado_comprobante IN ('aprobado', 'no_aplica')";

                                    $resultadoPago = Database::fetchOne($sqlPago, [$m['id']]);
                                    $tienePago = $resultadoPago && $resultadoPago['tiene_pago'] > 0;
                                    ?>
                                    <tr>
                                        <td><?=$m['id']?></td>
                                        <td><?=$m['mes']?>/<?=$m['anio']?></td>
                                        <td><?=$m['estado']?></td>
                                        <td><?=$estaVencida ? '⚠️ SÍ' : '✅ NO'?></td>
                                        <td><?=$tienePago ? '✅ SÍ' : '❌ NO'?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>🎯 Mensualidades que aparecen en dashboard:</h6>
                        <p><strong>Total mostradas:</strong> <?= count($mensualidadesPendientes) ?></p>
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>ID</th><th>Mes/Año</th><th>¿Mes Actual?</th><th>¿Vencida?</th><th>Razón</th></tr></thead>
                            <tbody>
                                <?php
                                $mesActual = date('Y-m');
                                foreach ($mensualidadesPendientes as $m):
                                    $fechaVencimiento = strtotime($m->fecha_vencimiento);
                                    $mesVencimiento = date('Y-m', $fechaVencimiento);
                                    $esMesActual = $mesVencimiento === $mesActual;
                                    $estaVencida = $fechaVencimiento < time();
                                    $razon = $esMesActual ? 'Mes actual' : 'Vencida';
                                ?>
                                    <tr>
                                        <td><?=$m->id?></td>
                                        <td><?=$m->mes?>/<?=$m->anio?></td>
                                        <td><?=$esMesActual ? '✅ SÍ' : '❌ NO'?></td>
                                        <td><?=$estaVencida ? '⚠️ SÍ' : '✅ NO'?></td>
                                        <td><?=$razon?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p><strong>Fecha actual:</strong> <?= date('Y-m-d H:i:s') ?> (Mes actual: <?=$mesActual?>)</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Estadísticas Principales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="value"><?= count($mensualidadesPendientes) ?></div>
                    <div class="label">Mensualidades Pendientes</div>
                    <?php if (count($mensualidadesPendientes) > 0): ?>
                        <div class="change negative">
                            <i class="bi bi-arrow-up"></i> <?= formatUSD($deudaInfo['deuda_total_usd']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="value"><?= count($ultimosPagos) ?></div>
                    <div class="label">Pagos Realizados</div>
                    <a href="<?= url('cliente/historial-pagos') ?>" class="text-decoration-none">
                        <small class="text-muted">Ver historial →</small>
                    </a>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="value"><?= count($pagosPendientesAprobacion) ?></div>
                    <div class="label">Pagos en Revisión</div>
                    <?php if (count($pagosPendientesAprobacion) > 0): ?>
                        <small class="text-warning">Pendiente de aprobación</small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                        <i class="bi bi-controller"></i>
                    </div>
                    <div class="value"><?= count($controles) ?></div>
                    <div class="label">Controles Asignados</div>
                    <a href="<?= url('cliente/controles') ?>" class="text-decoration-none">
                        <small class="text-muted">Ver controles →</small>
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Mensualidades Pendientes -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bi bi-list-check"></i> Mensualidades Pendientes
                        </h6>
                        <?php if (count($mensualidadesPendientes) > 0): ?>
                            <a href="<?= url('cliente/registrar-pago') ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-upload"></i> Registrar Pago
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($mensualidadesPendientes)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-check-circle text-success" style="font-size: 48px;"></i>
                                <p class="text-muted mt-3">No tienes mensualidades pendientes</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mes</th>
                                            <th>Monto USD</th>
                                            <th>Estado</th>
                                            <th>Fecha Vencimiento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mensualidadesPendientes as $mensualidad): ?>
    <tr>
        <td>
            <strong><?= formatearMesAnio($mensualidad->mes_correspondiente ?? '') ?></strong>
        </td>
        <td><?= formatUSD($mensualidad->monto_usd ?? 0) ?></td>
        <td>
            <?php
            $fechaVencimiento = strtotime($mensualidad->fecha_vencimiento ?? '');
            $mesActual = date('Y-m');
            $mesVencimiento = date('Y-m', $fechaVencimiento);

            if (($mensualidad->estado ?? '') === 'pagada'): ?>
                <span class="badge bg-success">Pagada</span>
            <?php elseif (($mensualidad->estado ?? '') === 'vencida' || $fechaVencimiento < time()): ?>
                <span class="badge bg-danger">Vencida</span>
            <?php elseif ($mesVencimiento === $mesActual): ?>
                <span class="badge bg-warning">Pendiente</span>
            <?php else: ?>
                <span class="badge bg-info">Próxima</span>
            <?php endif; ?>
        </td>
        <td><?= date('d/m/Y', strtotime($mensualidad->fecha_vencimiento ?? '')) ?></td>
    </tr>
<?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <th><?= formatUSD($deudaInfo['deuda_total_usd']) ?></th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Últimos Pagos -->
                <?php if (!empty($ultimosPagos)): ?>
                    <div class="card mt-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="bi bi-clock-history"></i> Últimos Pagos
                            </h6>
                            <a href="<?= url('cliente/historial-pagos') ?>" class="btn btn-sm btn-outline-primary">
                                Ver todos
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Monto</th>
                                            <th>Método</th>
                                            <th>Estado</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ultimosPagos as $pago): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($pago->fecha_pago)) ?></td>
                                                <td>
                                                    <strong><?= $pago->moneda_pago === 'USD' ? formatUSD($pago->monto_usd) : formatBs($pago->monto_bs) ?></strong>
                                                </td>
                                                <td><?= ucfirst($pago->metodo_pago ?? '') ?></td>
                                                <td>
                                                    <?php if (($pago->estado_comprobante ?? '') === 'aprobado'): ?>
                                                        <span class="badge bg-success">Aprobado</span>
                                                    <?php elseif (($pago->estado_comprobante ?? '') === 'rechazado'): ?>
                                                        <span class="badge bg-danger">Rechazado</span>
                                                    <?php elseif (($pago->estado_comprobante ?? '') === 'no_aplica'): ?>
                                                        <span class="badge bg-info">Aprobado Automáticamente</span>
                                                    <?php elseif (($pago->estado_comprobante ?? '') === 'pendiente'): ?>
                                                        <span class="badge bg-warning">Pendiente de Aprobación</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= ucfirst($pago->estado_comprobante ?? 'Desconocido') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="<?= url('cliente/ver-pago?id=' . $pago->id) ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar derecho -->
            <div class="col-md-4">
                <!-- Mis Controles -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-controller"></i> Mis Controles
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($controles)): ?>
                            <p class="text-muted text-center">No tienes controles asignados</p>
                        <?php else: ?>
                            <?php foreach ($controles as $control): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                    <div>
                                        <div class="fw-bold"><?= $control['numero_control_completo'] ?></div>
                                        <small class="text-muted">
                                            <?= $control['bloque'] ?>-<?= $control['numero_apartamento'] ?>
                                        </small>
                                    </div>
                                    <div>
                                        <?php if ($control['estado'] === 'activo'): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php elseif ($control['estado'] === 'bloqueado'): ?>
                                            <span class="badge bg-danger">Bloqueado</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= ucfirst($control['estado']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <a href="<?= url('cliente/controles') ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">
                                Ver todos los controles
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notificaciones recientes -->
                <?php if (!empty($notificaciones)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-bell"></i> Notificaciones
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($notificaciones as $notif): ?>
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-<?= $notif['tipo'] === 'pago_aprobado' ? 'check-circle text-success' : ($notif['tipo'] === 'pago_rechazado' ? 'x-circle text-danger' : 'info-circle text-info') ?> me-2"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold"><?= htmlspecialchars($notif['titulo']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($notif['mensaje']) ?></small>
                                            <div class="text-muted" style="font-size: 11px;">
                                                <?= date('d/m/Y H:i', strtotime($notif['fecha_creacion'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <a href="<?= url('cliente/notificaciones') ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">
                                Ver todas
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Información de Tarifa -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle"></i> Información
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql = "SELECT * FROM configuracion_tarifas WHERE activo = 1 ORDER BY fecha_vigencia_inicio DESC LIMIT 1";
                        $config = Database::fetchOne($sql);
                        $sql = "SELECT tasa_usd_bs FROM tasa_cambio_bcv ORDER BY fecha_registro DESC LIMIT 1";
                        $tasaBCV = Database::fetchOne($sql);
                        ?>
                        <div class="mb-3">
                            <small class="text-muted">Tarifa Mensual</small>
                            <div class="fw-bold text-primary"><?= formatUSD($config['monto_mensual_usd'] ?? 1.00) ?> por control</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Tasa BCV Actual</small>
                            <div class="fw-bold"><?= number_format($tasaBCV['tasa_usd_bs'] ?? 36.50, 2) ?> Bs/$</div>
                        </div>
                        <div class="alert alert-info mb-0" style="font-size: 13px;">
                            <i class="bi bi-lightbulb"></i>
                            <strong>Tip:</strong> Puedes pagar en USD o Bs. La conversión se hace con la tasa BCV del día.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
