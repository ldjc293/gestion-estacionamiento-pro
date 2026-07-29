<?php
$pageTitle = 'Editar Usuario';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Usuarios', 'url' => url('admin/usuarios')],
    ['label' => 'Editar', 'url' => '#']
];

require_once __DIR__ . '/../../layouts/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../../layouts/alerts.php'; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-pencil"></i> Editar Usuario #<?= $usuario->id ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="<?= url('admin/usuarios/process-editar') ?>" method="POST" id="formEditarUsuario">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="usuario_id" value="<?= $usuario->id ?>">

                            <!-- Datos Personales -->
                            <h6 class="mb-3 fw-bold">Datos Personales</h6>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Nombre Completo *</label>
                                    <input type="text"
                                           class="form-control"
                                           name="nombre_completo"
                                           value="<?= htmlspecialchars($usuario->nombre_completo) ?>"
                                           required
                                           maxlength="100">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Cédula</label>
                                    <div class="input-group">
                                        <?php
                                        // Separar el tipo y número de cédula
                                        $cedulaTipo = '';
                                        $cedulaNumero = '';
                                        if (!empty($usuario->cedula)) {
                                            $partes = explode('-', $usuario->cedula);
                                            if (count($partes) === 2) {
                                                $cedulaTipo = $partes[0];
                                                $cedulaNumero = $partes[1];
                                            }
                                        }
                                        ?>
                                        <select class="form-select" name="cedula_tipo" id="cedulaTipo" style="max-width: 80px;">
                                            <option value="">-</option>
                                            <option value="V" <?= $cedulaTipo === 'V' ? 'selected' : '' ?>>V</option>
                                            <option value="E" <?= $cedulaTipo === 'E' ? 'selected' : '' ?>>E</option>
                                            <option value="J" <?= $cedulaTipo === 'J' ? 'selected' : '' ?>>J</option>
                                        </select>
                                        <input type="text"
                                               class="form-control"
                                               name="cedula_numero"
                                               id="cedulaNumero"
                                               value="<?= htmlspecialchars($cedulaNumero) ?>"
                                               placeholder="12345678"
                                               pattern="\d{6,8}"
                                               maxlength="8">
                                    </div>
                                    <small class="text-muted">Ingrese solo números (6 a 8 dígitos) - Opcional</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Email *</label>
                                    <input type="email"
                                           class="form-control"
                                           name="email"
                                           value="<?= htmlspecialchars($usuario->email) ?>"
                                           required
                                           maxlength="100">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Teléfono</label>
                                    <input type="tel"
                                           class="form-control"
                                           name="telefono"
                                           value="<?= htmlspecialchars($usuario->telefono ?? '') ?>"
                                           maxlength="20">
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Datos de Cuenta -->
                            <h6 class="mb-3 fw-bold">Datos de Cuenta</h6>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Rol *</label>
                                    <select class="form-select" name="rol" id="selectRol" required>
                                        <option value="cliente" <?= $usuario->rol === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                                        <option value="operador" <?= $usuario->rol === 'operador' ? 'selected' : '' ?>>Operador</option>
                                        <option value="consultor" <?= $usuario->rol === 'consultor' ? 'selected' : '' ?>>Consultor</option>
                                        <option value="administrador" <?= $usuario->rol === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Estado *</label>
                                    <select class="form-select" name="activo" required>
                                        <option value="1" <?= $usuario->activo ? 'selected' : '' ?>>Activo</option>
                                        <option value="0" <?= !$usuario->activo ? 'selected' : '' ?>>Inactivo</option>
                                    </select>
                                </div>
                            </div>

                            <?php if ($usuario->rol === 'cliente' && isset($apartamento) && $apartamento): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-building"></i>
                                    <strong>Apartamento:</strong>
                                    <?php if (is_array($apartamento)): ?>
                                        <?= htmlspecialchars($apartamento['bloque'] ?? 'N/A') ?>-<?= htmlspecialchars($apartamento['escalera'] ?? 'N/A') ?>-<?= htmlspecialchars($apartamento['piso'] ?? 'N/A') ?>-<?= htmlspecialchars($apartamento['numero_apartamento'] ?? 'N/A') ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($apartamento->bloque ?? 'N/A') ?>-<?= htmlspecialchars($apartamento->escalera ?? 'N/A') ?>-<?= htmlspecialchars($apartamento->piso ?? 'N/A') ?>-<?= htmlspecialchars($apartamento->numero_apartamento ?? 'N/A') ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <hr class="my-4">

                            <!-- Cambiar Contraseña (Opcional) -->
                            <h6 class="mb-3 fw-bold">Cambiar Contraseña (Opcional)</h6>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Nueva Contraseña</label>
                                    <div class="input-group">
                                        <input type="password"
                                               class="form-control"
                                               name="password"
                                               id="password"
                                               minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                            <i class="bi bi-eye" id="icon_password"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Dejar en blanco para no cambiar</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Confirmar Contraseña</label>
                                    <div class="input-group">
                                        <input type="password"
                                               class="form-control"
                                               name="password_confirm"
                                               id="password_confirm"
                                               minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirm')">
                                            <i class="bi bi-eye" id="icon_password_confirm"></i>
                                        </button>
                                    </div>
                                    <small id="passwordMatch" class="text-muted"></small>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="cambiar_password_siguiente" id="cambiarPassword" value="1">
                                <label class="form-check-label" for="cambiarPassword">
                                    Requerir cambio de contraseña en el próximo inicio de sesión
                                </label>
                            </div>

                            <!-- Botones -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="btnSubmit">
                                    <i class="bi bi-check-circle"></i> Guardar Cambios
                                </button>
                                <a href="<?= url('admin/usuarios') ?>" class="btn btn-outline-secondary">
                                    Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Información -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle"></i> Información
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0" style="font-size: 14px;">
                            <li class="mb-2">
                                <strong>ID:</strong> <?= $usuario->id ?>
                            </li>
                            <li class="mb-2">
                                <strong>Fecha de registro:</strong><br>
                                <?= ($usuario->fecha_registro ?? false) ? date('d/m/Y H:i', strtotime($usuario->fecha_registro)) : 'No disponible' ?>
                            </li>
                            <li class="mb-2">
                                <strong>Último acceso:</strong><br>
                                <?= ($usuario->ultimo_acceso ?? false) ? date('d/m/Y H:i', strtotime($usuario->ultimo_acceso)) : 'Nunca' ?>
                            </li>
                            <?php if ($usuario->intentos_fallidos > 0): ?>
                                <li class="mb-0">
                                    <strong class="text-danger">Intentos fallidos:</strong>
                                    <?= $usuario->intentos_fallidos ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <?php if ($usuario->rol === 'cliente'): ?>
                    <!-- Estadísticas del Cliente -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-graph-up"></i> Estadísticas
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0" style="font-size: 14px;">
                                <li class="mb-2">
                                    <strong>Controles asignados:</strong> <?= $estadisticas['controles'] ?? 0 ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Pagos realizados:</strong> <?= $estadisticas['pagos'] ?? 0 ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Mensualidades vencidas:</strong>
                                    <span class="badge bg-<?= ($estadisticas['vencidas'] ?? 0) > 0 ? 'danger' : 'success' ?>">
                                        <?= $estadisticas['vencidas'] ?? 0 ?>
                                    </span>
                                </li>
                                <li class="mb-0">
                                    <strong>Deuda total:</strong>
                                    <span class="<?= ($estadisticas['deuda'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= formatUSD($estadisticas['deuda'] ?? 0) ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Cargar / Revertir Deuda Histórica -->
                    <div class="card mb-4 border-warning">
                        <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-dark">
                                <i class="bi bi-clock-history text-warning me-2"></i> Deuda Histórica
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">
                                Gestiona o corrige mensualidades no canceladas para reflejar la deuda acumulada real del residente.
                            </p>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-warning btn-sm fw-semibold text-dark" onclick="abrirModalDeudaHistorica(<?= $usuario->id ?>, '<?= htmlspecialchars($usuario->nombre_completo, ENT_QUOTES) ?>')">
                                    <i class="bi bi-calendar-plus me-1"></i> Cargar Deuda Histórica
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm fw-semibold" onclick="abrirModalRevertirDeuda(<?= $usuario->id ?>, '<?= htmlspecialchars($usuario->nombre_completo, ENT_QUOTES) ?>')">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Revertir / Limpiar Deuda Errónea
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Gestión de Controles de Estacionamiento -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="bi bi-controller"></i> Gestión de Controles
                            </h6>
                            <span class="badge bg-info"><?= count($controles ?? []) ?> controles</span>
                        </div>
                        <div class="card-body">
                            <!-- Controles Actuales -->
                            <?php if (isset($controles) && !empty($controles)): ?>
                                <div class="mb-3">
                                    <h6 class="text-muted mb-2">Controles Asignados</h6>
                                    <div class="list-group mb-3">
                                        <?php foreach ($controles as $control): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                <div>
                                                    <strong class="small"><?= htmlspecialchars($control['numero_control_completo']) ?></strong>
                                                    <?php
                                                    $estadoBadge = [
                                                        'activo' => 'success',
                                                        'suspendido' => 'warning',
                                                        'desactivado' => 'secondary',
                                                        'perdido' => 'danger',
                                                        'bloqueado' => 'dark',
                                                        'vacio' => 'light'
                                                    ];
                                                    $colorBadge = $estadoBadge[$control['estado']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $colorBadge ?> ms-2 small"
                                                          style="cursor: pointer;"
                                                          title="Haga clic para cambiar estatus"
                                                          onclick="abrirModalCambiarEstatusControl(<?= $control['id'] ?>, '<?= htmlspecialchars($control['numero_control_completo']) ?>', '<?= $control['estado'] ?>')">
                                                        <?= ucfirst($control['estado']) ?> <i class="bi bi-pencil-square ms-1" style="font-size: 10px;"></i>
                                                    </span>
                                                    <?php if ($control['fecha_asignacion']): ?>
                                                        <br><small class="text-muted">
                                                            Asignado: <?= date('d/m/Y', strtotime($control['fecha_asignacion'])) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                            title="Cambiar Estatus"
                                                            onclick="abrirModalCambiarEstatusControl(<?= $control['id'] ?>, '<?= htmlspecialchars($control['numero_control_completo']) ?>', '<?= $control['estado'] ?>')">
                                                        <i class="bi bi-gear"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                            title="Remover Control"
                                                            onclick="removerControl(<?= $control['id'] ?>, '<?= htmlspecialchars($control['numero_control_completo']) ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Asignar Nuevo Control -->
                            <?php if (isset($controlesDisponibles) && !empty($controlesDisponibles)): ?>
                                <div class="border-top pt-3">
                                    <h6 class="text-muted mb-2">Asignar Nuevo Control</h6>
                                    <form method="POST" action="<?= url('admin/asignar-control-usuario') ?>" class="d-flex gap-2">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="usuario_id" value="<?= $usuario->id ?>">
                                        <select class="form-select form-select-sm" name="control_id" required>
                                            <option value="">Seleccionar control...</option>
                                            <?php foreach ($controlesDisponibles as $control): ?>
                                                <option value="<?= $control['id'] ?>">
                                                    <?= htmlspecialchars($control['numero_control_completo']) ?> (Pos <?= $control['posicion_numero'] ?>, Rec <?= $control['receptor'] ?><?= $control['estado'] !== 'vacio' ? ' - ' . ucfirst($control['estado']) : '' ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="bi bi-plus-circle"></i> Asignar
                                        </button>
                                    </form>
                                </div>
                            <?php elseif (isset($controlesDisponibles)): ?>
                                <div class="border-top pt-3 text-center">
                                    <small class="text-muted">No hay controles disponibles para asignar</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
ob_start();
?>
<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById('icon_' + fieldId);

    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Check password match
document.getElementById('password_confirm')?.addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirm = this.value;
    const matchText = document.getElementById('passwordMatch');

    if (confirm.length === 0 && password.length === 0) {
        matchText.textContent = '';
        return;
    }

    if (password === confirm) {
        matchText.textContent = '✓ Las contraseñas coinciden';
        matchText.className = 'text-success';
    } else {
        matchText.textContent = '✗ Las contraseñas no coinciden';
        matchText.className = 'text-danger';
    }
});

