<?php
$pageTitle = 'Control de Mensualidades';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Control de Mensualidades', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-calendar-check text-primary"></i> Control de Mensualidades
                </h6>
                <div class="btn-group btn-group-sm">
                    <button onclick="exportarExcel()" class="btn btn-success">
                        <i class="bi bi-file-excel"></i> Excel
                    </button>
                    <button onclick="exportarPDF()" class="btn btn-danger">
                        <i class="bi bi-file-pdf"></i> PDF
                    </button>
                    <button onclick="window.print()" class="btn btn-outline-primary">
                        <i class="bi bi-printer"></i> Imprimir
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" class="row g-3 mb-4" id="formAdminMensualidades">
                    <div class="col-md-3 position-relative">
                        <label class="form-label">Buscar Cliente</label>
                        <input type="text" name="busqueda" id="inputAdminBusqueda" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>" placeholder="Nombre, correo, cédula, apto..." autocomplete="off">
                        <div id="autocompleteResultsAdminMens" class="position-relative">
                            <div id="suggestionsListAdminMens" class="list-group position-absolute w-100 shadow border-0" style="z-index: 1000; max-height: 250px; overflow-y: auto; display: none;"></div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Meses Vencidos</label>
                        <select name="meses_min" class="form-select form-select-sm">
                            <option value="0" <?= (!isset($_GET['meses_min']) || $_GET['meses_min'] === '0' || $_GET['meses_min'] === '') ? 'selected' : '' ?>>0+ meses (Todos)</option>
                            <option value="1" <?= (isset($_GET['meses_min']) && $_GET['meses_min'] === '1') ? 'selected' : '' ?>>1+ meses</option>
                            <option value="2" <?= (isset($_GET['meses_min']) && $_GET['meses_min'] === '2') ? 'selected' : '' ?>>2+ meses</option>
                            <option value="3" <?= (isset($_GET['meses_min']) && $_GET['meses_min'] === '3') ? 'selected' : '' ?>>3+ meses</option>
                            <option value="4" <?= (isset($_GET['meses_min']) && $_GET['meses_min'] === '4') ? 'selected' : '' ?>>4+ meses (Bloqueado)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bloque</label>
                        <select name="torre" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach (($bloques ?? []) as $t): ?>
                                <option value="<?= $t ?>" <?= ($_GET['torre'] ?? '') == $t ? 'selected' : '' ?>>Bloque <?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Ordenar por</label>
                        <select name="orden" class="form-select form-select-sm">
                            <option value="meses_desc" <?= ($_GET['orden'] ?? 'meses_desc') === 'meses_desc' ? 'selected' : '' ?>>Meses (Mayor a Menor)</option>
                            <option value="meses_asc" <?= ($_GET['orden'] ?? '') === 'meses_asc' ? 'selected' : '' ?>>Meses (Menor a Mayor)</option>
                            <option value="deuda_desc" <?= ($_GET['orden'] ?? '') === 'deuda_desc' ? 'selected' : '' ?>>Deuda (Mayor a Menor)</option>
                            <option value="deuda_asc" <?= ($_GET['orden'] ?? '') === 'deuda_asc' ? 'selected' : '' ?>>Deuda (Menor a Mayor)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                            <a href="?" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Resumen -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="value"><?= count($morosos) ?></div>
                            <div class="label">Total Clientes</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="value"><?= formatUSD($totalDeuda ?? 0) ?></div>
                            <div class="label">Deuda Total</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                                <i class="bi bi-calendar-x"></i>
                            </div>
                            <div class="value"><?= $totalMensualidades ?? 0 ?></div>
                            <div class="label">Mensualidades Vencidas</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(220, 38, 38, 0.1); color: #dc2626;">
                                <i class="bi bi-lock"></i>
                            </div>
                            <div class="value"><?= $bloqueados ?? 0 ?></div>
                            <div class="label">Controles Bloqueados</div>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <?php if (empty($morosos)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-check-circle text-success" style="font-size: 80px;"></i>
                        <h5 class="mt-3">¡Excelente!</h5>
                        <p class="text-muted">No hay clientes con morosidad en este momento</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="tableMorosidad">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Apartamento</th>
                                    <th>Controles</th>
                                    <th>Meses Vencidos</th>
                                    <th>Deuda Total</th>
                                    <th>Última Mensualidad</th>
                                    <th>Estado</th>
                                    <th>Contacto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($morosos as $moroso): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($moroso['nombre_completo'] ?? '') ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($moroso['cedula'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                Bloque <?= $moroso['torre'] ?> - Apto <?= $moroso['apartamento'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= $moroso['total_controles'] ?> controles
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($moroso['meses_vencidos'] > 0): ?>
                                                <span class="badge bg-warning" style="font-size: 14px;">
                                                    <?= $moroso['meses_vencidos'] ?> meses
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success" style="font-size: 14px;">
                                                    Al día
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($moroso['deuda_total'] > 0): ?>
                                                <strong class="text-danger"><?= formatUSD($moroso['deuda_total']) ?></strong>
                                            <?php else: ?>
                                                <strong class="text-success">$0.00</strong>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($moroso['ultima_mensualidad']): ?>
                                                <small><?= date('m/Y', strtotime($moroso['ultima_mensualidad'])) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($moroso['meses_vencidos'] >= MESES_BLOQUEO): ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-lock"></i> Bloqueado
                                                </span>
                                            <?php elseif ($moroso['meses_vencidos'] >= 3): ?>
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-exclamation-triangle"></i> Crítico
                                                </span>
                                            <?php elseif ($moroso['meses_vencidos'] > 0): ?>
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-clock"></i> Vencido
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Solvente
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <i class="bi bi-envelope"></i> <?= htmlspecialchars($moroso['email'] ?? '') ?><br>
                                                <?php if ($moroso['telefono']): ?>
                                                    <i class="bi bi-phone"></i> <?= htmlspecialchars($moroso['telefono'] ?? '') ?>
                                                <?php endif; ?>
                                            </small>
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

<?php
$additionalJS = <<<JS
<script>
function exportarExcel() {
    window.location.href = URL_BASE + '/admin/export-morosidad?formato=excel';
}

function exportarPDF() {
    window.location.href = URL_BASE + '/admin/export-morosidad?formato=pdf';
}
</script>
JS;
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let debounceTimer;
    const searchInput = document.getElementById('inputAdminBusqueda');
    const suggestionsList = document.getElementById('suggestionsListAdminMens');

    if (searchInput && suggestionsList) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.trim();
            clearTimeout(debounceTimer);

            if (searchTerm.length < 2) {
                suggestionsList.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(async function() {
                try {
                    const response = await fetch(`<?= url('operador/buscar-cliente') ?>?q=${encodeURIComponent(searchTerm)}`);
                    const data = await response.json();

                    if (data.length > 0) {
                        let html = '';
                        data.forEach(cliente => {
                            html += `<a href="#" class="list-group-item list-group-item-action py-2" onclick="selectSuggestionAdminMens('${cliente.nombre_completo.replace(/'/g, "\\'")}')">
                                        <div class="fw-bold text-primary mb-0">${cliente.nombre_completo}</div>
                                        <small class="text-muted">${cliente.email || ''} ${cliente.cedula ? '| Cédula: ' + cliente.cedula : ''} ${cliente.apartamento ? '| ' + cliente.apartamento : ''}</small>
                                    </a>`;
                        });
                        suggestionsList.innerHTML = html;
                        suggestionsList.style.display = 'block';
                    } else {
                        suggestionsList.style.display = 'none';
                    }
                } catch (error) {
                    console.error('Error autocompletar:', error);
                    suggestionsList.style.display = 'none';
                }
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#autocompleteResultsAdminMens') && !e.target.closest('#inputAdminBusqueda')) {
                suggestionsList.style.display = 'none';
            }
        });
    }
});

function selectSuggestionAdminMens(nombre) {
    const input = document.getElementById('inputAdminBusqueda');
    if (input) {
        input.value = nombre;
        document.getElementById('formAdminMensualidades').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
