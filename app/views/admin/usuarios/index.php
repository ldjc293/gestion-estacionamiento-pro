<?php
$pageTitle = 'Gestión de Usuarios';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Usuarios', 'url' => '#']
];

require_once __DIR__ . '/../../layouts/header.php';
?>

<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../../layouts/alerts.php'; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-people"></i> Gestión de Usuarios
                </h6>
                <a href="<?= url('admin/crearUsuario') ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Crear Usuario
                </a>
            </div>
            <div class="card-body">
                <!-- Token CSRF oculto para JavaScript -->
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <!-- Filtros -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <input type="text"
                               name="buscar"
                               class="form-control form-control-sm"
                               placeholder="Buscar por nombre, cédula o email..."
                               value="<?= $_GET['buscar'] ?? '' ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="rol" class="form-select form-select-sm">
                            <option value="">Todos los roles</option>
                            <option value="cliente" <?= ($_GET['rol'] ?? '') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                            <option value="operador" <?= ($_GET['rol'] ?? '') === 'operador' ? 'selected' : '' ?>>Operador</option>
                            <option value="consultor" <?= ($_GET['rol'] ?? '') === 'consultor' ? 'selected' : '' ?>>Consultor</option>
                            <option value="administrador" <?= ($_GET['rol'] ?? '') === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="estado" class="form-select form-select-sm">
                            <option value="">Todos los estados</option>
                            <option value="activo" <?= ($_GET['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= ($_GET['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                    </div>
                    <div class="col-md-3 text-end">
                        <button type="button" onclick="exportarExcel()" class="btn btn-success btn-sm">
                            <i class="bi bi-file-excel"></i> Exportar
                        </button>
                    </div>
                </form>

                <!-- Estadísticas Rápidas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="value"><?= $estadisticas['total'] ?? 0 ?></div>
                            <div class="label">Total Usuarios</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="value"><?= $estadisticas['clientes'] ?? 0 ?></div>
                            <div class="label">Clientes</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                                <i class="bi bi-shield"></i>
                            </div>
                            <div class="value"><?= $estadisticas['operadores'] ?? 0 ?></div>
                            <div class="label">Operadores</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                                <i class="bi bi-x-circle"></i>
                            </div>
                            <div class="value"><?= $estadisticas['inactivos'] ?? 0 ?></div>
                            <div class="label">Inactivos</div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Usuarios -->
                <?php if (empty($usuarios)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 80px;"></i>
                        <h5 class="mt-3">No hay usuarios</h5>
                        <p class="text-muted">No se encontraron usuarios con los filtros seleccionados</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Contacto</th>
                                    <th>Rol</th>
                                    <th>Apartamento</th>
                                    <th>Controles</th>
                                    <th>Estado</th>
                                    <th>Último Acceso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?= $usuario->id ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($usuario->nombre_completo) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($usuario->cedula ?? 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <small>
                                                <i class="bi bi-envelope"></i> <?= htmlspecialchars($usuario->email) ?><br>
                                                <?php if ($usuario->telefono): ?>
                                                    <i class="bi bi-phone"></i> <?= htmlspecialchars($usuario->telefono) ?>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeColors = [
                                                'cliente' => 'primary',
                                                'operador' => 'warning',
                                                'consultor' => 'info',
                                                'administrador' => 'danger'
                                            ];
                                            $color = $badgeColors[$usuario->rol] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>">
                                                <?= ucfirst($usuario->rol) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (isset($usuario->apartamento) && $usuario->apartamento): ?>
                                                <span class="badge bg-secondary">
                                                    <?= $usuario->apartamento ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($usuario->controles)): ?>
                                                <?php
                                                // Filtrar controles que no sean NULL
                                                $controlesValidos = array_filter($usuario->controles, function($c) {
                                                    return !empty($c['numero_control']);
                                                });
                                                ?>
                                                <?php if (empty($controlesValidos)): ?>
                                                    <span class="text-muted">Sin controles</span>
                                                <?php else: ?>
                                                    <div class="d-flex flex-column gap-1">
                                                        <?php foreach ($controlesValidos as $control): ?>
                                                            <small class="d-flex align-items-center gap-1">
                                                                <i class="bi bi-controller"></i>
                                                                <strong>#<?= htmlspecialchars($control['numero_control']) ?></strong>
                                                                <?php if ($control['estado'] === 'activo'): ?>
                                                                    <span class="badge bg-success" style="font-size: 0.65rem;">Activo</span>
                                                                <?php elseif ($control['estado'] === 'suspendido'): ?>
                                                                    <span class="badge bg-warning" style="font-size: 0.65rem;">Suspendido</span>
                                                                <?php elseif ($control['estado'] === 'perdido'): ?>
                                                                    <span class="badge bg-danger" style="font-size: 0.65rem;">Perdido</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary" style="font-size: 0.65rem;"><?= ucfirst($control['estado']) ?></span>
                                                                <?php endif; ?>
                                                            </small>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($usuario->activo): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($usuario->ultimo_acceso): ?>
                                                <small><?= date('d/m/Y H:i', strtotime($usuario->ultimo_acceso)) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Nunca</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('admin/editarUsuario?id=' . $usuario->id) ?>"
                                                   class="btn btn-outline-primary"
                                                   title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button onclick="cambiarRol(<?= $usuario->id ?>, '<?= $usuario->rol ?>')"
                                                        class="btn btn-outline-info"
                                                        title="Cambiar Rol">
                                                    <i class="bi bi-person-badge"></i>
                                                </button>
                                                <?php if ($usuario->activo): ?>
                                                    <button onclick="toggleEstado(<?= $usuario->id ?>, 'desactivar')"
                                                            class="btn btn-outline-danger"
                                                            title="Desactivar">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="toggleEstado(<?= $usuario->id ?>, 'activar')"
                                                            class="btn btn-outline-success"
                                                            title="Activar">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="eliminarUsuario(<?= $usuario->id ?>, '<?= htmlspecialchars($usuario->nombre_completo, ENT_QUOTES) ?>')"
                                                        class="btn btn-outline-danger"
                                                        title="Eliminar Usuario">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <?php if (isset($paginacion) && $paginacion['total_paginas'] > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($paginacion['pagina_actual'] > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?pagina=<?= $paginacion['pagina_actual'] - 1 ?>">Anterior</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $paginacion['total_paginas']; $i++): ?>
                                    <li class="page-item <?= $i === $paginacion['pagina_actual'] ? 'active' : '' ?>">
                                        <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($paginacion['pagina_actual'] < $paginacion['total_paginas']): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?pagina=<?= $paginacion['pagina_actual'] + 1 ?>">Siguiente</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<JS
<script>
function toggleEstado(usuarioId, accion) {
    const mensaje = accion === 'activar' ? '¿Activar este usuario?' : '¿Desactivar este usuario?';

    if (confirm(mensaje)) {
        fetch(URL_BASE + '/admin/usuarios/toggle-estado', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: document.querySelector('[name="csrf_token"]').value,
                usuario_id: usuarioId,
                accion: accion
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error al cambiar el estado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
        });
    }
}

function cambiarRol(usuarioId, rolActual) {
    const roles = ['cliente', 'operador', 'consultor', 'administrador'];
    const nombresRoles = {
        'cliente': 'Cliente',
        'operador': 'Operador',
        'consultor': 'Consultor',
        'administrador': 'Administrador'
    };

    // Crear opciones para el select
    let opciones = '';
    roles.forEach(rol => {
        const selected = rol === rolActual ? 'selected' : '';
        opciones += '<option value="' + rol + '" ' + selected + '>' + nombresRoles[rol] + '</option>';
    });

    const csrfToken = document.querySelector('[name="csrf_token"]').value;

    const modalHtml = '' +
        '<div class="modal fade" id="modalCambiarRol" tabindex="-1">' +
            '<div class="modal-dialog">' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<h5 class="modal-title">Cambiar Rol de Usuario</h5>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
                    '</div>' +
                    '<div class="modal-body">' +
                        '<form id="formCambiarRol">' +
                            '<input type="hidden" name="csrf_token" value="' + csrfToken + '">' +
                            '<input type="hidden" name="usuario_id" value="' + usuarioId + '">' +
                            '<div class="mb-3">' +
                                '<label class="form-label">Nuevo Rol</label>' +
                                '<select class="form-select" name="nuevo_rol" required>' +
                                    opciones +
                                '</select>' +
                            '</div>' +
                        '</form>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                        '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>' +
                        '<button type="button" class="btn btn-primary" id="btnGuardarRol">Guardar Cambios</button>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';

    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalCambiarRol'));
    modal.show();

    // Agregar event listener al botón después de crear el modal
    document.getElementById('btnGuardarRol').addEventListener('click', guardarCambioRol);

    // Limpiar modal cuando se cierre
    document.getElementById('modalCambiarRol').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

function guardarCambioRol() {
    console.log('guardarCambioRol called');
    const form = document.getElementById('formCambiarRol');
    console.log('Form:', form);
    const formData = new FormData(form);

    // Convertir FormData a objeto para enviar como JSON
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    console.log('Data to send:', data);

    fetch(URL_BASE + '/admin/cambiar-rol', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Cerrar modal
            bootstrap.Modal.getInstance(document.getElementById('modalCambiarRol')).hide();
            // Recargar página
            location.reload();
        } else {
            alert(data.message || 'Error al cambiar el rol');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}

function eliminarUsuario(usuarioId, nombreUsuario) {
    if (confirm('¿Está seguro de que desea eliminar al usuario "' + nombreUsuario + '"?\\n\\nEsta acción NO se puede deshacer.')) {
        const csrfToken = document.querySelector('[name="csrf_token"]').value;
        
        fetch(URL_BASE + '/admin/eliminar-usuario', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: csrfToken,
                usuario_id: usuarioId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error al eliminar el usuario');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
        });
    }
}

function exportarExcel() {
    const params = new URLSearchParams(window.location.search);
    params.append('formato', 'excel');
    window.location.href = URL_BASE + '/admin/usuarios/export?' + params.toString();
}
</script>
JS;
?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
