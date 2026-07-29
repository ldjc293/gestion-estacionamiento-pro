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
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-warning text-dark"
                                                        title="Cargar Deuda Histórica"
                                                        onclick="abrirModalDeudaHistoricaConsultor(<?= ($cliente['id'] ?? $cliente->id) ?>, '<?= htmlspecialchars($cliente['nombre_completo'] ?? $cliente->nombre_completo ?? '', ENT_QUOTES) ?>')">
                                                    <i class="bi bi-clock-history"></i> Deuda
                                                </button>
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

function abrirModalDeudaHistoricaConsultor(usuarioId, nombreUsuario) {
    const anioActual = new Date().getFullYear();
    let opcionesAnio = '';
    for (let a = anioActual; a >= 2020; a--) {
        opcionesAnio += `<option value="${a}">${a}</option>`;
    }

    const meses = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    let opcionesMes = '';
    meses.forEach((m, idx) => {
        opcionesMes += `<option value="${idx + 1}">${m}</option>`;
    });

    Swal.fire({
        title: 'Gestión de Deuda Histórica',
        html: `
            <div class="text-start mb-3">
                <ul class="nav nav-pills nav-justified mb-3" id="deudaTabsCon" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active small py-1" id="tab-cargar-tab-con" data-bs-toggle="pill" data-bs-target="#tab-cargar-con" type="button" role="tab">Cargar Deuda</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link small py-1 text-danger" id="tab-revertir-tab-con" data-bs-toggle="pill" data-bs-target="#tab-revertir-con" type="button" role="tab">Revertir Errónea</button>
                    </li>
                </ul>

                <div class="tab-content" id="deudaTabsContentCon">
                    <!-- Tab Cargar -->
                    <div class="tab-pane fade show active" id="tab-cargar-con" role="tabpanel">
                        <p class="small text-muted mb-2">Selecciona el <strong>primer mes que debe</strong> el cliente <strong>${nombreUsuario}</strong>.</p>
                        <div class="mb-2">
                            <label class="form-label font-semibold small mb-1">Año de inicio:</label>
                            <select id="swalAnioInicioCon" class="form-select form-select-sm">${opcionesAnio}</select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label font-semibold small mb-1">Mes de inicio:</label>
                            <select id="swalMesInicioCon" class="form-select form-select-sm">${opcionesMes}</select>
                        </div>
                    </div>
                    <!-- Tab Revertir -->
                    <div class="tab-pane fade" id="tab-revertir-con" role="tabpanel">
                        <div class="alert alert-warning p-2 small mb-2">
                            <i class="bi bi-shield-exclamation me-1"></i>
                            Elimina mensualidades no pagadas en caso de error. Las mensualidades pagadas se conservarán intactas.
                        </div>
                        <div class="row g-2">
                            <div class="col-6 mb-2">
                                <label class="form-label font-semibold small mb-1">Año Inicio:</label>
                                <select id="swalRevAnioInicioCon" class="form-select form-select-sm">${opcionesAnio}</select>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label font-semibold small mb-1">Mes Inicio:</label>
                                <select id="swalRevMesInicioCon" class="form-select form-select-sm">${opcionesMes}</select>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label font-semibold small mb-1">Año Fin (opcional):</label>
                                <select id="swalRevAnioFinCon" class="form-select form-select-sm"><option value="">Hasta la fecha</option>${opcionesAnio}</select>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label font-semibold small mb-1">Mes Fin (opcional):</label>
                                <select id="swalRevMesFinCon" class="form-select form-select-sm"><option value="">Hasta la fecha</option>${opcionesMes}</select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-check-circle me-1"></i> Procesar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2563eb',
        preConfirm: () => {
            const isRevertir = document.getElementById('tab-revertir-tab-con').classList.contains('active');
            if (isRevertir) {
                const anioInicio = document.getElementById('swalRevAnioInicioCon').value;
                const mesInicio = document.getElementById('swalRevMesInicioCon').value;
                const anioFin = document.getElementById('swalRevAnioFinCon').value;
                const mesFin = document.getElementById('swalRevMesFinCon').value;
                if (!anioInicio || !mesInicio) {
                    Swal.showValidationMessage('Selecciona año y mes de inicio válidos');
                    return false;
                }
                return { accion: 'revertir', anioInicio, mesInicio, anioFin, mesFin };
            } else {
                const anio = document.getElementById('swalAnioInicioCon').value;
                const mes = document.getElementById('swalMesInicioCon').value;
                if (!anio || !mes) {
                    Swal.showValidationMessage('Selecciona año y mes válidos');
                    return false;
                }
                return { accion: 'cargar', anio, mes };
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const val = result.value;
            if (val.accion === 'revertir') {
                Swal.fire({
                    title: 'Eliminando mensualidades erróneas...',
                    text: 'Procesando reversión de deuda',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                const formData = new FormData();
                formData.append('usuario_id', usuarioId);
                formData.append('anio_inicio', val.anioInicio);
                formData.append('mes_inicio', val.mesInicio);
                if (val.anioFin) formData.append('anio_fin', val.anioFin);
                if (val.mesFin) formData.append('mes_fin', val.mesFin);
                formData.append('csrf_token', '<?= generateCSRFToken() ?>');

                fetch('<?= url("consultor/revertir-deuda-historica") ?>', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('¡Proceso Completado!', data.message, 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'No se pudo revertir la deuda', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Ocurrió un error en la solicitud', 'error');
                });
            } else {
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Generando mensualidades históricas',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                const formData = new FormData();
                formData.append('usuario_id', usuarioId);
                formData.append('anio_inicio', val.anio);
                formData.append('mes_inicio', val.mes);
                formData.append('csrf_token', '<?= generateCSRFToken() ?>');

                fetch('<?= url("consultor/cargar-deuda-historica") ?>', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('¡Éxito!', data.message, 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'No se pudo cargar la deuda histórica', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Ocurrió un error en la solicitud', 'error');
                });
            }
        }
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
