<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio de Contraseña Obligatorio - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .change-container {
            width: 100%;
            max-width: 550px;
            padding: 20px;
        }

        .change-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .change-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .change-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--warning-color), #d97706);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
        }

        .change-header .icon i {
            font-size: 36px;
            color: white;
        }

        .change-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .change-header p {
            color: var(--secondary-color);
            font-size: 14px;
            margin: 0;
        }

        .welcome-box {
            background: #fffbeb;
            border-left: 4px solid var(--warning-color);
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .welcome-box h5 {
            font-size: 16px;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 8px;
        }

        .welcome-box p {
            font-size: 14px;
            color: #78350f;
            margin: 0;
            line-height: 1.6;
        }

        .form-floating {
            margin-bottom: 16px;
            position: relative;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            padding-right: 45px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .form-control.is-valid {
            border-color: var(--success-color);
        }

        .form-control.is-invalid {
            border-color: var(--danger-color);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--secondary-color);
            z-index: 10;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .requirements {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .requirements h6 {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 12px;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            font-size: 14px;
            color: var(--secondary-color);
            transition: all 0.3s ease;
        }

        .requirement-item i {
            font-size: 16px;
        }

        .requirement-item.valid {
            color: var(--success-color);
        }

        .requirement-item.valid i:before {
            content: "\f26b";
        }

        .requirement-item.invalid i:before {
            content: "\f28a";
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid var(--danger-color);
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }

        .user-info {
            background: #f8fafc;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-info i {
            font-size: 24px;
            color: var(--primary-color);
        }

        .user-info div h6 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }

        .user-info div p {
            margin: 0;
            font-size: 13px;
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="change-container">
        <div class="change-card">
            <div class="change-header">
                <div class="icon">
                    <i class="bi bi-shield-exclamation"></i>
                </div>
                <h1>Cambio de Contraseña Obligatorio</h1>
                <p>Por tu seguridad, debes cambiar tu contraseña temporal</p>
            </div>

            <div class="welcome-box">
                <h5><i class="bi bi-emoji-smile"></i> ¡Bienvenido al Sistema!</h5>
                <p>
                    Este es tu primer acceso. Por seguridad, es necesario que establezcas una contraseña personal.
                    Tu contraseña actual es temporal y debe ser cambiada antes de continuar.
                </p>
            </div>

            <div class="user-info">
                <i class="bi bi-person-circle"></i>
                <div>
                    <h6><?= htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario') ?></h6>
                    <p><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
                </div>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i>
                    <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form action="<?= url('auth/processCambiarPasswordObligatorio') ?>" method="POST" id="changeForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <div class="form-floating">
                    <input type="password"
                           class="form-control"
                           id="password_actual"
                           name="password_actual"
                           placeholder="Contraseña Actual"
                           required
                           autofocus>
                    <label for="password_actual">
                        <i class="bi bi-lock"></i> Contraseña Actual (Temporal)
                    </label>
                    <span class="password-toggle" onclick="togglePassword('password_actual', 'toggleIcon1')">
                        <i class="bi bi-eye" id="toggleIcon1"></i>
                    </span>
                </div>

                <div class="form-floating">
                    <input type="password"
                           class="form-control"
                           id="password_nueva"
                           name="password_nueva"
                           placeholder="Nueva Contraseña"
                           required>
                    <label for="password_nueva">
                        <i class="bi bi-key"></i> Nueva Contraseña
                    </label>
                    <span class="password-toggle" onclick="togglePassword('password_nueva', 'toggleIcon2')">
                        <i class="bi bi-eye" id="toggleIcon2"></i>
                    </span>
                </div>

                <div class="requirements">
                    <h6><i class="bi bi-shield-check"></i> Requisitos de Seguridad:</h6>
                    <div class="requirement-item" id="req-length">
                        <i class="bi bi-circle"></i>
                        <span>Mínimo 8 caracteres</span>
                    </div>
                    <div class="requirement-item" id="req-uppercase">
                        <i class="bi bi-circle"></i>
                        <span>Al menos 1 letra mayúscula</span>
                    </div>
                    <div class="requirement-item" id="req-number">
                        <i class="bi bi-circle"></i>
                        <span>Al menos 1 número</span>
                    </div>
                    <div class="requirement-item" id="req-match">
                        <i class="bi bi-circle"></i>
                        <span>Las contraseñas deben coincidir</span>
                    </div>
                </div>

                <div class="form-floating">
                    <input type="password"
                           class="form-control"
                           id="password_confirm"
                           name="password_confirm"
                           placeholder="Confirmar Nueva Contraseña"
                           required>
                    <label for="password_confirm">
                        <i class="bi bi-key-fill"></i> Confirmar Nueva Contraseña
                    </label>
                    <span class="password-toggle" onclick="togglePassword('password_confirm', 'toggleIcon3')">
                        <i class="bi bi-eye" id="toggleIcon3"></i>
                    </span>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                    <span id="btnText">
                        <i class="bi bi-check-circle"></i> Cambiar Contraseña y Continuar
                    </span>
                    <span id="btnSpinner" style="display: none;">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                        Procesando...
                    </span>
                </button>
            </form>

            <!-- Botón para volver al login -->
            <div class="text-center mt-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="volverAlLogin()">
                    <i class="bi bi-arrow-left"></i> Volver al Login
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordNuevaInput = document.getElementById('password_nueva');
        const confirmInput = document.getElementById('password_confirm');
        const submitBtn = document.getElementById('submitBtn');

        let requirements = {
            length: false,
            uppercase: false,
            number: false,
            match: false
        };

        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        // Validate password requirements
        passwordNuevaInput.addEventListener('input', function() {
            const password = this.value;

            // Length
            requirements.length = password.length >= 8;
            updateRequirement('req-length', requirements.length);

            // Uppercase
            requirements.uppercase = /[A-Z]/.test(password);
            updateRequirement('req-uppercase', requirements.uppercase);

            // Number
            requirements.number = /\d/.test(password);
            updateRequirement('req-number', requirements.number);

            // Check match
            checkMatch();
        });

        confirmInput.addEventListener('input', checkMatch);

        function checkMatch() {
            const password = passwordNuevaInput.value;
            const confirm = confirmInput.value;

            if (confirm) {
                requirements.match = password === confirm;
                updateRequirement('req-match', requirements.match);

                if (requirements.match) {
                    confirmInput.classList.remove('is-invalid');
                    confirmInput.classList.add('is-valid');
                } else {
                    confirmInput.classList.remove('is-valid');
                    confirmInput.classList.add('is-invalid');
                }
            } else {
                requirements.match = false;
                updateRequirement('req-match', false);
                confirmInput.classList.remove('is-valid', 'is-invalid');
            }

            updateSubmitButton();
        }

        function updateRequirement(id, valid) {
            const element = document.getElementById(id);
            if (valid) {
                element.classList.add('valid');
                element.classList.remove('invalid');
            } else {
                element.classList.remove('valid');
                element.classList.add('invalid');
            }
        }

        function updateSubmitButton() {
            const allValid = Object.values(requirements).every(v => v === true);
            const actualPassword = document.getElementById('password_actual').value;
            submitBtn.disabled = !(allValid && actualPassword);
        }

        // Also check on actual password input
        document.getElementById('password_actual').addEventListener('input', updateSubmitButton);

        // Form submit
        document.getElementById('changeForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');

            btn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-block';
        });

        // Función para volver al login
        function volverAlLogin() {
            if (confirm('¿Estás seguro de que quieres volver al login? Se cerrará tu sesión actual.')) {
                // Redirigir al endpoint que destruye la sesión
                window.location.href = '<?= url('auth/logout') ?>';
            }
        }
    </script>
</body>
</html>
