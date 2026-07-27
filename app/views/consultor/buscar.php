<?php
$pageTitle = 'Búsqueda General';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('consultor/dashboard')],
    ['label' => 'Búsqueda General', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-search text-primary"></i> Búsqueda General en el Sistema
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= url('consultor/buscar') ?>" class="row g-3" id="searchFormGeneral">
                    <div class="col-md-10 position-relative">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" 
                                   name="q" 
                                   id="clienteSearchInput"
                                   class="form-control form-control-lg" 
                                   placeholder="Buscar por nombre, cédula, correo, bloque o número de apartamento..." 
                                   value="<?= htmlspecialchars($criterio ?? '') ?>"
                                   required
                                   minlength="2"
                                   autocomplete="off">
                        </div>
                        <div id="autocompleteResultsGeneral" class="position-relative">
                            <div id="suggestionsListGeneral" class="list-group position-absolute w-100 shadow border-0" style="z-index: 1000; max-height: 250px; overflow-y: auto; display: none;"></div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($criterio)): ?>
            <h5 class="mb-3 text-muted">Resultados para "<strong class="text-dark"><?= htmlspecialchars($criterio) ?></strong>":</h5>

            <!-- Clientes Encontrados -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="bi bi-people-fill text-info"></i> Clientes Encontrados (<?= count($resultados['clientes'] ?? []) ?>)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($resultados['clientes'])): ?>
                        <div class="p-3 text-muted">No se encontraron clientes que coincidan con el criterio.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Nombre / Cédula</th>
                                        <th>Correo</th>
                                        <th>Teléfono</th>
                                        <th>Rol</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultados['clientes'] as $cliente): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($cliente['nombre_completo'] ?? $cliente->nombre_completo ?? '') ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($cliente['cedula'] ?? $cliente->cedula ?? '') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($cliente['email'] ?? $cliente->email ?? '') ?></td>
                                            <td><?= htmlspecialchars($cliente['telefono'] ?? $cliente->telefono ?? '-') ?></td>
                                            <td><span class="badge bg-secondary"><?= ucfirst(htmlspecialchars($cliente['rol'] ?? $cliente->rol ?? 'cliente')) ?></span></td>
                                            <td>
                                                <a href="<?= url('consultor/ver-cliente/' . ($cliente['id'] ?? $cliente->id)) ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-eye"></i> Ver Detalle
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Apartamentos Encontrados -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="bi bi-building text-primary"></i> Apartamentos Encontrados (<?= count($resultados['apartamentos'] ?? []) ?>)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($resultados['apartamentos'])): ?>
                        <div class="p-3 text-muted">No se encontraron apartamentos que coincidan con el criterio.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Bloque</th>
                                        <th>Número Apartamento</th>
                                        <th>Propietario / Residente</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultados['apartamentos'] as $apto): ?>
                                        <tr>
                                            <td><span class="badge bg-primary">Bloque <?= htmlspecialchars($apto['bloque'] ?? $apto->bloque ?? '') ?></span></td>
                                            <td><strong>Apto <?= htmlspecialchars($apto['numero_apartamento'] ?? $apto->numero_apartamento ?? '') ?></strong></td>
                                            <td><?= htmlspecialchars($apto['nombre_completo'] ?? $apto->nombre_completo ?? '-') ?></td>
                                            <td>
                                                <a href="<?= url('consultor/reporte-apartamentos?torre=' . urlencode($apto['bloque'] ?? $apto->bloque ?? '')) ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-list"></i> Ver en Reporte
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Controles Encontrados -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="bi bi-tag-fill text-warning"></i> Controles Encontrados (<?= count($resultados['controles'] ?? []) ?>)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($resultados['controles'])): ?>
                        <div class="p-3 text-muted">No se encontraron controles que coincidan con el criterio.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Número Control</th>
                                        <th>Receptor</th>
                                        <th>Posición</th>
                                        <th>Estado</th>
                                        <th>Asignado a</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultados['controles'] as $ctrl): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($ctrl['numero_control_completo'] ?? $ctrl->numero_control_completo ?? '') ?></strong></td>
                                            <td>Receptor <?= htmlspecialchars($ctrl['receptor'] ?? $ctrl->receptor ?? '') ?></td>
                                            <td>Posición <?= htmlspecialchars($ctrl['posicion_numero'] ?? $ctrl->posicion_numero ?? '') ?></td>
                                            <td>
                                                <?php 
                                                    $est = $ctrl['estado'] ?? $ctrl->estado ?? 'disponible';
                                                    $badgeClass = $est === 'asignado' ? 'bg-success' : ($est === 'bloqueado' ? 'bg-danger' : 'bg-secondary');
                                                ?>
                                                <span class="badge <?= $badgeClass ?>"><?= ucfirst($est) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($ctrl['nombre_completo'] ?? $ctrl->nombre_completo ?? 'Sin asignar') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let debounceTimer;
    const searchInput = document.getElementById('clienteSearchInput');
    const suggestionsList = document.getElementById('suggestionsListGeneral');

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
                            html += `<a href="#" class="list-group-item list-group-item-action py-2" onclick="seleccionarResultado('${cliente.nombre_completo.replace(/'/g, "\\'")}')">
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
                    console.error('Error en sugerencias de búsqueda:', error);
                    suggestionsList.style.display = 'none';
                }
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#autocompleteResultsGeneral') && !e.target.closest('#clienteSearchInput')) {
                suggestionsList.style.display = 'none';
            }
        });
    }
});

function seleccionarResultado(nombre) {
    const input = document.getElementById('clienteSearchInput');
    if (input) {
        input.value = nombre;
        document.getElementById('searchFormGeneral').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
