<?php
$pageTitle = 'Relación de Ingresos vs. Egresos';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('consultor/dashboard')],
    ['label' => 'Relación Financiera', 'url' => '#']
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
                    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border-start border-4 border-primary">
                        <div>
                            <h4 class="mb-1 text-dark fw-bold">
                                <i class="bi bi-graph-up-arrow text-primary"></i> Relación Financiera (Ingresos vs. Egresos)
                            </h4>
                            <p class="text-muted mb-0 small">Consulta el balance consolidado entre las mensualidades cobradas y los gastos operativos.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interactive Filters Card -->
            <div class="card shadow-sm border-0 mb-4 rounded-3">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-filter-right"></i> Parámetros de Consulta</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="<?= url('consultor/reporte-gastos') ?>" class="row g-3 align-items-end" id="filterForm">
                        <!-- Tipo de Agrupación -->
                        <div class="col-md-3">
                            <label for="tipo_filtro" class="form-label small fw-bold">Tipo de Consulta</label>
                            <select name="tipo_filtro" id="tipo_filtro" class="form-select">
                                <option value="mensual" <?= $tipoFiltro === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                                <option value="trimestral" <?= $tipoFiltro === 'trimestral' ? 'selected' : '' ?>>Trimestral</option>
                                <option value="anual" <?= $tipoFiltro === 'anual' ? 'selected' : '' ?>>Anual</option>
                                <option value="rango_personalizado" <?= $tipoFiltro === 'rango_personalizado' ? 'selected' : '' ?>>Rango Personalizado</option>
                            </select>
                        </div>

                        <!-- Filtro Mensual -->
                        <div class="col-md-3 filter-group" id="group-mensual">
                            <label for="mes" class="form-label small fw-bold">Mes</label>
                            <select name="mes" id="mes" class="form-select">
                                <?php
                                $mesesNombres = [
                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                ];
                                foreach ($mesesNombres as $num => $nombre):
                                ?>
                                    <option value="<?= $num ?>" <?= $mes === $num ? 'selected' : '' ?>><?= $nombre ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filtro Trimestral -->
                        <div class="col-md-3 filter-group" id="group-trimestral">
                            <label for="trimestre" class="form-label small fw-bold">Trimestre</label>
                            <select name="trimestre" id="trimestre" class="form-select">
                                <option value="1" <?= $trimestre === 1 ? 'selected' : '' ?>>1er Trimestre (Ene - Mar)</option>
                                <option value="2" <?= $trimestre === 2 ? 'selected' : '' ?>>2do Trimestre (Abr - Jun)</option>
                                <option value="3" <?= $trimestre === 3 ? 'selected' : '' ?>>3er Trimestre (Jul - Sep)</option>
                                <option value="4" <?= $trimestre === 4 ? 'selected' : '' ?>>4to Trimestre (Oct - Dic)</option>
                            </select>
                        </div>

                        <!-- Año común (usado por mensual, trimestral y anual) -->
                        <div class="col-md-2 filter-group" id="group-anio">
                            <label for="anio" class="form-label small fw-bold">Año</label>
                            <select name="anio" id="anio" class="form-select">
                                <?php
                                $anioActual = intval(date('Y'));
                                for ($a = $anioActual; $a >= 2024; $a--):
                                ?>
                                    <option value="<?= $a ?>" <?= $anio === $a ? 'selected' : '' ?>><?= $a ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Rango Personalizado -->
                        <div class="col-md-3 filter-group" id="group-rango-inicio">
                            <label for="fecha_inicio" class="form-label small fw-bold">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fechaInicio) ?>">
                        </div>
                        <div class="col-md-3 filter-group" id="group-rango-fin">
                            <label for="fecha_fin" class="form-label small fw-bold">Fecha Fin</label>
                            <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="<?= htmlspecialchars($fechaFin) ?>">
                        </div>

                        <!-- Botón de Filtrado -->
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Generar Relación</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resumen de Balances en Tarjetas de Diseño Premium -->
            <div class="row g-3 mb-4">
                <!-- Columna Ingresos -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100 rounded-3" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; color: white !important;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 text-uppercase fw-bold opacity-75">Ingresos Totales (Cobros)</h6>
                                <span class="fs-4"><i class="bi bi-wallet2"></i></span>
                            </div>
                            <div class="mb-2">
                                <h3 class="mb-0 fw-bold"><?= formatUSD($resumen['ingresos']['USD_equiv']) ?></h3>
                                <small class="opacity-75">Equivalente total en USD</small>
                            </div>
                            <hr class="my-2 bg-white opacity-25">
                            <div class="d-flex justify-content-between small">
                                <span><i class="bi bi-cash"></i> Efectivo USD: <strong><?= formatUSD($resumen['ingresos']['USD_efectivo']) ?></strong></span>
                                <span><i class="bi bi-bank"></i> Digital Bs: <strong><?= formatBs($resumen['ingresos']['Bs_digital']) ?></strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Egresos -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100 rounded-3" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important; color: white !important;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 text-uppercase fw-bold opacity-75">Egresos Totales (Gastos)</h6>
                                <span class="fs-4"><i class="bi bi-cart-dash"></i></span>
                            </div>
                            <div class="mb-2">
                                <h3 class="mb-0 fw-bold"><?= formatUSD($resumen['egresos']['USD']) ?></h3>
                                <small class="opacity-75">Gastos registrados en USD</small>
                            </div>
                            <hr class="my-2 bg-white opacity-25">
                            <div class="d-flex justify-content-between small">
                                <span><i class="bi bi-cash-stack"></i> Gastos en Bs: <strong><?= formatBs($resumen['egresos']['Bs']) ?></strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Balance Neto -->
                <?php
                // Calcular balances
                $balanceUSD = $resumen['ingresos']['USD_equiv'] - $resumen['egresos']['USD'];
                $balanceBs = $resumen['ingresos']['Bs_equiv'] - $resumen['egresos']['Bs'];
                $isPositive = $balanceUSD >= 0;
                ?>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100 rounded-3" style="background: <?= $isPositive ? 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)' : 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)' ?> !important; color: white !important;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 text-uppercase fw-bold opacity-75">Balance Neto Estimado</h6>
                                <span class="fs-4"><i class="bi <?= $isPositive ? 'bi-graph-up' : 'bi-graph-down' ?>"></i></span>
                            </div>
                            <div class="mb-2">
                                <h3 class="mb-0 fw-bold"><?= formatUSD($balanceUSD) ?></h3>
                                <small class="opacity-75">Balance neto en USD</small>
                            </div>
                            <hr class="my-2 bg-white opacity-25">
                            <div class="d-flex justify-content-between small">
                                <span><i class="bi bi-calculator"></i> Balance Bs: <strong><?= formatBs($balanceBs) ?></strong></span>
                                <span class="badge <?= $isPositive ? 'bg-success' : 'bg-danger' ?>"><?= $isPositive ? 'Superávit' : 'Déficit' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Breakdown Rows -->
            <div class="row g-4">
                <!-- Desglose de Egresos (Gastos) -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-arrow-down-circle-fill"></i> Detalle de Egresos (Gastos)</h6>
                            <span class="badge bg-danger"><?= count($gastos) ?> Gastos</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($gastos)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-cart-x fs-1"></i>
                                    <p class="mt-2 small">No hay egresos registrados en este período.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-hover align-middle table-sm mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th class="ps-3">Fecha</th>
                                                <th>Detalle</th>
                                                <th class="text-end pe-3">Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($gastos as $g): ?>
                                                <tr>
                                                    <td class="ps-3 small"><?= date('d/m/Y', strtotime($g->fecha_gasto)) ?></td>
                                                    <td>
                                                        <div class="fw-bold small"><?= htmlspecialchars($g->nombre) ?></div>
                                                        <span class="text-muted small fs-7"><?= ucfirst($g->metodo_pago) ?> | Registrado por: <?= htmlspecialchars($g->registrado_por_nombre) ?></span>
                                                    </td>
                                                    <td class="text-end pe-3">
                                                        <strong class="<?= $g->moneda === 'USD' ? 'text-success' : 'text-primary' ?> small">
                                                            <?= $g->moneda === 'USD' ? formatUSD($g->monto) : formatBs($g->monto) ?>
                                                        </strong>
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

                <!-- Desglose de Ingresos (Cobros) -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-success"><i class="bi bi-arrow-up-circle-fill"></i> Detalle de Ingresos (Mensualidades)</h6>
                            <span class="badge bg-success"><?= count($ingresosDetalle) ?> Recibos</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($ingresosDetalle)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-wallet-fill fs-1"></i>
                                    <p class="mt-2 small">No hay cobros aprobados en este período.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-hover align-middle table-sm mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th class="ps-3">Fecha</th>
                                                <th>Cliente / Apto</th>
                                                <th class="text-end pe-3">Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ingresosDetalle as $i): ?>
                                                <tr>
                                                    <td class="ps-3 small"><?= date('d/m/Y', strtotime($i['fecha_pago'])) ?></td>
                                                    <td>
                                                        <div class="fw-bold small"><?= htmlspecialchars($i['cliente_nombre']) ?></div>
                                                        <span class="text-muted small fs-7">Apto: <?= $i['apartamento'] ?> | Recibo: <?= $i['numero_recibo'] ?></span>
                                                    </td>
                                                    <td class="text-end pe-3">
                                                        <strong class="text-success small">
                                                            <?php if ($i['moneda_pago'] === 'usd_efectivo'): ?>
                                                                <?= formatUSD($i['monto_usd']) ?>
                                                            <?php else: ?>
                                                                <?= formatBs($i['monto_bs']) ?>
                                                            <?php endif; ?>
                                                        </strong>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterSelect = document.getElementById('tipo_filtro');
    
    function toggleFilterInputs() {
        const value = filterSelect.value;
        
        // Esconder todos
        document.querySelectorAll('.filter-group').forEach(el => el.classList.add('d-none'));

        // Mostrar según el tipo seleccionado
        if (value === 'mensual') {
            document.getElementById('group-mensual').classList.remove('d-none');
            document.getElementById('group-anio').classList.remove('d-none');
        } else if (value === 'trimestral') {
            document.getElementById('group-trimestral').classList.remove('d-none');
            document.getElementById('group-anio').classList.remove('d-none');
        } else if (value === 'anual') {
            document.getElementById('group-anio').classList.remove('d-none');
        } else if (value === 'rango_personalizado') {
            document.getElementById('group-rango-inicio').classList.remove('d-none');
            document.getElementById('group-rango-fin').classList.remove('d-none');
        }
    }

    filterSelect.addEventListener('change', toggleFilterInputs);
    toggleFilterInputs(); // Ejecutar al cargar la página para inicializar
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
