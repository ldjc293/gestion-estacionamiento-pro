<?php
$pageTitle = 'Cambiar Contraseña';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('cliente/dashboard')],
    ['label' => 'Cambiar Contraseña', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-shield-lock"></i> Cambiar Contraseña
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Requisitos de seguridad:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Mínimo 8 caracteres</li>
                                <li>Al menos una letra mayúscula</li>
                                <li>Al menos una letra minúscula</li>
                                <li>Al menos un número</li>
                                <li>Al menos un carácter especial (@$!%*?&)</li>
                            </ul>
                        </div>

                        <form action="<?= url('cliente/process-cambiar-password') ?>" method="POST" id="formCambiarPassword">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                            <!-- Contraseña Actual -->
                            <div class="mb-3">
                                <label for="password_actual" class="form-label fw-bold">
                                    <i class="bi bi-lock"></i> Contraseña Actual *
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control"
                                           id="password_actual"
                                           name="password_actual"
                                           required
                                           autofocus>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_actual')">
                                        <i class="bi bi-eye" id="icon_password_actual"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Nueva Contraseña -->
                            <div class="mb-3">
                                <label for="password_nueva" class="form-label fw-bold">
                                    <i class="bi bi-key"></i> Nueva Contraseña *
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control"
                                           id="password_nueva"
                                           name="password_nueva"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_nueva')">
                                        <i class="bi bi-eye" id="icon_password_nueva"></i>
                                    </button>
                                </div>
                                <!-- Indicador de fortaleza -->
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="passwordFeedback" class="text-muted"></small>
                            </div>

                            <!-- Confirmar Nueva Contraseña -->
                            <div class="mb-4">
                                <label for="password_confirmar" class="form-label fw-bold">
                                    <i class="bi bi-key-fill"></i> Confirmar Nueva Contraseña *
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control"
                                           id="password_confirmar"
                                           name="password_confirmar"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirmar')">
                                        <i class="bi bi-eye" id="icon_password_confirmar"></i>
                                    </button>
                                </div>
                                <small id="passwordMatch" class="text-muted"></small>
                            </div>

                            <!-- Botones -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="btnSubmit">
                                    <i class="bi bi-check-circle"></i> Cambiar Contraseña
                                </button>
                                <a href="<?= url('cliente/dashboard') ?>" class="btn btn-outline-secondary">
                                    Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Consejos de Seguridad -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-shield-check"></i> Consejos de Seguridad
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0" style="font-size: 14px;">
                            <li class="mb-2">No uses contraseñas obvias o fáciles de adivinar</li>
                            <li class="mb-2">No uses la misma contraseña en múltiples sitios</li>
                            <li class="mb-2">Cambia tu contraseña periódicamente</li>
                            <li class="mb-2">No compartas tu contraseña con nadie</li>
                            <li class="mb-0">Usa una combinación única de letras, números y símbolos</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<JS
<script>
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

// Check password strength
document.getElementById('password_nueva').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    const feedback = document.getElementById('passwordFeedback');

    let strength = 0;
    let messages = [];

    // Length
    if (password.length >= 8) strength += 20;
    else messages.push('mínimo 8 caracteres');

    // Lowercase
    if (/[a-z]/.test(password)) strength += 20;
    else messages.push('una minúscula');

    // Uppercase
    if (/[A-Z]/.test(password)) strength += 20;
    else messages.push('una mayúscula');

    // Numbers
    if (/[0-9]/.test(password)) strength += 20;
    else messages.push('un número');

    // Special chars
    if (/[@$!%*?&]/.test(password)) strength += 20;
    else messages.push('un carácter especial');

    // Update progress bar
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
        feedback.textContent = 'Fuerte - Cumple todos los requisitos';
        feedback.className = 'text-success';
    }
});

// Check password match
document.getElementById('password_confirmar').addEventListener('input', function() {
    const nueva = document.getElementById('password_nueva').value;
    const confirmar = this.value;
    const matchText = document.getElementById('passwordMatch');

    if (confirmar.length === 0) {
        matchText.textContent = '';
        return;
    }

    if (nueva === confirmar) {
        matchText.textContent = '✓ Las contraseñas coinciden';
        matchText.className = 'text-success';
    } else {
        matchText.textContent = '✗ Las contraseñas no coinciden';
        matchText.className = 'text-danger';
    }
});

// Form validation
document.getElementById('formCambiarPassword').addEventListener('submit', function(e) {
    const nueva = document.getElementById('password_nueva').value;
    const confirmar = document.getElementById('password_confirmar').value;

    if (nueva !== confirmar) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }

    // Check password requirements
    if (nueva.length < 8 || !/[a-z]/.test(nueva) || !/[A-Z]/.test(nueva) ||
        !/[0-9]/.test(nueva) || !/[@$!%*?&]/.test(nueva)) {
        e.preventDefault();
        alert('La contraseña no cumple con los requisitos de seguridad');
        return false;
    }

    const btn = document.getElementById('btnSubmit');
    setButtonLoading(btn, true);
});
</script>
JS;
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
