<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .password-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }

        .password-card {
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

        .password-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .password-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
        }

        .password-header .icon i {
            font-size: 36px;
            color: white;
        }

        .password-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .password-header p {
            color: var(--secondary-color);
            font-size: 14px;
            margin: 0;
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

        .strength-meter {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }

        .strength-bar {
            height: 100%;
            transition: all 0.3s ease;
        }

        .strength-weak { background: #ef4444; width: 33%; }
        .strength-medium { background: #f59e0b; width: 66%; }
        .strength-strong { background: #10b981; width: 100%; }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="password-card">
            <div class="password-header">
                <div class="icon">
                    <i class="bi bi-key-fill"></i>
                </div>
                <h1>Nueva Contraseña</h1>
                <p>Establece una contraseña segura para tu cuenta</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i>
                    <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form action="<?= url('auth/processNuevaPassword') ?>" method="POST" id="passwordForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <div class="form-floating">
                    <input type="password"
                           class="form-control"
                           id="password"
                           name="password"
                           placeholder="Nueva Contraseña"
                           required>
                    <label for="password">
                        <i class="bi bi-lock"></i> Nueva Contraseña
                    </label>
                    <span class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                        <i class="bi bi-eye" id="toggleIcon1"></i>
                    </span>
                </div>

                <div class="strength-meter" id="strengthMeter" style="display: none;">
                    <div class="strength-bar" id="strengthBar"></div>
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
                           placeholder="Confirmar Contraseña"
                           required>
                    <label for="password_confirm">
                        <i class="bi bi-lock-fill"></i> Confirmar Contraseña
                    </label>
                    <span class="password-toggle" onclick="togglePassword('password_confirm', 'toggleIcon2')">
                        <i class="bi bi-eye" id="toggleIcon2"></i>
                    </span>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                    <span id="btnText">
                        <i class="bi bi-check-circle"></i> Cambiar Contraseña
                    </span>
                    <span id="btnSpinner" style="display: none;">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                        Procesando...
                    </span>
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirm');
        const submitBtn = document.getElementById('submitBtn');
        const strengthMeter = document.getElementById('strengthMeter');
        const strengthBar = document.getElementById('strengthBar');

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
        passwordInput.addEventListener('input', function() {
            const password = this.value;

            strengthMeter.style.display = password ? 'block' : 'none';

            // Length
            requirements.length = password.length >= 8;
            updateRequirement('req-length', requirements.length);

            // Uppercase
            requirements.uppercase = /[A-Z]/.test(password);
            updateRequirement('req-uppercase', requirements.uppercase);

            // Number
            requirements.number = /\d/.test(password);
            updateRequirement('req-number', requirements.number);

            // Password strength
            updateStrength(password);

            // Check match
            checkMatch();
        });

        confirmInput.addEventListener('input', checkMatch);

        function checkMatch() {
            const password = passwordInput.value;
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

        function updateStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            strengthBar.className = 'strength-bar';

            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        }

        function updateSubmitButton() {
            const allValid = Object.values(requirements).every(v => v === true);
            submitBtn.disabled = !allValid;
        }

        // Form submit
        document.getElementById('passwordForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');

            btn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-block';
        });
    </script>
</body>
</html>
