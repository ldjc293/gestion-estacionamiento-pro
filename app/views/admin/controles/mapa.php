<?php
$pageTitle = 'Mapa de Controles';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Mapa de Controles', 'url' => '#']
];

require_once __DIR__ . '/../../layouts/header.php';
?>

<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../../layouts/alerts.php'; ?>

        <!-- Estadísticas Generales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-key text-primary" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $estadisticas['total'] ?? 0 ?></h3>
                        <small class="text-muted">Total Controles</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $estadisticas['activos'] ?? 0 ?></h3>
                        <small class="text-muted">Activos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-dash-circle text-warning" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $estadisticas['vacios'] ?? 0 ?></h3>
                        <small class="text-muted">Disponibles</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-lock text-danger" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $estadisticas['bloqueados'] ?? 0 ?></h3>
                        <small class="text-muted">Bloqueados</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestañas de Vista -->
        <ul class="nav nav-tabs mb-4" id="vistaControlTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="vista-usuarios-tab" data-bs-toggle="tab" data-bs-target="#vista-usuarios" type="button">
                    <i class="bi bi-people"></i> Vista por Usuario/Apartamento
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="vista-posiciones-tab" data-bs-toggle="tab" data-bs-target="#vista-posiciones" type="button">
                    <i class="bi bi-grid-3x3"></i> Vista por Posiciones
                </button>
            </li>
        </ul>

        <div class="tab-content" id="vistaControlTabsContent">
            <!-- Vista por Usuario/Apartamento -->
            <div class="tab-pane fade show active" id="vista-usuarios" role="tabpanel">
                <?php
                // Obtener controles agrupados por usuario/apartamento
                $sql = "SELECT
                            u.id as usuario_id,
                            u.nombre_completo,
                            u.email,
                            CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento,
                            a.id as apartamento_id,
                            au.id as apartamento_usuario_id,
                            au.cantidad_controles,
                            COUNT(c.id) as controles_asignados,
                            GROUP_CONCAT(c.numero_control_completo ORDER BY c.posicion_numero SEPARATOR ', ') as lista_controles,
                            SUM(CASE WHEN c.estado = 'activo' THEN 1 ELSE 0 END) as controles_activos,
                            SUM(CASE WHEN c.estado = 'bloqueado' THEN 1 ELSE 0 END) as controles_bloqueados
                        FROM apartamento_usuario au
                        JOIN usuarios u ON u.id = au.usuario_id
                        JOIN apartamentos a ON a.id = au.apartamento_id
                        LEFT JOIN controles_estacionamiento c ON c.apartamento_usuario_id = au.id
                        WHERE au.activo = TRUE
                        GROUP BY au.id, u.id, u.nombre_completo, u.email, a.bloque, a.numero_apartamento, a.id, au.cantidad_controles
                        ORDER BY a.bloque, a.numero_apartamento";

                $controlesUsuarios = Database::fetchAll($sql);
                ?>

                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-people"></i> Controles por Usuario/Apartamento
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($controlesUsuarios)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mb-0 mt-3">No hay apartamentos con residentes asignados</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Apartamento</th>
                                            <th>Residente</th>
                                            <th>Email</th>
                                            <th>Cantidad Asignada</th>
                                            <th>Controles Registrados</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($controlesUsuarios as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($item['apartamento']) ?></strong>
                                                </td>
                                                <td>
                                                    <i class="bi bi-person"></i>
                                                    <?= htmlspecialchars($item['nombre_completo']) ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?= htmlspecialchars($item['email']) ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?= $item['cantidad_controles'] ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($item['controles_asignados'] > 0): ?>
                                                        <span class="badge bg-info"><?= $item['controles_asignados'] ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($item['controles_activos'] > 0): ?>
                                                        <span class="badge bg-success"><?= $item['controles_activos'] ?> Activo(s)</span>
                                                    <?php endif; ?>
                                                    <?php if ($item['controles_bloqueados'] > 0): ?>
                                                        <span class="badge bg-danger"><?= $item['controles_bloqueados'] ?> Bloqueado(s)</span>
                                                    <?php endif; ?>
                                                    <?php if ($item['controles_asignados'] == 0): ?>
                                                        <span class="text-muted">Sin controles</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="<?= url('admin/usuarios/editar?id=' . $item['usuario_id']) ?>"
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Gestionar Controles">
                                                        <i class="bi bi-gear"></i>
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
            </div>

            <!-- Vista por Posiciones -->
            <div class="tab-pane fade" id="vista-posiciones" role="tabpanel">
                <!-- Búsqueda rápida -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-search"></i> Búsqueda de Controles
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="<?= url('admin/controles') ?>" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="buscar" placeholder="Buscar por posición (Ej: 15, 150A, 250B)">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="estado">
                                    <option value="">Todos los estados</option>
                                    <option value="activo">Activos</option>
                                    <option value="vacio">Disponibles</option>
                                    <option value="bloqueado">Bloqueados</option>
                                    <option value="suspendido">Suspendidos</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="<?= url('admin/controles') ?>" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-x-circle"></i> Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

        <!-- Mapa de Controles -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-map"></i> Mapa de Controles (Posiciones 1-250)
                </h6>
                <span class="badge bg-info">Total: <?= count($mapa ?? []) ?> posiciones</span>
            </div>
            <div class="card-body">
                <?php if (empty($mapa)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mb-0 mt-3">No hay controles registrados</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Posición</th>
                                    <th>Receptor A</th>
                                    <th>Receptor B</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mapa as $posicion => $receptores): ?>
                                    <tr>
                                        <td class="align-middle">
                                            <strong style="font-size: 1.1rem;"><?= $posicion ?></strong>
                                        </td>

                                        <!-- Receptor A -->
                                        <td style="width: 45%;">
                                            <?php if (isset($receptores['A'])): ?>
                                                <?php $controlA = $receptores['A']; ?>
                                                <div class="p-2 border rounded
                                                    <?php if ($controlA['estado'] == 'activo'): ?>bg-success bg-opacity-10 border-success
                                                    <?php elseif ($controlA['estado'] == 'bloqueado'): ?>bg-danger bg-opacity-10 border-danger
                                                    <?php elseif ($controlA['estado'] == 'vacio'): ?>bg-light border-secondary
                                                    <?php else: ?>bg-warning bg-opacity-10 border-warning<?php endif; ?>">

                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong><?= $controlA['numero_control_completo'] ?></strong>
                                                            <span class="badge
                                                                <?php if ($controlA['estado'] == 'activo'): ?>bg-success
                                                                <?php elseif ($controlA['estado'] == 'bloqueado'): ?>bg-danger
                                                                <?php elseif ($controlA['estado'] == 'vacio'): ?>bg-secondary
                                                                <?php else: ?>bg-warning<?php endif; ?> ms-2">
                                                                <?= ucfirst($controlA['estado']) ?>
                                                            </span>
                                                        </div>
                                                        <select class="form-select form-select-sm estado-control-select"
                                                                style="width: auto;"
                                                                data-control-id="<?= $controlA['id'] ?>"
                                                                data-control-numero="<?= $controlA['numero_control_completo'] ?>"
                                                                data-estado-actual="<?= $controlA['estado'] ?>"
                                                                onchange="cambiarEstadoControl(this)">
                                                            <option value="">Cambiar...</option>
                                                            <option value="activo" <?= $controlA['estado'] == 'activo' ? 'selected' : '' ?>>Activo</option>
                                                            <option value="vacio" <?= $controlA['estado'] == 'vacio' ? 'selected' : '' ?>>Disponible</option>
                                                            <option value="bloqueado" <?= $controlA['estado'] == 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                                                            <option value="suspendido" <?= $controlA['estado'] == 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
                                                            <option value="desactivado" <?= $controlA['estado'] == 'desactivado' ? 'selected' : '' ?>>Desactivado</option>
                                                            <option value="perdido" <?= $controlA['estado'] == 'perdido' ? 'selected' : '' ?>>Perdido</option>
                                                        </select>
                                                    </div>

                                                    <?php if (!empty($controlA['propietario_nombre'])): ?>
                                                        <div class="mt-1 small">
                                                            <i class="bi bi-person"></i>
                                                            <?= htmlspecialchars($controlA['propietario_nombre']) ?>
                                                        </div>
                                                        <?php if (!empty($controlA['apartamento'])): ?>
                                                            <div class="small text-muted">
                                                                <i class="bi bi-building"></i>
                                                                Apto. <?= htmlspecialchars($controlA['apartamento']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div class="mt-1 small text-muted">
                                                            <i class="bi bi-dash-circle"></i> Disponible
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Receptor B -->
                                        <td style="width: 45%;">
                                            <?php if (isset($receptores['B'])): ?>
                                                <?php $controlB = $receptores['B']; ?>
                                                <div class="p-2 border rounded
                                                    <?php if ($controlB['estado'] == 'activo'): ?>bg-success bg-opacity-10 border-success
                                                    <?php elseif ($controlB['estado'] == 'bloqueado'): ?>bg-danger bg-opacity-10 border-danger
                                                    <?php elseif ($controlB['estado'] == 'vacio'): ?>bg-light border-secondary
                                                    <?php else: ?>bg-warning bg-opacity-10 border-warning<?php endif; ?>">

                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong><?= $controlB['numero_control_completo'] ?></strong>
                                                            <span class="badge
                                                                <?php if ($controlB['estado'] == 'activo'): ?>bg-success
                                                                <?php elseif ($controlB['estado'] == 'bloqueado'): ?>bg-danger
                                                                <?php elseif ($controlB['estado'] == 'vacio'): ?>bg-secondary
                                                                <?php else: ?>bg-warning<?php endif; ?> ms-2">
                                                                <?= ucfirst($controlB['estado']) ?>
                                                            </span>
                                                        </div>
                                                        <select class="form-select form-select-sm estado-control-select"
                                                                style="width: auto;"
                                                                data-control-id="<?= $controlB['id'] ?>"
                                                                data-control-numero="<?= $controlB['numero_control_completo'] ?>"
                                                                data-estado-actual="<?= $controlB['estado'] ?>"
                                                                onchange="cambiarEstadoControl(this)">
                                                            <option value="">Cambiar...</option>
                                                            <option value="activo" <?= $controlB['estado'] == 'activo' ? 'selected' : '' ?>>Activo</option>
                                                            <option value="vacio" <?= $controlB['estado'] == 'vacio' ? 'selected' : '' ?>>Disponible</option>
                                                            <option value="bloqueado" <?= $controlB['estado'] == 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                                                            <option value="suspendido" <?= $controlB['estado'] == 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
                                                            <option value="desactivado" <?= $controlB['estado'] == 'desactivado' ? 'selected' : '' ?>>Desactivado</option>
                                                            <option value="perdido" <?= $controlB['estado'] == 'perdido' ? 'selected' : '' ?>>Perdido</option>
                                                        </select>
                                                    </div>

                                                    <?php if (!empty($controlB['propietario_nombre'])): ?>
                                                        <div class="mt-1 small">
                                                            <i class="bi bi-person"></i>
                                                            <?= htmlspecialchars($controlB['propietario_nombre']) ?>
                                                        </div>
                                                        <?php if (!empty($controlB['apartamento'])): ?>
                                                            <div class="small text-muted">
                                                                <i class="bi bi-building"></i>
                                                                Apto. <?= htmlspecialchars($controlB['apartamento']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div class="mt-1 small text-muted">
                                                            <i class="bi bi-dash-circle"></i> Disponible
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
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

                <!-- Leyenda -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle"></i> Leyenda
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="p-2 bg-success bg-opacity-10 border border-success rounded mb-2">
                                    <strong>Activo:</strong> Control asignado y en uso
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 bg-light border border-secondary rounded mb-2">
                                    <strong>Disponible:</strong> Control libre para asignar
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 bg-danger bg-opacity-10 border border-danger rounded mb-2">
                                    <strong>Bloqueado:</strong> Control bloqueado (morosidad)
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 bg-warning bg-opacity-10 border border-warning rounded mb-2">
                                    <strong>Otros:</strong> Suspendido, perdido, etc.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal para cambiar estado de control -->
<div class="modal fade" id="modalCambiarEstado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Estado del Control</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/cambiarEstadoControl') ?>" method="POST" id="formCambiarEstado">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="control_id" id="modal_control_id">

                    <div class="alert alert-info">
                        <strong>Control:</strong> <span id="modal_control_numero"></span><br>
                        <strong>Estado actual:</strong> <span id="modal_control_estado_actual" class="badge"></span>
                    </div>

                    <div class="mb-3">
                        <label for="modal_estado" class="form-label fw-bold">Nuevo Estado *</label>
                        <select class="form-select" name="estado" id="modal_estado" required>
                            <option value="">Seleccione...</option>
                            <option value="activo">Activo</option>
                            <option value="vacio">Disponible (Vacío)</option>
                            <option value="bloqueado">Bloqueado</option>
                            <option value="suspendido">Suspendido</option>
                            <option value="desactivado">Desactivado</option>
                            <option value="perdido">Perdido</option>
                        </select>
                        <small class="text-muted">Selecciona el nuevo estado del control</small>
                    </div>

                    <div class="mb-3">
                        <label for="modal_motivo" class="form-label fw-bold">Motivo *</label>
                        <textarea class="form-control"
                                  name="motivo"
                                  id="modal_motivo"
                                  required
                                  rows="3"
                                  maxlength="255"
                                  placeholder="Describe el motivo del cambio de estado"></textarea>
                        <small class="text-muted">Explica por qué se cambia el estado del control</small>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Importante:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Si cambias a "Disponible", el control se desasignará del propietario actual</li>
                            <li>Si cambias a "Bloqueado", el control quedará inactivo hasta desbloquearlo</li>
                            <li>Los cambios quedan registrados en el log del sistema</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Cambiar Estado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Función para cambiar estado de control mediante AJAX
function cambiarEstadoControl(selectElement) {
    const controlId = selectElement.dataset.controlId;
    const controlNumero = selectElement.dataset.controlNumero;
    const nuevoEstado = selectElement.value;
    const estadoActual = selectElement.dataset.estadoActual;

    // Si no se seleccionó un estado, salir
    if (!nuevoEstado || nuevoEstado === estadoActual) {
        selectElement.value = estadoActual; // Restaurar valor original
        return;
    }

    // Preparar mensaje de confirmación según el tipo de cambio
    let mensajeConfirmacion = '';
    if (nuevoEstado === 'bloqueado') {
        mensajeConfirmacion = '¿Está seguro de BLOQUEAR el control ' + controlNumero + '?\n\nEsto impedirá su uso hasta que sea desbloqueado.';
    } else if (nuevoEstado === 'vacio') {
        mensajeConfirmacion = '¿Está seguro de marcar como DISPONIBLE el control ' + controlNumero + '?\n\nEsto lo desasignará del propietario actual.';
    } else {
        mensajeConfirmacion = '¿Cambiar el control ' + controlNumero + ' de "' + estadoActual + '" a "' + nuevoEstado + '"?';
    }

    // Pedir motivo del cambio
    const motivo = prompt('Por favor, ingrese el motivo para cambiar el estado del control ' + controlNumero + ':');
    if (!motivo || motivo.trim() === '') {
        selectElement.value = estadoActual; // Restaurar valor original
        return;
    }

    // Confirmar el cambio
    if (!confirm(mensajeConfirmacion)) {
        selectElement.value = estadoActual; // Restaurar valor original
        return;
    }

    // Mostrar indicador de carga
    const originalButton = selectElement;
    const originalHtml = originalButton.innerHTML;
    originalButton.innerHTML = '<option>Cargando...</option>';
    originalButton.disabled = true;

    // Realizar petición AJAX
    fetch('<?= url("admin/cambiarEstadoControl") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            'csrf_token': '<?= generateCSRFToken() ?>',
            'control_id': controlId,
            'estado': nuevoEstado,
            'motivo': motivo.trim()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar badge y colores sin recargar página
            actualizarVistaControl(controlId, nuevoEstado, data.mensaje);

            // Actualizar dataset
            selectElement.dataset.estadoActual = nuevoEstado;

            // Mostrar notificación de éxito
            mostrarNotificacion('success', data.mensaje);
        } else {
            // Restaurar estado original en caso de error
            selectElement.value = estadoActual;
            mostrarNotificacion('danger', data.mensaje || 'Error al cambiar el estado');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        selectElement.value = estadoActual;
        mostrarNotificacion('danger', 'Error de conexión. Por favor, intente nuevamente.');
    })
    .finally(() => {
        // Restaurar el select
        originalButton.innerHTML = originalHtml;
        originalButton.disabled = false;

        // Actualizar las opciones para que el nuevo estado quede seleccionado
        Array.from(originalButton.options).forEach(option => {
            if (option.value === nuevoEstado) {
                option.selected = true;
            } else if (option.value === "") {
                option.text = "Cambiar...";
            }
        });
    });
}

// Función para actualizar la vista del control sin recargar
function actualizarVistaControl(controlId, nuevoEstado, mensaje) {
    // Buscar el elemento del control en la página
    const controlElement = document.querySelector(`[data-control-id="${controlId}"]`).closest('td');
    const badge = controlElement.querySelector('.badge');
    const container = controlElement.querySelector('.border');

    // Actualizar texto y color del badge
    badge.textContent = nuevoEstado.charAt(0).toUpperCase() + nuevoEstado.slice(1);
    badge.className = 'badge ms-2';

    // Actualizar clase de contenedor según nuevo estado
    container.className = 'p-2 border rounded';
    if (nuevoEstado === 'activo') {
        badge.classList.add('bg-success');
        container.classList.add('bg-success', 'bg-opacity-10', 'border-success');
    } else if (nuevoEstado === 'bloqueado') {
        badge.classList.add('bg-danger');
        container.classList.add('bg-danger', 'bg-opacity-10', 'border-danger');
    } else if (nuevoEstado === 'vacio') {
        badge.classList.add('bg-secondary');
        container.classList.add('bg-light', 'border-secondary');
    } else {
        badge.classList.add('bg-warning');
        container.classList.add('bg-warning', 'bg-opacity-10', 'border-warning');
    }

    // Si el nuevo estado es "vacio", remover información del propietario
    if (nuevoEstado === 'vacio') {
        const propietarioInfo = controlElement.parentElement.querySelectorAll('.small');
        propietarioInfo.forEach(info => {
            if (info.textContent.includes('Disponible')) {
                info.innerHTML = '<i class="bi bi-dash-circle"></i> Disponible';
            } else {
                info.remove();
            }
        });
    }
}

// Función para mostrar notificaciones
function mostrarNotificacion(tipo, mensaje) {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Agregar al cuerpo del documento
    document.body.appendChild(notification);

    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (notification && notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Mantener compatibilidad con el modal existente para usos especiales
function abrirModalCambiarEstado(controlId, numeroControl, estadoActual) {
    // Esta función se mantiene por compatibilidad, pero ahora
    // se prefiere usar el desplegable directo
    console.log('Se recomienda usar el desplegable directo en lugar del modal');
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
