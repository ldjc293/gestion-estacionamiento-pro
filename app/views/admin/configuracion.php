<?php
$pageTitle = 'Configuración del Sistema';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Configuración', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="row">
            <div class="col-md-8">
                <!-- Configuración General -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-gear"></i> Configuración General
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="<?= url('admin/processConfiguracion') ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Nombre del Sistema</label>
                                <input type="text"
                                       class="form-control"
                                       name="sistema_nombre"
                                       value="<?= htmlspecialchars($config['sistema_nombre'] ?? 'Sistema de Pagos Estacionamiento') ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Monto Mensualidad (USD)</label>
                                <input type="number"
                                       class="form-control"
                                       name="monto_mensualidad"
                                       value="<?= $config['monto_mensualidad'] ?? MONTO_MENSUALIDAD ?>"
                                       step="0.01"
                                       min="0"
                                       required>
                                <small class="text-muted">Precio por control mensual en dólares</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Meses para Bloqueo Automático</label>
                                <input type="number"
                                       class="form-control"
                                       name="meses_bloqueo"
                                       value="<?= $config['meses_bloqueo'] ?? MESES_BLOQUEO ?>"
                                       min="1"
                                       max="12"
                                       required>
                                <small class="text-muted">Cantidad de meses de mora antes de bloquear controles</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Tasa de Cambio BCV (Bs por USD)</label>
                                <div class="input-group">
                                    <input type="number"
                                           class="form-control"
                                           id="tasa_bcv_display"
                                           value="<?= number_format($config['tasa_bcv'] ?? 36.50, 2, '.', '') ?>"
                                           step="0.01"
                                           min="0"
                                           disabled>
                                    <button type="button" class="btn btn-outline-secondary" id="btnActualizarTasa" onclick="actualizarTasaAutomatica(this)">
                                        <i class="bi bi-arrow-repeat"></i> Actualizar desde BCV
                                    </button>
                                </div>
                                <small class="text-muted">
                                    <?php if (isset($config['tasa_bcv_fecha'])): ?>
                                        Última actualización: <?= date('d/m/Y H:i', strtotime($config['tasa_bcv_fecha'])) ?>
                                        (Fuente: <?= htmlspecialchars($config['tasa_bcv_fuente']) ?>)
                                    <?php else: ?>
                                        Sin actualizaciones registradas
                                    <?php endif; ?>
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Email de Notificaciones</label>
                                <input type="email"
                                       class="form-control"
                                       name="email_notificaciones"
                                       value="<?= htmlspecialchars($config['email_notificaciones'] ?? '') ?>"
                                       required>
                                <small class="text-muted">Email que aparece como remitente en las notificaciones</small>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Guardar Configuración
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Configuración de Email (SMTP) -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-envelope"></i> Configuración SMTP
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="<?= url('admin/processSmtp') ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Servidor SMTP</label>
                                <input type="text"
                                       class="form-control"
                                       name="smtp_host"
                                       value="<?= htmlspecialchars($config['smtp_host'] ?? '') ?>"
                                       placeholder="smtp.gmail.com"
                                       required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Puerto</label>
                                    <input type="number"
                                           class="form-control"
                                           name="smtp_port"
                                           value="<?= $config['smtp_port'] ?? 587 ?>"
                                           required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Encriptación</label>
                                    <select class="form-select" name="smtp_encryption" required>
                                        <option value="tls" <?= ($config['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                        <option value="ssl" <?= ($config['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Usuario SMTP</label>
                                <input type="text"
                                       class="form-control"
                                       name="smtp_user"
                                       value="<?= htmlspecialchars($config['smtp_user'] ?? '') ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Contraseña SMTP</label>
                                <input type="password"
                                       class="form-control"
                                       name="smtp_password"
                                       placeholder="••••••••">
                                <small class="text-muted">Dejar en blanco para no cambiar</small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Guardar SMTP
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="probarEmail()">
                                    <i class="bi bi-send"></i> Enviar Email de Prueba
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Mantenimiento del Sistema -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-wrench"></i> Mantenimiento
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Precaución:</strong> Estas acciones pueden afectar el funcionamiento del sistema.
                        </div>

                        <div class="d-grid gap-2">
                            <button onclick="limpiarCache()" class="btn btn-outline-primary">
                                <i class="bi bi-trash"></i> Limpiar Caché
                            </button>
                            <button onclick="regenerarMensualidades()" class="btn btn-outline-warning">
                                <i class="bi bi-calendar-plus"></i> Generar Mensualidades para Todos los Clientes
                            </button>
                            <button onclick="verificarIntegridad()" class="btn btn-outline-info">
                                <i class="bi bi-check2-square"></i> Verificar Integridad de Datos
                            </button>
                            <button onclick="exportarBaseDatos()" class="btn btn-outline-success">
                                <i class="bi bi-download"></i> Exportar Base de Datos (Backup)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Información del Sistema -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle"></i> Información del Sistema
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0" style="font-size: 14px;">
                            <li class="mb-2">
                                <strong>Versión:</strong> 1.0.0
                            </li>
                            <li class="mb-2">
                                <strong>PHP:</strong> <?= phpversion() ?>
                            </li>
                            <li class="mb-2">
                                <strong>Base de Datos:</strong> MySQL
                            </li>
                            <li class="mb-2">
                                <strong>Servidor:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?>
                            </li>
                            <li class="mb-0">
                                <strong>Timezone:</strong> <?= date_default_timezone_get() ?>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Configuración de CRON -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bi bi-clock-history"></i> Tareas CRON
                        </h6>
                        <small class="text-muted">Click para configurar</small>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tareasCron)): ?>
                            <p class="text-muted text-center">No hay tareas CRON configuradas</p>
                        <?php else: ?>
                            <?php foreach ($tareasCron as $tarea): ?>
                                <div class="border rounded p-3 mb-3" style="cursor: pointer;" onclick="abrirModalCron(<?= htmlspecialchars(json_encode($tarea)) ?>)">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <strong><?= htmlspecialchars($tarea['descripcion']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php if ($tarea['frecuencia'] === 'mensual'): ?>
                                                    Día <?= $tarea['dia_mes'] ?> de cada mes, <?= date('H:i', strtotime($tarea['hora_ejecucion'])) ?>
                                                <?php elseif ($tarea['frecuencia'] === 'semanal'): ?>
                                                    Semanal, <?= date('H:i', strtotime($tarea['hora_ejecucion'])) ?>
                                                <?php else: ?>
                                                    Diario, <?= date('H:i', strtotime($tarea['hora_ejecucion'])) ?>
                                                <?php endif; ?>
                                            </small>
                                            <?php if ($tarea['ultima_ejecucion']): ?>
                                                <br><small class="text-info">
                                                    <i class="bi bi-check-circle"></i> Última ejecución: <?= date('d/m/Y H:i', strtotime($tarea['ultima_ejecucion'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge <?= $tarea['activo'] ? 'bg-success' : 'bg-secondary' ?> mb-2">
                                                <?= $tarea['activo'] ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                            <br>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    onclick="event.stopPropagation(); ejecutarTareaCron('<?= $tarea['nombre_tarea'] ?>', '<?= htmlspecialchars($tarea['descripcion']) ?>')">
                                                <i class="bi bi-play-circle"></i> Ejecutar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para configurar tarea CRON -->
<div class="modal fade" id="modalConfigCron" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configurar Tarea CRON</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cron_tarea_id">
                <input type="hidden" id="cron_nombre_tarea">
                <input type="hidden" id="cron_frecuencia">

                <div class="alert alert-info">
                    <strong id="cron_descripcion"></strong>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Estado</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="cron_activo">
                        <label class="form-check-label" for="cron_activo">
                            <span id="cron_activo_label">Activo</span>
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="cron_hora" class="form-label fw-bold">Hora de Ejecución</label>
                    <input type="time" class="form-control" id="cron_hora" required>
                    <small class="text-muted">Hora en formato 24 horas (HH:MM)</small>
                </div>

                <div class="mb-3" id="cron_dia_mes_group" style="display: none;">
                    <label for="cron_dia_mes" class="form-label fw-bold">Día del Mes</label>
                    <input type="number" class="form-control" id="cron_dia_mes" min="1" max="31">
                    <small class="text-muted">Para tareas mensuales (1-31)</small>
                </div>

                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Importante:</strong> Los cambios se aplicarán inmediatamente. Asegúrese de configurar correctamente la hora de ejecución.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarConfigCron()">
                    <i class="bi bi-check-circle"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$csrfToken = generateCSRFToken();
$additionalJS = <<<JS
<script>
// CSRF Token para esta página (URL_BASE ya está definido en header.php)
const CSRF_TOKEN = '{$csrfToken}';

function actualizarTasaAutomatica(btn) {
    if (!confirm('¿Actualizar la tasa de cambio consultando la página oficial del BCV?\\n\\nEsta acción actualizará la tasa para todos los cálculos del sistema.')) {
        return;
    }

    // Deshabilitar botón y mostrar estado de carga
    btn.disabled = true;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Consultando BCV...';

    // Realizar petición AJAX
    fetch(URL_BASE + '/admin/actualizarTasaBCV', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            csrf_token: CSRF_TOKEN
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la conexión con el servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Actualizar el campo de tasa en la interfaz
            const tasaInput = document.getElementById('tasa_bcv_display');
            if (tasaInput) {
                tasaInput.value = data.tasa;
            }

            // Actualizar la fecha de última actualización
            // El <small> es el siguiente elemento después del <div class="input-group">
            const inputGroup = document.getElementById('tasa_bcv_display').closest('.input-group');
            const smallText = inputGroup ? inputGroup.nextElementSibling : null;
            if (smallText && smallText.tagName === 'SMALL' && data.fecha) {
                smallText.innerHTML = 'Última actualización: ' + data.fecha + ' (Fuente: ' + data.fuente + ')';
            }

            // Mostrar mensaje de éxito
            showToast(data.message, 'success');

            // Restaurar botón después de 2 segundos
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }, 2000);

        } else {
            // Mostrar mensaje de error
            showToast(data.message || 'Error al actualizar la tasa del BCV', 'error');

            // Restaurar botón
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error de conexión. Verifique su conexión a internet e intente nuevamente.', 'error');

        // Restaurar botón
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    });
}

// Función helper para mostrar notificaciones toast
function showToast(message, type = 'info') {
    // Si existe un sistema de toasts en el proyecto, usarlo
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
        return;
    }

    // Si existe Bootstrap toast
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        // Crear contenedor si no existe
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }

        // Determinar color según tipo
        const bgClass = type === 'success' ? 'bg-success' :
                       type === 'error' ? 'bg-danger' :
                       type === 'warning' ? 'bg-warning' : 'bg-info';

        // Crear toast
        const toastHTML = `
            <div class="toast align-items-center text-white \${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        \${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 5000 });
        toast.show();

        // Eliminar el elemento después de ocultarse
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    } else {
        // Fallback a alert si no hay sistema de toasts
        alert(message);
    }
}

function probarEmail() {
    const email = prompt('Ingrese el email donde desea recibir la prueba:');
    if (email) {
        showToast('Enviando email de prueba...', 'info');
        // TODO: Implementar envío de email de prueba
    }
}

function limpiarCache() {
    if (!confirm('¿Limpiar todos los archivos de caché del sistema?\\n\\nEsta acción es segura y puede mejorar el rendimiento.')) {
        return;
    }

    showToast('Limpiando caché del sistema...', 'info');

    fetch(URL_BASE + '/admin/limpiarCache', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            csrf_token: CSRF_TOKEN
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Error al limpiar caché', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error al procesar la solicitud', 'error');
    });
}

function regenerarMensualidades() {
    if (!confirm('⚠️ ¿Generar mensualidades para todos los clientes activos?\\n\\nEsta acción generará las mensualidades del mes actual para todos los clientes activos que no las tengan.\\nNo afectará a las mensualidades ya existentes.')) {
        return;
    }

    showToast('Generando mensualidades...', 'info');

    fetch(URL_BASE + '/admin/regenerarMensualidades', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            csrf_token: CSRF_TOKEN
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            // No recargar la página si no se generaron mensualidades
            if (data.cantidad > 0) {
                setTimeout(() => location.reload(), 2000);
            }
        } else {
            showToast(data.message || 'Error al generar mensualidades', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error al procesar la solicitud', 'error');
    });
}

function verificarIntegridad() {
    showToast('Verificando integridad de datos...', 'info');

    fetch(URL_BASE + '/admin/verificarIntegridad', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            csrf_token: CSRF_TOKEN
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Verificación completada:\\n\\n' + data.message);
        } else {
            alert('✗ Errores encontrados:\\n\\n' + (data.message || 'Error al verificar integridad'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error al procesar la solicitud', 'error');
    });
}

function exportarBaseDatos() {
    if (!confirm('¿Exportar backup de la base de datos completa?\\n\\nSe descargará un archivo .sql comprimido.')) {
        return;
    }

    // Ejecutar el backup y descargar
    window.location.href = URL_BASE + '/admin/exportarBaseDatos?csrf_token=' + CSRF_TOKEN;
    showToast('Generando backup de base de datos...', 'info');
}

// ============ FUNCIONES PARA CONFIGURACIÓN CRON ============

function abrirModalCron(tarea) {
    // Llenar el modal con los datos de la tarea
    document.getElementById('cron_tarea_id').value = tarea.id;
    document.getElementById('cron_nombre_tarea').value = tarea.nombre_tarea;
    document.getElementById('cron_frecuencia').value = tarea.frecuencia;
    document.getElementById('cron_descripcion').textContent = tarea.descripcion;
    document.getElementById('cron_activo').checked = tarea.activo == 1;

    // Actualizar label del switch
    updateCronActivoLabel();

    // Extraer hora de la hora_ejecucion (formato HH:MM:SS)
    const horaCompleta = tarea.hora_ejecucion;
    const horaParts = horaCompleta.split(':');
    document.getElementById('cron_hora').value = horaParts[0] + ':' + horaParts[1];

    // Mostrar/ocultar campo de día del mes según la frecuencia
    if (tarea.frecuencia === 'mensual') {
        document.getElementById('cron_dia_mes_group').style.display = 'block';
        document.getElementById('cron_dia_mes').value = tarea.dia_mes || 1;
    } else {
        document.getElementById('cron_dia_mes_group').style.display = 'none';
    }

    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('modalConfigCron'));
    modal.show();
}

function updateCronActivoLabel() {
    const checkbox = document.getElementById('cron_activo');
    const label = document.getElementById('cron_activo_label');
    label.textContent = checkbox.checked ? 'Activo' : 'Inactivo';
    label.className = checkbox.checked ? 'text-success fw-bold' : 'text-secondary';
}

// Actualizar label cuando cambia el estado
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('cron_activo');
    if (checkbox) {
        checkbox.addEventListener('change', updateCronActivoLabel);
    }
});

function guardarConfigCron() {
    const tareaId = document.getElementById('cron_tarea_id').value;
    const activo = document.getElementById('cron_activo').checked;
    const horaEjecucion = document.getElementById('cron_hora').value;
    const diaMes = document.getElementById('cron_dia_mes').value || null;
    const frecuencia = document.getElementById('cron_frecuencia').value;

    if (!horaEjecucion) {
        alert('Por favor ingrese una hora de ejecución válida');
        return;
    }

    if (frecuencia === 'mensual' && (!diaMes || diaMes < 1 || diaMes > 31)) {
        alert('Por favor ingrese un día del mes válido (1-31)');
        return;
    }

    // Deshabilitar botón
    const btnGuardar = event.target;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...';

    fetch(URL_BASE + '/admin/actualizarTareaCron', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            csrf_token: CSRF_TOKEN,
            tarea_id: parseInt(tareaId),
            activo: activo,
            hora_ejecucion: horaEjecucion,
            dia_mes: diaMes ? parseInt(diaMes) : null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Error al actualizar la tarea');
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="bi bi-check-circle"></i> Guardar Cambios';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="bi bi-check-circle"></i> Guardar Cambios';
    });
}

function ejecutarTareaCron(nombreTarea, descripcion) {
    if (!confirm('¿Ejecutar ahora la tarea \"' + descripcion + '\"?\\n\\nEsto ejecutará la tarea inmediatamente.')) {
        return;
    }

    fetch(URL_BASE + '/admin/ejecutarTareaCron', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            csrf_token: CSRF_TOKEN,
            nombre_tarea: nombreTarea
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ ' + data.message);
            location.reload();
        } else {
            alert('✗ ' + (data.message || 'Error al ejecutar la tarea'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}
</script>
JS;

require_once __DIR__ . '/../layouts/footer.php';
