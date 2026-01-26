<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?= APP_NAME ?></title>
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
            padding: 40px 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .registro-container {
            max-width: 700px;
            margin: 0 auto;
        }

        .registro-card {
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

        .registro-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .registro-header .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
        }

        .registro-header .logo i {
            font-size: 28px;
            color: white;
        }

        .registro-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .registro-header p {
            color: var(--secondary-color);
            font-size: 14px;
            margin: 0;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin: 25px 0 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-title i {
            color: var(--primary-color);
            margin-right: 8px;
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
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

        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
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

        .form-text {
            font-size: 12px;
            color: var(--secondary-color);
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

        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-login a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-to-login a:hover {
            color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="registro-container">
        <div class="registro-card">
            <div class="registro-header">
                <div class="logo">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h1>Registro de Nuevo Usuario</h1>
                <p>Complete el formulario para solicitar acceso al sistema</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i>
                    <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form action="<?= url('auth/process-registro') ?>" method="POST" id="registroForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <!-- Información Personal -->
                <div class="section-title">
                    <i class="bi bi-person"></i> Información Personal
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text"
                                   class="form-control"
                                   id="nombre"
                                   name="nombre"
                                   placeholder="Nombre"
                                   required
                                   value="<?= $_SESSION['form_data']['nombre'] ?? '' ?>">
                            <label for="nombre">Nombre *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text"
                                   class="form-control"
                                   id="apellido"
                                   name="apellido"
                                   placeholder="Apellido"
                                   required
                                   value="<?= $_SESSION['form_data']['apellido'] ?? '' ?>">
                            <label for="apellido">Apellido *</label>
                        </div>
                    </div>
                </div>

                <div class="form-floating">
                    <input type="email"
                           class="form-control"
                           id="email"
                           name="email"
                           placeholder="email@ejemplo.com"
                           required
                           value="<?= $_SESSION['form_data']['email'] ?? '' ?>">
                    <label for="email"><i class="bi bi-envelope"></i> Email *</label>
                </div>

                <div class="form-floating">
                    <input type="tel"
                           class="form-control"
                           id="telefono"
                           name="telefono"
                           placeholder="Teléfono"
                           required
                           value="<?= $_SESSION['form_data']['telefono'] ?? '' ?>">
                    <label for="telefono"><i class="bi bi-telephone"></i> Teléfono *</label>
                    <div class="form-text">Ejemplo: 04141234567</div>
                </div>

                <!-- Información Importante sobre Contraseña -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Nota sobre contraseña:</strong> No es necesario crear una contraseña ahora.
                    Una vez que su solicitud sea aprobada, podrá iniciar sesión con la contraseña temporal
                    <strong>123456</strong> y cambiarla inmediatamente por una de su preferencia.
                </div>

                <!-- Información del Apartamento -->
                <div class="section-title">
                    <i class="bi bi-house"></i> Información del Apartamento
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select" id="bloque" name="bloque" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($bloques as $bloque): ?>
                                    <option value="<?= $bloque ?>" <?= ($_SESSION['form_data']['bloque'] ?? '') === $bloque ? 'selected' : '' ?>>
                                        <?= $bloque ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="bloque">Bloque *</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select" id="escalera" name="escalera" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($escaleras as $escalera): ?>
                                    <option value="<?= $escalera ?>" <?= ($_SESSION['form_data']['escalera'] ?? '') === $escalera ? 'selected' : '' ?>>
                                        <?= $escalera ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="escalera">Escalera *</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select" id="piso" name="piso" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($pisos as $piso): ?>
                                    <option value="<?= $piso ?>" <?= ($_SESSION['form_data']['piso'] ?? '') === $piso ? 'selected' : '' ?>>
                                        <?= $piso ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="piso">Piso *</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select" id="apartamento" name="apartamento" required>
                                <option value="">Seleccionar...</option>
                            </select>
                            <label for="apartamento">Apto *</label>
                        </div>
                    </div>
                </div>

                <!-- Controles de Estacionamiento -->
                <div class="section-title">
                    <i class="bi bi-car-front"></i> Controles de Estacionamiento
                </div>

                <div class="form-floating">
                    <input type="number"
                           class="form-control"
                           id="cantidad_controles"
                           name="cantidad_controles"
                           placeholder="Cantidad de Controles"
                           min="1"
                           required
                           value="<?= $_SESSION['form_data']['cantidad_controles'] ?? '1' ?>">
                    <label for="cantidad_controles"><i class="bi bi-hash"></i> Cantidad de Controles *</label>
                    <div class="form-text">Número de controles de estacionamiento que necesita</div>
                </div>

                <!-- Comentarios Adicionales -->
                <div class="form-floating">
                    <textarea class="form-control"
                              id="comentarios"
                              name="comentarios"
                              placeholder="Comentarios adicionales (opcional)"
                              style="height: 80px; resize: vertical;"><?= $_SESSION['form_data']['comentarios'] ?? '' ?></textarea>
                    <label for="comentarios"><i class="bi bi-chat-dots"></i> Comentarios Adicionales</label>
                    <div class="form-text">Información adicional que considere importante (opcional)</div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6 mb-2">
                        <a href="<?= url('auth/login') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver al Login
                        </a>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span id="btnText">
                                <i class="bi bi-send"></i> Enviar Solicitud
                            </span>
                            <span id="btnSpinner" style="display: none;">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Enviando...
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);

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
        document.getElementById('registroForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');

            btn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-block';
        });

        // Cargar escaleras, pisos y apartamentos dinámicamente
        const bloqueSelect = document.getElementById('bloque');
        const escaleraSelect = document.getElementById('escalera');
        const pisoSelect = document.getElementById('piso');
        const apartamentoSelect = document.getElementById('apartamento');

        // Cuando cambia el bloque, cargar escaleras
        bloqueSelect.addEventListener('change', function() {
            const bloque = this.value;
            
            // Limpiar selects dependientes
            escaleraSelect.innerHTML = '<option value="">Seleccionar...</option>';
            pisoSelect.innerHTML = '<option value="">Seleccionar...</option>';
            apartamentoSelect.innerHTML = '<option value="">Seleccionar...</option>';

            if (!bloque) return;

            // Cargar escaleras para este bloque
            fetch(`<?= url('api/get-escaleras') ?>?bloque=${bloque}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.escalera;
                        option.textContent = item.escalera;
                        escaleraSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error cargando escaleras:', error);
                });
        });

        // Cuando cambia la escalera, cargar pisos
        escaleraSelect.addEventListener('change', function() {
            const bloque = bloqueSelect.value;
            const escalera = this.value;
            
            // Limpiar selects dependientes
            pisoSelect.innerHTML = '<option value="">Seleccionar...</option>';
            apartamentoSelect.innerHTML = '<option value="">Seleccionar...</option>';

            if (!bloque || !escalera) return;

            // Cargar pisos para este bloque y escalera
            fetch(`<?= url('api/get-pisos') ?>?bloque=${bloque}&escalera=${escalera}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.piso;
                        // Convertir a número para comparación
                        const pisoNum = parseInt(item.piso);
                        option.textContent = pisoNum === 0 ? 'PB' : pisoNum;
                        pisoSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error cargando pisos:', error);
                });
        });

        // Cuando cambia el piso, cargar apartamentos
        pisoSelect.addEventListener('change', function() {
            const bloque = bloqueSelect.value;
            const escalera = escaleraSelect.value;
            const piso = this.value;
            
            console.log('Cargando apartamentos:', {bloque, escalera, piso});
            
            apartamentoSelect.innerHTML = '<option value="">Seleccionar...</option>';

            if (!bloque || !escalera || !piso) {
                console.log('Faltan datos, abortando');
                return;
            }

            const timestamp = new Date().getTime();
            const url = `<?= url('api/get-apartamentos') ?>?bloque=${bloque}&escalera=${escalera}&piso=${piso}&_=${timestamp}`;
            console.log('URL:', url);

            // Cargar apartamentos para este bloque, escalera y piso
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Apartamentos recibidos:', data);
                    console.log('Total apartamentos:', data.length);
                    
                    if (data.length === 0) {
                        console.warn('No se encontraron apartamentos');
                    }
                    
                    data.forEach(apto => {
                        console.log('Agregando apartamento:', apto.numero_apartamento);
                        const option = document.createElement('option');
                        option.value = apto.numero_apartamento;
                        option.textContent = apto.numero_apartamento;
                        apartamentoSelect.appendChild(option);
                    });
                    
                    console.log('Apartamentos cargados en el select');
                })
                .catch(error => {
                    console.error('Error cargando apartamentos:', error);
                });
        });

        <?php unset($_SESSION['form_data']); ?>
    </script>
</body>
</html>
