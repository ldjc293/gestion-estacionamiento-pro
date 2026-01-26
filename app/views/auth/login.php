<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --bg-light: #f8fafc;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-card {
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

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header .logo {
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

        .login-header .logo i {
            font-size: 36px;
            color: white;
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .login-header p {
            color: var(--secondary-color);
            font-size: 14px;
            margin: 0;
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
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

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: var(--primary-dark);
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px 16px;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid var(--danger-color);
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border-left: 4px solid var(--success-color);
        }

        .footer-text {
            text-align: center;
            color: white;
            margin-top: 30px;
            font-size: 14px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--secondary-color);
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .form-floating {
            position: relative;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="bi bi-p-square-fill"></i>
                </div>
                <h1><?= ESTACIONAMIENTO_NOMBRE ?></h1>
                <p>Sistema de Control de Pagos</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i>
                    <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    <?= $_SESSION['success'] ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form action="<?= url('auth/processLogin') ?>" method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <div class="form-floating">
                    <input type="email"
                           class="form-control"
                           id="email"
                           name="email"
                           placeholder="email@ejemplo.com"
                           required
                           autocomplete="email"
                           autofocus>
                    <label for="email">
                        <i class="bi bi-envelope"></i> Correo Electrónico
                    </label>
                </div>

                <div class="form-floating">
                    <input type="password"
                           class="form-control"
                           id="password"
                           name="password"
                           placeholder="Contraseña"
                           required
                           autocomplete="current-password">
                    <label for="password">
                        <i class="bi bi-lock"></i> Contraseña
                    </label>
                    <span class="password-toggle" onclick="togglePassword()">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </span>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span id="btnText">
                        <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                    </span>
                    <span id="btnSpinner" style="display: none;">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Iniciando...
                    </span>
                </button>
            </form>

            <div class="forgot-password">
                <a href="<?= url('auth/forgot-password') ?>">
                    <i class="bi bi-question-circle"></i> ¿Olvidaste tu contraseña?
                </a>
            </div>

            <div class="forgot-password" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                <p style="color: var(--secondary-color); font-size: 14px; margin-bottom: 10px;">¿No tienes una cuenta?</p>
                <a href="<?= url('auth/registro') ?>" style="color: var(--success-color);">
                    <i class="bi bi-person-plus"></i> Registrarse
                </a>
            </div>
        </div>

        <div class="footer-text">
            <p>&copy; <?= date('Y') ?> - <?= ESTACIONAMIENTO_NOMBRE ?></p>
            <p style="font-size: 12px; opacity: 0.8;">
                <?= ESTACIONAMIENTO_DIRECCION ?>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        // Loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');

            btn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-block';
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.animation = 'slideUp 0.3s ease-out reverse';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
