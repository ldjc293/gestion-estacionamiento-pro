<?php
$pageTitle = 'Clientes y Controles Asignados';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('operador/dashboard')],
    ['label' => 'Clientes y Controles', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">
                            <i class="bi bi-people"></i> Clientes y Controles Asignados
                        </h4>
                        <small class="text-muted">Vista general de clientes con sus controles de estacionamiento</small>
                    </div>
                    <a href="<?= url('operador/vista-controles') ?>" class="btn btn-outline-primary">
                        <i class="bi bi-grid"></i> Ver Controles
                    </a>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="formClientesControles">
                    <div class="col-md-4 position-relative">
                        <label class="form-label">Buscar</label>
                        <input type="text"
                               class="form-control"
                               id="inputSearchClientesControles"
                               name="busqueda"
                               value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>"
                               placeholder="Nombre, email, cédula o apartamento"
                               autocomplete="off">
                        <div id="autocompleteResultsClientesControles" class="position-relative">
                            <div id="suggestionsListClientesControles" class="list-group position-absolute w-100 shadow border-0" style="z-index: 1000; max-height: 250px; overflow-y: auto; display: none;"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bloque</label>
                        <select class="form-select" name="bloque">
                            <option value="">Todos los bloques</option>
                            <?php for ($i = 27; $i <= 32; $i++): ?>
                                <option value="<?= $i ?>" <?= (isset($_GET['bloque']) && $_GET['bloque'] == $i) ? 'selected' : '' ?>>
                                    Bloque <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <a href="<?= url('operador/clientes-controles') ?>" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de Clientes -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-table"></i> Lista de Clientes
                    <span class="badge bg-primary ms-2"><?= count($clientes) ?> clientes</span>
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($clientes)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-people text-muted" style="font-size: 64px;"></i>
                        <p class="text-muted mt-3">No se encontraron clientes con los filtros aplicados</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Apartamento</th>
                                    <th>Controles Totales</th>
                                    <th>Estado de Controles</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientes as $cliente): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($cliente['nombre_completo']) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($cliente['email'] ?? 'Sin email') ?>
                                                    <?php if (!empty($cliente['cedula'])): ?>
                                                        <br><i class="bi bi-card-text"></i> <?= htmlspecialchars($cliente['cedula']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= htmlspecialchars($cliente['apartamento'] ?? 'Sin apartamento') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= $cliente['total_controles'] ?> controles
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if ($cliente['controles_activos'] > 0): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle"></i> <?= $cliente['controles_activos'] ?> activos
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($cliente['controles_bloqueados'] > 0): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-lock"></i> <?= $cliente['controles_bloqueados'] ?> bloqueados
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($cliente['controles_suspendidos'] > 0): ?>
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-pause-circle"></i> <?= $cliente['controles_suspendidos'] ?> suspendidos
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($cliente['controles_desactivados'] > 0): ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="bi bi-dash-circle"></i> <?= $cliente['controles_desactivados'] ?> desactivados
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($cliente['controles_perdidos'] > 0): ?>
                                                    <span class="badge bg-dark">
                                                        <i class="bi bi-question-circle"></i> <?= $cliente['controles_perdidos'] ?> perdidos
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button"
                                                        class="btn btn-outline-success"
                                                        title="Gestionar Controles"
                                                        onclick="abrirGestionControles(<?= $cliente['id'] ?>, '<?= htmlspecialchars($cliente['nombre_completo']) ?>', '<?= htmlspecialchars($cliente['apartamento'] ?? '') ?>')">
                                                    <i class="bi bi-controller"></i> Gestionar
                                                </button>
                                                <button type="button"
                                                        class="btn btn-outline-warning text-dark"
                                                        title="Cargar Deuda Histórica"
                                                        onclick="abrirModalDeudaHistoricaOperador(<?= $cliente['id'] ?>, '<?= htmlspecialchars($cliente['nombre_completo'], ENT_QUOTES) ?>')">
                                                    <i class="bi bi-clock-history"></i> Deuda
                                                </button>
                                                <a href="<?= url('operador/registrar-pago-presencial?buscar=' . urlencode($cliente['email'])) ?>"
                                                   class="btn btn-primary"
                                                   title="Registrar Pago">
                                                    <i class="bi bi-cash-coin"></i> Pago
                                                </a>
                                            </div>
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

<!-- Modal para gestión de controles -->
<div class="modal fade" id="gestionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    <i class="bi bi-sliders"></i>
                    Gestión Integral de Usuario - <span id="modalUsuarioNombre"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light" id="modalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function cargarControlesUsuarioModal(usuarioId) {
    fetch('<?= url('operador/gestionar-controles-usuario-ajax') ?>?id=' + usuarioId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Error reloading controls modal:', error);
            document.getElementById('modalContent').innerHTML = '<div class="alert alert-danger">Error al actualizar la lista de controles</div>';
        });
}

