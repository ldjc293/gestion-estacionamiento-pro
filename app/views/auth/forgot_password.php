<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - <?= APP_NAME ?></title>
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

        .recovery-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }

        .recovery-card {
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

        .recovery-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .recovery-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
        }

        .recovery-header .icon i {
            font-size: 36px;
            color: white;
        }

        .recovery-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .recovery-header p {
            color: var(--secondary-color);
            font-size: 14px;
            margin: 0;
            line-height: 1.6;
        }

        .info-box {
            background: #eff6ff;
            border-left: 4px solid var(--primary-color);
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #1e40af;
        }

        .info-box i {
            margin-right: 8px;
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

        .btn-outline-secondary {
            border: 2px solid #e2e8f0;
            color: var(--secondary-color);
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 12px;
        }

        .btn-outline-secondary:hover {
            background: #f8fafc;
            border-color: var(--secondary-color);
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

        .footer-link {
            text-align: center;
            margin-top: 24px;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .footer-link a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }

        .footer-link a:hover {
            opacity: 0.8;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <div class="recovery-card">
            <div class="recovery-header">
                <div class="icon">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h1>Recuperar Contraseña</h1>
                <p>Ingresa tu email y te enviaremos un código de verificación</p>
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

            <div class="info-box">
                <i class="bi bi-info-circle-fill"></i>
                <strong>Instrucciones:</strong> Recibirás un código de 6 dígitos que expira en 15 minutos. Revisa también tu carpeta de SPAM.
            </div>

            <form action="<?= url('auth/processForgotPassword') ?>" method="POST" id="recoveryForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <div class="form-floating">
                    <input type="email"
                           class="form-control"
                           id="email"
                           name="email"
                           placeholder="email@ejemplo.com"
                           required
                           autofocus>
                    <label for="email">
                        <i class="bi bi-envelope"></i> Correo Electrónico Registrado
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span id="btnText">
                        <i class="bi bi-send"></i> Enviar Código de Verificación
                    </span>
                    <span id="btnSpinner" style="display: none;">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Enviando...
                    </span>
                </button>

                <a href="<?= url('auth/login') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver al Login
                </a>
            </form>
        </div>

        <div class="footer-link">
            <p>
                <i class="bi bi-shield-check"></i>
                Por tu seguridad, no revelamos si un email existe en el sistema
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Loading state on submit
        document.getElementById('recoveryForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');

            btn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-block';
        });

        // Auto-hide alerts
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
