<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Código - <?= APP_NAME ?></title>
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

        .verify-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }

        .verify-card {
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

        .verify-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .verify-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--success-color), #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        }

        .verify-header .icon i {
            font-size: 36px;
            color: white;
        }

        .verify-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .verify-header p {
            color: var(--secondary-color);
            font-size: 14px;
            margin: 0;
        }

        .email-display {
            background: #f1f5f9;
            padding: 12px 16px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 24px;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .code-input-container {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .code-digit {
            width: 60px;
            height: 70px;
            font-size: 32px;
            font-weight: 700;
            text-align: center;
            border: 3px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .code-digit:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .code-digit.filled {
            border-color: var(--success-color);
            background: #f0fdf4;
        }

        .hidden-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
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

        .timer {
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--secondary-color);
        }

        .timer.warning {
            color: #d97706;
            font-weight: 600;
        }

        .timer.expired {
            color: var(--danger-color);
            font-weight: 600;
        }

        .resend-link {
            text-align: center;
            margin-top: 20px;
        }

        .resend-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }

        .resend-link a:hover {
            text-decoration: underline;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-card">
            <div class="verify-header">
                <div class="icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h1>Verificar Código</h1>
                <p>Ingresa el código de 6 dígitos que enviamos a tu email</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i>
                    <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="email-display">
                <i class="bi bi-envelope-fill"></i>
                <?php
                    $email = $_SESSION['reset_email'] ?? '';
                    // Ocultar parcialmente el email
                    $parts = explode('@', $email);
                    if (count($parts) === 2) {
                        $username = $parts[0];
                        $domain = $parts[1];
                        $hiddenUsername = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2);
                        echo $hiddenUsername . '@' . $domain;
                    }
                ?>
            </div>

            <div class="timer" id="timer">
                <i class="bi bi-clock"></i> Expira en: <span id="timeRemaining">15:00</span>
            </div>

            <form action="<?= url('auth/processVerificarCodigo') ?>" method="POST" id="verifyForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="codigo" id="codigoHidden">

                <div class="code-input-container">
                    <input type="text" maxlength="1" class="code-digit" id="digit1" data-index="0" autocomplete="off">
                    <input type="text" maxlength="1" class="code-digit" id="digit2" data-index="1" autocomplete="off">
                    <input type="text" maxlength="1" class="code-digit" id="digit3" data-index="2" autocomplete="off">
                    <input type="text" maxlength="1" class="code-digit" id="digit4" data-index="3" autocomplete="off">
                    <input type="text" maxlength="1" class="code-digit" id="digit5" data-index="4" autocomplete="off">
                    <input type="text" maxlength="1" class="code-digit" id="digit6" data-index="5" autocomplete="off">
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                    <span id="btnText">
                        <i class="bi bi-check-circle"></i> Verificar Código
                    </span>
                    <span id="btnSpinner" style="display: none;">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                        Verificando...
                    </span>
                </button>

                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='<?= url('auth/forgot-password') ?>'">
                    <i class="bi bi-arrow-left"></i> Volver
                </button>
            </form>

            <div class="resend-link">
                <a href="<?= url('auth/forgot-password') ?>">
                    <i class="bi bi-arrow-clockwise"></i> ¿No recibiste el código? Reenviar
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const digits = document.querySelectorAll('.code-digit');
        const submitBtn = document.getElementById('submitBtn');
        const codigoHidden = document.getElementById('codigoHidden');
        const timerElement = document.getElementById('timer');
        const timeRemainingSpan = document.getElementById('timeRemaining');

        // Focus first digit on load
        digits[0].focus();

        // Handle digit input
        digits.forEach((digit, index) => {
            digit.addEventListener('input', (e) => {
                const value = e.target.value;

                // Only allow numbers
                if (!/^\d$/.test(value)) {
                    e.target.value = '';
                    return;
                }

                // Mark as filled
                e.target.classList.add('filled');

                // Move to next digit
                if (value && index < digits.length - 1) {
                    digits[index + 1].focus();
                }

                // Check if all digits are filled
                checkComplete();
            });

            // Handle backspace
            digit.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    digits[index - 1].focus();
                    digits[index - 1].value = '';
                    digits[index - 1].classList.remove('filled');
                    checkComplete();
                }

                // Handle paste
                if (e.key === 'v' && (e.ctrlKey || e.metaKey)) {
                    e.preventDefault();
                    navigator.clipboard.readText().then(text => {
                        const code = text.replace(/\D/g, '').slice(0, 6);
                        code.split('').forEach((char, i) => {
                            if (digits[i]) {
                                digits[i].value = char;
                                digits[i].classList.add('filled');
                            }
                        });
                        checkComplete();
                    });
                }
            });
        });

        // Check if all digits are filled
        function checkComplete() {
            const code = Array.from(digits).map(d => d.value).join('');
            codigoHidden.value = code;

            if (code.length === 6) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // Timer countdown (15 minutes)
        let timeLeft = 15 * 60; // 15 minutes in seconds
        const timerInterval = setInterval(() => {
            timeLeft--;

            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timeRemainingSpan.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            // Warning at 3 minutes
            if (timeLeft <= 180 && timeLeft > 0) {
                timerElement.classList.add('warning');
            }

            // Expired
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerElement.classList.add('expired');
                timerElement.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Código expirado. Por favor, solicita uno nuevo.';
                submitBtn.disabled = true;
                digits.forEach(d => d.disabled = true);
            }
        }, 1000);

        // Form submit
        document.getElementById('verifyForm').addEventListener('submit', function(e) {
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