function abrirGestionControles(usuarioId, nombreUsuario, apartamento) {
    document.getElementById('modalUsuarioNombre').textContent = nombreUsuario + (apartamento ? ' (' + apartamento + ')' : '');
    document.getElementById('modalContent').innerHTML = '<div class="text-center py-4"><span class="spinner-border text-primary" role="status"></span> Cargando...</div>';

    const modalElement = document.getElementById('gestionModal');
    let modal = bootstrap.Modal.getInstance(modalElement);
    if (!modal) {
        modal = new bootstrap.Modal(modalElement);
    }
    modal.show();

    cargarControlesUsuarioModal(usuarioId);
}

function removerControlAjax(controlId, controlNumero, usuarioId) {
    if (confirm('¿Está seguro de que desea remover el control ' + controlNumero + ' del usuario?')) {
        const motivo = prompt('Motivo de la remoción:');
        if (motivo && motivo.trim() !== '') {
            fetch('<?= url('operador/remover-control-usuario') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'csrf_token': '<?= generateCSRFToken() ?>',
                    'usuario_id': usuarioId,
                    'control_id': controlId,
                    'motivo': motivo.trim()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cargarControlesUsuarioModal(usuarioId);
                } else {
                    alert('Error: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión');
            });
        }
    }
}

// Delegación de eventos para todos los formularios inyectados dinámicamente
document.addEventListener('submit', function(e) {
    if (e.target && (
        e.target.id === 'formAsignarControl' || 
        e.target.id === 'formEditarUsuario' || 
        e.target.id === 'formAsignarApartamento' || 
        e.target.classList.contains('form-cambiar-estado')
    )) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const usuarioId = form.querySelector('[name="usuario_id"]').value;
        const submitBtn = form.querySelector('button[type="submit"]');
        let originalBtnHtml = '';

        if (submitBtn) {
            originalBtnHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        }

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Notificar éxito visualmente o simplemente recargar el modal
                cargarControlesUsuarioModal(usuarioId);
            } else {
                alert('Error: ' + (data.message || 'Error desconocido'));
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
            // Intentar recargar el modal como fallback
            cargarControlesUsuarioModal(usuarioId);
        });
    }
});

// Recargar la página principal únicamente al cerrar el modal
const reloadPage = function () {
    location.reload();
};

document.getElementById('gestionModal').addEventListener('hidden.bs.modal', reloadPage);

// Autocompletado en tiempo real estilo registrar pago presencial
(function() {
    let debounceTimer;
    const searchInput = document.getElementById('inputSearchClientesControles');
    const suggestionsList = document.getElementById('suggestionsListClientesControles');

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
                            html += `<a href="#" class="list-group-item list-group-item-action py-2" onclick="selectSuggestionClientesControles('${cliente.nombre_completo.replace(/'/g, "\\'")}')">
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
            if (!e.target.closest('#autocompleteResultsClientesControles') && !e.target.closest('#inputSearchClientesControles')) {
                suggestionsList.style.display = 'none';
            }
        });
    }
})();

function selectSuggestionClientesControles(nombre) {
    const input = document.getElementById('inputSearchClientesControles');
    if (input) {
        input.value = nombre;
        document.getElementById('formClientesControles').submit();
    }
}

function abrirModalDeudaHistoricaOperador(usuarioId, nombreUsuario) {
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
        title: 'Cargar Deuda Histórica',
        html: `
            <div class="text-start mb-3">
                <p class="small text-muted mb-2">Selecciona el <strong>primer mes que debe</strong> el cliente <strong>${nombreUsuario}</strong>. Se generarán automáticamente todas las mensualidades faltantes desde ese mes hasta la actualidad.</p>
                <div class="mb-3">
                    <label class="form-label font-semibold small">Año de inicio de deuda:</label>
                    <select id="swalAnioInicioOp" class="form-select">${opcionesAnio}</select>
                </div>
                <div class="mb-3">
                    <label class="form-label font-semibold small">Mes de inicio de deuda:</label>
                    <select id="swalMesInicioOp" class="form-select">${opcionesMes}</select>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-check-circle me-1"></i> Generar Deuda',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#f59e0b',
        preConfirm: () => {
            const anio = document.getElementById('swalAnioInicioOp').value;
            const mes = document.getElementById('swalMesInicioOp').value;
            if (!anio || !mes) {
                Swal.showValidationMessage('Selecciona año y mes válidos');
                return false;
            }
            return { anio, mes };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                text: 'Generando mensualidades históricas',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData();
            formData.append('usuario_id', usuarioId);
            formData.append('anio_inicio', result.value.anio);
            formData.append('mes_inicio', result.value.mes);
            formData.append('csrf_token', '<?= generateCSRFToken() ?>');

            fetch('<?= url("operador/cargar-deuda-historica") ?>', {
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
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>