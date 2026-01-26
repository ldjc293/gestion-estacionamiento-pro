<?php
$pageTitle = 'Mis Controles';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('cliente/dashboard')],
    ['label' => 'Mis Controles', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <!-- Resumen -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="bi bi-controller"></i>
                    </div>
                    <div class="value"><?= count($controles) ?></div>
                    <div class="label">Controles Asignados</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="value">
                        <?= count(array_filter($controles, fn($c) => $c['estado'] === 'activo')) ?>
                    </div>
                    <div class="label">Controles Activos</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                        <i class="bi bi-shield-x"></i>
                    </div>
                    <div class="value">
                        <?= count(array_filter($controles, fn($c) => $c['estado'] === 'bloqueado')) ?>
                    </div>
                    <div class="label">Controles Bloqueados</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-list-ul"></i> Detalle de Controles
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($controles)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-controller" style="font-size: 64px; color: #cbd5e1;"></i>
                        <p class="text-muted mt-3">No tienes controles asignados</p>
                        <p class="text-muted">Contacta a la administración para solicitar la asignación de controles</p>
                    </div>
                <?php else: ?>
                    <?php
                    // Agrupar controles por apartamento
                    $controlesAgrupados = [];
                    foreach ($controles as $control) {
                        $key = $control['apartamento_usuario_id'];
                        if (!isset($controlesAgrupados[$key])) {
                            $controlesAgrupados[$key] = [
                                'apartamento' => $apartamentos[$key] ?? null,
                                'controles' => []
                            ];
                        }
                        $controlesAgrupados[$key]['controles'][] = $control;
                    }
                    ?>

                    <?php foreach ($controlesAgrupados as $grupo): ?>
                        <?php $apto = $grupo['apartamento']; ?>
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="mb-0">
                                            <i class="bi bi-building"></i>
                                            Apartamento <?= $apto['bloque'] ?? '' ?>-<?= $apto['numero_apartamento'] ?? '' ?>
                                        </h6>
                                        <small class="text-muted">
                                            Escalera <?= $apto['escalera'] ?? '' ?>, Piso <?= $apto['piso'] ?? '' ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <span class="badge bg-primary">
                                            <?= count($grupo['controles']) ?> control(es)
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($grupo['controles'] as $control): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="border rounded p-3 h-100">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h5 class="mb-1">
                                                            <i class="bi bi-controller"></i>
                                                            Control <?= $control['numero_control_completo'] ?>
                                                        </h5>
                                                        <small class="text-muted">
                                                            Posición <?= $control['posicion_numero'] ?> - Receptor <?= $control['receptor'] ?>
                                                        </small>
                                                    </div>
                                                    <div>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm dropdown-toggle"
                                                                    type="button"
                                                                    id="estadoDropdown-<?= $control['id'] ?>"
                                                                    data-bs-toggle="dropdown"
                                                                    aria-expanded="false"
                                                                    style="<?php if ($control['estado'] === 'activo'): ?>background: #10b981; color: white;
                                                                    <?php elseif ($control['estado'] === 'bloqueado'): ?>background: #ef4444; color: white;
                                                                    <?php elseif ($control['estado'] === 'suspendido'): ?>background: #f59e0b; color: white;
                                                                    <?php else: ?>background: #6b7280; color: white;<?php endif; ?>">
                                                                <?php if ($control['estado'] === 'activo'): ?>
                                                                    <i class="bi bi-check-circle"></i> Activo
                                                                <?php elseif ($control['estado'] === 'bloqueado'): ?>
                                                                    <i class="bi bi-shield-x"></i> Bloqueado
                                                                <?php elseif ($control['estado'] === 'suspendido'): ?>
                                                                    <i class="bi bi-pause-circle"></i> Suspendido
                                                                <?php else: ?>
                                                                    <i class="bi bi-circle"></i> <?= ucfirst($control['estado']) ?>
                                                                <?php endif; ?>
                                                            </button>
                                                            <ul class="dropdown-menu" aria-labelledby="estadoDropdown-<?= $control['id'] ?>">
                                                                <li><h6 class="dropdown-header">Estado: <?= ucfirst($control['estado']) ?></h6></li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item small text-muted" href="#">
                                                                    <i class="bi bi-info-circle"></i>
                                                                    <?php if ($control['estado'] === 'activo'): ?>
                                                                        Control funciona normalmente
                                                                    <?php elseif ($control['estado'] === 'bloqueado'): ?>
                                                                        Bloqueado por morosidad. Regulariza tu situación
                                                                    <?php elseif ($control['estado'] === 'suspendido'): ?>
                                                                        Contacta a la administración para más detalles
                                                                    <?php else: ?>
                                                                        Contacta a la administración para más detalles
                                                                    <?php endif; ?>
                                                                </a></li>
                                                                <?php if ($control['estado'] === 'bloqueado'): ?>
                                                                    <li><a class="dropdown-item text-primary" href="<?= url('cliente/estado-cuenta') ?>">
                                                                        <i class="bi bi-credit-card"></i> Ver Estado de Cuenta
                                                                    </a></li>
                                                                <?php endif; ?>
                                                                <li><a class="dropdown-item text-info" href="#" onclick="mostrarModalContacto('<?= $control['numero_control_completo'] ?>')">
                                                                    <i class="bi bi-envelope"></i> Solicitar cambio de estado
                                                                </a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if ($control['fecha_asignacion']): ?>
                                                    <div class="mb-2">
                                                        <small class="text-muted">
                                                            <i class="bi bi-calendar-check"></i>
                                                            Asignado: <?= date('d/m/Y', strtotime($control['fecha_asignacion'])) ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($control['motivo_estado']): ?>
                                                    <div class="alert alert-sm alert-<?= $control['estado'] === 'bloqueado' ? 'danger' : 'info' ?> mb-0 mt-2">
                                                        <small>
                                                            <i class="bi bi-info-circle"></i>
                                                            <?= htmlspecialchars($control['motivo_estado']) ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>

                                              </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Información adicional -->
                    <div class="alert alert-info mt-4">
                        <h6><i class="bi bi-info-circle"></i> Información Importante</h6>
                        <ul class="mb-0">
                            <li><strong>Estado Activo:</strong> El control funciona normalmente</li>
                            <li><strong>Estado Bloqueado:</strong> El control ha sido bloqueado por morosidad. Regulariza tu situación para reactivarlo</li>
                            <li><strong>Estado Suspendido:</strong> El control está temporalmente suspendido. Contacta a la administración</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para solicitar cambio de estado -->
<div class="modal fade" id="modalContacto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-envelope"></i> Solicitar Cambio de Estado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('cliente/solicitar-cambio-estado') ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="control_numero" id="contacto_control_numero">

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Tu solicitud será revisada por el administrador y procesada a la brevedad posible.
                    </div>

                    <div class="mb-3">
                        <label for="contacto_control_numero_display" class="form-label fw-bold">
                            Control:
                        </label>
                        <input type="text"
                               class="form-control"
                               id="contacto_control_numero_display"
                               readonly
                               style="background: #f8f9fa;">
                    </div>

                    <div class="mb-3">
                        <label for="contacto_motivo" class="form-label fw-bold">
                            Motivo de la solicitud *
                        </label>
                        <select class="form-select" name="motivo_solicitud" required>
                            <option value="">Seleccione un motivo...</option>
                            <option value="reactivacion">Solicitud de reactivación</option>
                            <option value="problema_tecnico">Reportar problema técnico</option>
                            <option value="perdida">Reportar pérdida o daño</option>
                            <option value="cambio_dueno">Cambio de propietario</option>
                            <option value="otro">Otro (especificar)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="contacto_descripcion" class="form-label fw-bold">
                            Descripción detallada *
                        </label>
                        <textarea class="form-control"
                                  name="descripcion"
                                  id="contacto_descripcion"
                                  required
                                  rows="4"
                                  maxlength="500"
                                  placeholder="Por favor describe detalladamente el motivo de tu solicitud..."></textarea>
                        <small class="text-muted">Máximo 500 caracteres</small>
                    </div>

                    <div class="mb-3">
                        <label for="contacto_telefono" class="form-label fw-bold">
                            Teléfono de contacto (opcional)
                        </label>
                        <input type="tel"
                               class="form-control"
                               name="telefono"
                               id="contacto_telefono"
                               placeholder="Número donde podemos contactarte">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> Enviar Solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function mostrarModalContacto(numeroControl) {
    // Resetear el formulario
    const form = document.querySelector('#modalContacto form');
    if (form) {
        form.reset();
    }

    // Establecer valores específicos
    const controlNumeroInput = document.getElementById('contacto_control_numero');
    const controlNumeroDisplay = document.getElementById('contacto_control_numero_display');

    if (controlNumeroInput) {
        controlNumeroInput.value = numeroControl;
    }
    if (controlNumeroDisplay) {
        controlNumeroDisplay.value = numeroControl;
    }

    const modal = new bootstrap.Modal(document.getElementById('modalContacto'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
