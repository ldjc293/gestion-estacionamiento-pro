<?php
$pageTitle = 'Generar Solicitud';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('cliente/dashboard')],
    ['label' => 'Generar Solicitud', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Formulario de Solicitud -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-envelope-plus"></i> Nueva Solicitud
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="<?= url('cliente/process-solicitud') ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                            <div class="mb-4">
                                <label for="tipoSolicitud" class="form-label fw-bold">Tipo de Solicitud *</label>
                                <select class="form-select" id="tipoSolicitud" name="tipo_solicitud" required>
                                    <option value="">-- Seleccione un tipo --</option>
                                    <option value="desincorporar_control">Desincorporar Control</option>
                                    <option value="reportar_perdido">Reportar Control Perdido</option>
                                    <option value="agregar_control">Añadir Nuevo Control</option>
                                    <option value="comprar_control">Comprar Control</option>
                                    <option value="solicitud_personalizada">Solicitud Personalizada</option>
                                </select>
                                <div class="form-text">Seleccione el tipo de solicitud que desea realizar</div>
                            </div>

                            <!-- Selector de Control (para solicitudes específicas) -->
                            <div class="mb-4" id="controlSelector" style="display: none;">
                                <label for="controlId" class="form-label fw-bold">Seleccionar Control</label>
                                <select class="form-select" id="controlId" name="control_id">
                                    <option value="">-- Seleccione un control --</option>
                                    <?php if (!empty($controles)): ?>
                                        <?php foreach ($controles as $control): ?>
                                            <option value="<?= $control['id'] ?>">
                                                Control #<?= htmlspecialchars($control['numero_control_completo']) ?>
                                                (<?= ucfirst($control['estado']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">Seleccione el control al que se refiere su solicitud</div>
                            </div>

                            <div class="mb-4">
                                <label for="descripcion" class="form-label fw-bold">Descripción *</label>
                                <textarea class="form-control"
                                          id="descripcion"
                                          name="descripcion"
                                          rows="5"
                                          placeholder="Describa detalladamente su solicitud..."
                                          required></textarea>
                                <div class="form-text">Proporcione todos los detalles necesarios para procesar su solicitud</div>
                            </div>

                            <div class="text-end">
                                <a href="<?= url('cliente/dashboard') ?>" class="btn btn-secondary me-2">
                                    <i class="bi bi-arrow-left"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i> Enviar Solicitud
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Historial de Solicitudes -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-clock-history"></i> Mis Solicitudes
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($historialSolicitudes)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No has realizado ninguna solicitud aún.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                            <th>Respuesta/Observación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historialSolicitudes as $solicitud): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    $tipos = [
                                                        'desincorporar_control' => ['label' => 'Desincorporar', 'icon' => 'dash-circle', 'color' => 'danger'],
                                                        'reportar_perdido' => ['label' => 'Reportar Perdido', 'icon' => 'exclamation-triangle', 'color' => 'warning'],
                                                        'agregar_control' => ['label' => 'Añadir Control', 'icon' => 'plus-circle', 'color' => 'success'],
                                                        'comprar_control' => ['label' => 'Comprar Control', 'icon' => 'cart-plus', 'color' => 'primary'],
                                                        'solicitud_personalizada' => ['label' => 'Personalizada', 'icon' => 'chat-dots', 'color' => 'info'],
                                                        'cambio_cantidad_controles' => ['label' => 'Cambio Cantidad', 'icon' => 'arrow-left-right', 'color' => 'secondary'],
                                                        'suspension_control' => ['label' => 'Suspensión', 'icon' => 'pause-circle', 'color' => 'warning'],
                                                        'desactivacion_control' => ['label' => 'Desactivación', 'icon' => 'x-circle', 'color' => 'dark']
                                                    ];
                                                    $tipoInfo = $tipos[$solicitud['tipo_solicitud']] ?? ['label' => ucfirst(str_replace('_', ' ', $solicitud['tipo_solicitud'])), 'icon' => 'tag', 'color' => 'secondary'];
                                                    ?>
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-circle bg-<?= $tipoInfo['color'] ?> bg-opacity-10 text-<?= $tipoInfo['color'] ?> me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                                            <i class="bi bi-<?= $tipoInfo['icon'] ?>"></i>
                                                        </div>
                                                        <div>
                                                            <span class="fw-bold d-block"><?= $tipoInfo['label'] ?></span>
                                                            <?php if ($solicitud['control_numero']): ?>
                                                                <small class="text-muted">Control #<?= htmlspecialchars($solicitud['control_numero']) ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y', strtotime($solicitud['fecha_solicitud'])) ?><br>
                                                        <?= date('H:i', strtotime($solicitud['fecha_solicitud'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $estadoClass = match($solicitud['estado']) {
                                                        'pendiente' => 'warning',
                                                        'aprobado' => 'success',
                                                        'rechazado' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?= $estadoClass ?>">
                                                        <?= ucfirst($solicitud['estado']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($solicitud['observaciones'])): ?>
                                                        <small class="d-block text-muted fst-italic">
                                                            "<?= htmlspecialchars($solicitud['observaciones']) ?>"
                                                        </small>
                                                    <?php elseif ($solicitud['estado'] === 'pendiente'): ?>
                                                        <small class="text-muted">- En revisión -</small>
                                                    <?php else: ?>
                                                        <small class="text-muted">- Sin observaciones -</small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Información de Ayuda -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle"></i> Tipos de Solicitud
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionTipos">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#desincorporar">
                                        <i class="bi bi-dash-circle text-danger me-2"></i> Desincorporar Control
                                    </button>
                                </h2>
                                <div id="desincorporar" class="accordion-collapse collapse" data-bs-parent="#accordionTipos">
                                    <div class="accordion-body">
                                        <small>Solicite la desincorporación de un control que ya no necesita. El control quedará disponible para otros residentes.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#perdido">
                                        <i class="bi bi-exclamation-triangle text-warning me-2"></i> Reportar Control Perdido
                                    </button>
                                </h2>
                                <div id="perdido" class="accordion-collapse collapse" data-bs-parent="#accordionTipos">
                                    <div class="accordion-body">
                                        <small>Reporte un control perdido o robado. Se bloqueará el control y podrá solicitar un reemplazo.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#agregar">
                                        <i class="bi bi-plus-circle text-success me-2"></i> Añadir Nuevo Control
                                    </button>
                                </h2>
                                <div id="agregar" class="accordion-collapse collapse" data-bs-parent="#accordionTipos">
                                    <div class="accordion-body">
                                        <small>Solicite la asignación de un control adicional para un segundo vehículo u otro uso.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#comprar">
                                        <i class="bi bi-cart-plus text-primary me-2"></i> Comprar Control
                                    </button>
                                </h2>
                                <div id="comprar" class="accordion-collapse collapse" data-bs-parent="#accordionTipos">
                                    <div class="accordion-body">
                                        <small>Compre un control adicional pagando la tarifa correspondiente. Se asignará automáticamente.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#personalizada">
                                        <i class="bi bi-chat-dots text-info me-2"></i> Solicitud Personalizada
                                    </button>
                                </h2>
                                <div id="personalizada" class="accordion-collapse collapse" data-bs-parent="#accordionTipos">
                                    <div class="accordion-body">
                                        <small>Para cualquier otra solicitud no contemplada en las opciones anteriores.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Contacto -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-headset"></i> ¿Necesita Ayuda?
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <i class="bi bi-telephone text-primary" style="font-size: 48px;"></i>
                        <p class="mt-3 mb-2">
                            <strong>Atención al Cliente</strong><br>
                            Lunes a Viernes: 8:00 AM - 5:00 PM
                        </p>
                        <p class="text-muted small mb-0">
                            Todas las solicitudes son revisadas por un operador y pueden tardar hasta 48 horas en ser procesadas.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar/ocultar selector de control según el tipo de solicitud
document.getElementById('tipoSolicitud').addEventListener('change', function() {
    const tipo = this.value;
    const controlSelector = document.getElementById('controlSelector');

    // Tipos que requieren seleccionar un control específico
    const tiposConControl = ['desincorporar_control', 'reportar_perdido'];

    if (tiposConControl.includes(tipo)) {
        controlSelector.style.display = 'block';
        document.getElementById('controlId').required = true;
    } else {
        controlSelector.style.display = 'none';
        document.getElementById('controlId').required = false;
    }

    // Cambiar placeholder según el tipo
    const descripcion = document.getElementById('descripcion');
    switch(tipo) {
        case 'desincorporar_control':
            descripcion.placeholder = 'Explique por qué desea desincorporar este control...';
            break;
        case 'reportar_perdido':
            descripcion.placeholder = 'Describa las circunstancias de la pérdida del control...';
            break;
        case 'agregar_control':
            descripcion.placeholder = 'Explique por qué necesita un control adicional...';
            break;
        case 'comprar_control':
            descripcion.placeholder = 'Indique cuántos controles desea comprar...';
            break;
        default:
            descripcion.placeholder = 'Describa detalladamente su solicitud...';
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>