// Validar cédula - solo números
const cedulaNumero = document.getElementById('cedulaNumero');
if (cedulaNumero) {
    cedulaNumero.addEventListener('input', function() {
        // Solo permitir números
        this.value = this.value.replace(/[^\d]/g, '');
    });
}

// Form validation
document.getElementById('formEditarUsuario')?.addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirm').value;

    // Solo validar si se ingresó una contraseña
    if (password.length > 0 || confirm.length > 0) {
        if (password !== confirm) {
            e.preventDefault();
            alert('Las contraseñas no coinciden');
            return false;
        }
    }

    const btn = document.getElementById('btnSubmit');
    setButtonLoading(btn, true);
});

// Función para remover control con SweetAlert2
function removerControl(controlId, controlNumero) {
    Swal.fire({
        title: '¿Remover Control #' + controlNumero + '?',
        text: 'El control se desvinculará de este residente y pasará a estar Vacío.',
        icon: 'warning',
        input: 'text',
        inputPlaceholder: 'Indique el motivo de la remoción...',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, remover control',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Debe ingresar un motivo para desincorporar el control';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url('admin/remover-control-usuario') ?>';
            form.style.display = 'none';

            const fields = {
                'csrf_token': '<?= generateCSRFToken() ?>',
                'usuario_id': '<?= $usuario->id ?>',
                'control_id': controlId,
                'motivo': result.value.trim()
            };

            for (const [name, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Función para cambiar estatus de un control con SweetAlert2
function abrirModalCambiarEstatusControl(controlId, controlNumero, estadoActual) {
    const htmlSelect = `
        <div class="text-start">
            <label class="form-label fw-bold small mb-1">Nuevo Estatus:</label>
            <select id="swal_nuevo_estado" class="form-select form-select-sm mb-3">
                <option value="activo" ${estadoActual === 'activo' ? 'selected' : ''}>Activo</option>
                <option value="suspendido" ${estadoActual === 'suspendido' ? 'selected' : ''}>Suspendido</option>
                <option value="desactivado" ${estadoActual === 'desactivado' ? 'selected' : ''}>Desactivado</option>
                <option value="perdido" ${estadoActual === 'perdido' ? 'selected' : ''}>Perdido (Reportado)</option>
                <option value="bloqueado" ${estadoActual === 'bloqueado' ? 'selected' : ''}>Bloqueado</option>
                <option value="vacio" ${estadoActual === 'vacio' ? 'selected' : ''}>Vacío (Desincorporar)</option>
            </select>
            <label class="form-label fw-bold small mb-1">Motivo del Cambio:</label>
            <input type="text" id="swal_motivo_estado" class="form-control form-control-sm" placeholder="Indique el motivo del cambio de estatus...">
        </div>
    `;

    Swal.fire({
        title: 'Cambiar Estatus Control #' + controlNumero,
        html: htmlSelect,
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Guardar Estatus',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const estado = document.getElementById('swal_nuevo_estado').value;
            const motivo = document.getElementById('swal_motivo_estado').value;
            if (!motivo || !motivo.trim()) {
                Swal.showValidationMessage('Debe ingresar un motivo para el cambio de estatus');
                return false;
            }
            return { estado: estado, motivo: motivo.trim() };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Actualizando estatus...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData();
            formData.append('csrf_token', '<?= generateCSRFToken() ?>');
            formData.append('control_id', controlId);
            formData.append('estado', result.value.estado);
            formData.append('motivo', result.value.motivo);

            fetch('<?= url('admin/cambiarEstadoControl') ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Éxito!', data.message || 'Estatus actualizado correctamente', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Atención', data.message || 'No se pudo cambiar el estatus', 'warning');
                }
            })
            .catch(err => {
                console.error(err);
                location.reload();
            });
        }
    });
}

function abrirModalDeudaHistorica(usuarioId, nombreUsuario) {
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
                    <select id="swalAnioInicio" class="form-select">${opcionesAnio}</select>
                </div>
                <div class="mb-3">
                    <label class="form-label font-semibold small">Mes de inicio de deuda:</label>
                    <select id="swalMesInicio" class="form-select">${opcionesMes}</select>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-check-circle me-1"></i> Generar Deuda',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#f59e0b',
        preConfirm: () => {
            const anio = document.getElementById('swalAnioInicio').value;
            const mes = document.getElementById('swalMesInicio').value;
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

            fetch('<?= url("admin/cargar-deuda-historica") ?>', {
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

function abrirModalRevertirDeuda(usuarioId, nombreUsuario) {
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
        title: 'Revertir / Limpiar Deuda Errónea',
        html: `
            <div class="text-start mb-3">
                <div class="alert alert-warning p-2 small mb-3">
                    <i class="bi bi-shield-exclamation me-1"></i>
                    Esta acción eliminará las mensualidades no pagadas de <strong>${nombreUsuario}</strong> en el rango indicado. Si existen mensualidades con pagos aprobados, se conservarán intactas.
                </div>
                <div class="row g-2">
                    <div class="col-6 mb-2">
                        <label class="form-label font-semibold small">Año Inicio:</label>
                        <select id="swalRevAnioInicio" class="form-select form-select-sm">${opcionesAnio}</select>
                    </div>
                    <div class="col-6 mb-2">
                        <label class="form-label font-semibold small">Mes Inicio:</label>
                        <select id="swalRevMesInicio" class="form-select form-select-sm">${opcionesMes}</select>
                    </div>
                    <div class="col-6 mb-2">
                        <label class="form-label font-semibold small">Año Fin (opcional):</label>
                        <select id="swalRevAnioFin" class="form-select form-select-sm"><option value="">Hasta la fecha</option>${opcionesAnio}</select>
                    </div>
                    <div class="col-6 mb-2">
                        <label class="form-label font-semibold small">Mes Fin (opcional):</label>
                        <select id="swalRevMesFin" class="form-select form-select-sm"><option value="">Hasta la fecha</option>${opcionesMes}</select>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-trash me-1"></i> Revertir Deuda',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        preConfirm: () => {
            const anioInicio = document.getElementById('swalRevAnioInicio').value;
            const mesInicio = document.getElementById('swalRevMesInicio').value;
            const anioFin = document.getElementById('swalRevAnioFin').value;
            const mesFin = document.getElementById('swalRevMesFin').value;
            if (!anioInicio || !mesInicio) {
                Swal.showValidationMessage('Selecciona año y mes de inicio válidos');
                return false;
            }
            return { anioInicio, mesInicio, anioFin, mesFin };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Eliminando mensualidades erróneas...',
                text: 'Procesando reversión de deuda',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData();
            formData.append('usuario_id', usuarioId);
            formData.append('anio_inicio', result.value.anioInicio);
            formData.append('mes_inicio', result.value.mesInicio);
            if (result.value.anioFin) formData.append('anio_fin', result.value.anioFin);
            if (result.value.mesFin) formData.append('mes_fin', result.value.mesFin);
            formData.append('csrf_token', '<?= generateCSRFToken() ?>');

            fetch('<?= url("admin/revertir-deuda-historica") ?>', {
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
        }
    });
}
</script>
<?php
$additionalJS = ob_get_clean();
require_once __DIR__ . '/../../layouts/footer.php';
?>
