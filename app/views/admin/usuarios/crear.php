<?php
$pageTitle = 'Crear Usuario';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Usuarios', 'url' => url('admin/usuarios')],
    ['label' => 'Crear', 'url' => '#']
];

require_once __DIR__ . '/../../layouts/header.php';
?>

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
                            <i class="bi bi-person-plus"></i> Crear Nuevo Usuario
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="<?= url('admin/processCrearUsuario') ?>" method="POST" id="formCrearUsuario">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                            <!-- Datos Personales -->
                            <h6 class="mb-3 fw-bold">Datos Personales</h6>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Nombre Completo *</label>
                                    <input type="text"
                                           class="form-control"
                                           name="nombre_completo"
                                           required
                                           maxlength="100">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Cédula</label>
                                    <div class="input-group">
                                        <select class="form-select" name="cedula_tipo" id="cedulaTipoCrear" style="max-width: 80px;">
                                            <option value="">-</option>
                                            <option value="V">V</option>
                                            <option value="E">E</option>
                                            <option value="J">J</option>
                                        </select>
                                        <input type="text"
                                               class="form-control"
                                               name="cedula_numero"
                                               id="cedulaNumeroCrear"
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
                                           required
                                           maxlength="100">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Teléfono</label>
                                    <input type="tel"
                                           class="form-control"
                                           name="telefono"
                                           placeholder="0414-1234567"
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
                                        <option value="">Seleccione...</option>
                                        <option value="cliente">Cliente</option>
                                        <option value="operador">Operador</option>
                                        <option value="consultor">Consultor</option>
                                        <option value="administrador">Administrador</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Estado *</label>
                                    <select class="form-select" name="activo" required>
                                        <option value="1">Activo</option>
                                        <option value="0">Inactivo</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Solo para Clientes -->
                            <div id="seccionApartamento" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Torre *</label>
                                        <select class="form-select" name="torre" id="selectTorre">
                                            <option value="">Seleccione...</option>
                                            <?php for ($t = 27; $t <= 32; $t++): ?>
                                                <option value="<?= $t ?>">Torre <?= $t ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Apartamento *</label>
                                        <input type="text"
                                               class="form-control"
                                               name="numero_apartamento"
                                               id="inputApartamento"
                                               placeholder="Ej: 101, 202, 303">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Contraseña *</label>
                                    <div class="input-group">
                                        <input type="password"
                                               class="form-control"
                                               name="password"
                                               id="password"
                                               required
                                               minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                            <i class="bi bi-eye" id="icon_password"></i>
                                        </button>
                                    </div>
                                    <div class="progress mt-1" style="height: 5px;">
                                        <div class="progress-bar" id="passwordStrength" style="width: 0%"></div>
                                    </div>
                                    <small id="passwordFeedback" class="text-muted"></small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Confirmar Contraseña *</label>
                                    <div class="input-group">
                                        <input type="password"
                                               class="form-control"
                                               name="password_confirm"
                                               id="password_confirm"
                                               required
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
                                    Requerir cambio de contraseña en el primer inicio de sesión
                                </label>
                            </div>

                            <!-- Botones -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="btnSubmit">
                                    <i class="bi bi-check-circle"></i> Crear Usuario
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
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle"></i> Requisitos de Contraseña
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0" style="font-size: 14px;">
                            <li class="mb-2">Mínimo 8 caracteres</li>
                            <li class="mb-2">Al menos una letra mayúscula</li>
                            <li class="mb-2">Al menos una letra minúscula</li>
                            <li class="mb-2">Al menos un número</li>
                            <li class="mb-0">Al menos un carácter especial (@$!%*?&)</li>
                        </ul>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-shield-check"></i> Roles del Sistema
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Cliente:</strong>
                            <p class="mb-0 small text-muted">Puede registrar pagos, ver estado de cuenta y controles</p>
                        </div>
                        <div class="mb-3">
                            <strong>Operador:</strong>
                            <p class="mb-0 small text-muted">Puede aprobar/rechazar pagos y registrar pagos presenciales</p>
                        </div>
                        <div class="mb-3">
                            <strong>Consultor:</strong>
                            <p class="mb-0 small text-muted">Puede ver todos los reportes del sistema</p>
                        </div>
                        <div class="mb-0">
                            <strong>Administrador:</strong>
                            <p class="mb-0 small text-muted">Acceso completo al sistema</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<JS
<script>
// Show/hide apartment section based on role
document.getElementById('selectRol').addEventListener('change', function() {
    const seccion = document.getElementById('seccionApartamento');
    const torre = document.getElementById('selectTorre');
    const apto = document.getElementById('inputApartamento');

    if (this.value === 'cliente') {
        seccion.style.display = 'block';
        torre.required = true;
        apto.required = true;
    } else {
        seccion.style.display = 'none';
        torre.required = false;
        apto.required = false;
    }
});

// Toggle password visibility
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

// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    const feedback = document.getElementById('passwordFeedback');

    let strength = 0;
    let messages = [];

    if (password.length >= 8) strength += 20;
    else messages.push('mínimo 8 caracteres');

    if (/[a-z]/.test(password)) strength += 20;
    else messages.push('una minúscula');

    if (/[A-Z]/.test(password)) strength += 20;
    else messages.push('una mayúscula');

    if (/[0-9]/.test(password)) strength += 20;
    else messages.push('un número');

    if (/[@$!%*?&]/.test(password)) strength += 20;
    else messages.push('un carácter especial');

    strengthBar.style.width = strength + '%';

    if (strength < 40) {
        strengthBar.className = 'progress-bar bg-danger';
        feedback.textContent = 'Débil - Falta: ' + messages.join(', ');
        feedback.className = 'text-danger';
    } else if (strength < 80) {
        strengthBar.className = 'progress-bar bg-warning';
        feedback.textContent = 'Media - Falta: ' + messages.join(', ');
        feedback.className = 'text-warning';
    } else {
        strengthBar.className = 'progress-bar bg-success';
        feedback.textContent = 'Fuerte';
        feedback.className = 'text-success';
    }
});

// Check password match
document.getElementById('password_confirm').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirm = this.value;
    const matchText = document.getElementById('passwordMatch');

    if (confirm.length === 0) {
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
const cedulaNumeroCrear = document.getElementById('cedulaNumeroCrear');
if (cedulaNumeroCrear) {
    cedulaNumeroCrear.addEventListener('input', function() {
        // Solo permitir números
        this.value = this.value.replace(/[^\d]/g, '');
    });
}

// Form validation
document.getElementById('formCrearUsuario').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirm').value;

    if (password !== confirm) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }

    const btn = document.getElementById('btnSubmit');
    setButtonLoading(btn, true);
});
</script>
JS;
?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
