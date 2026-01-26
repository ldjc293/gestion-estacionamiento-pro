<?php
$pageTitle = 'Solicitudes de Cambios';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('operador/dashboard')],
    ['label' => 'Solicitudes', 'url' => '#']
];

// Incluir helper de tipos de solicitud
require_once __DIR__ . '/../../../helpers/SolicitudHelper.php';

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Solicitudes de Cambios Pendientes
                </h6>
                <span class="badge bg-warning" style="font-size: 14px;">
                    <?= is_array($solicitudes) ? count($solicitudes) : 0 ?> pendientes
                </span>
            </div>
            <div class="card-body">
                <?php if (!is_array($solicitudes) || count($solicitudes) === 0): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-check-circle text-success" style="font-size: 80px;"></i>
                        <h5 class="mt-3">¡Todo en orden!</h5>
                        <p class="text-muted">No hay solicitudes de cambios pendientes</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Importante:</strong> Revisa cada solicitud cuidadosamente antes de aprobar o rechazar.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">ID</th>
                                    <th style="width: 200px;">Solicitante</th>
                                    <th>Tipo de Solicitud</th>
                                    <th style="width: 150px;">Fecha</th>
                                    <th style="width: 280px;" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (is_array($solicitudes) ? $solicitudes : [] as $solicitud): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">#<?= str_pad($solicitud['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-circle text-muted me-2" style="font-size: 1.5rem;"></i>
                                                <div>
                                                    <strong class="d-block"><?= htmlspecialchars($solicitud['solicitante_nombre']) ?></strong>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($solicitud['apartamento_bloque'] ?? '') ?>-<?= htmlspecialchars($solicitud['apartamento_escalera'] ?? '') ?>-<?= htmlspecialchars($solicitud['apartamento_piso'] ?? '') ?>-<?= htmlspecialchars($solicitud['apartamento_numero'] ?? '') ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $tipoInfo = SolicitudHelper::getTipoInfo($solicitud['tipo_solicitud']);
                                            ?>
                                            <span class="badge bg-<?= $tipoInfo['color'] ?>">
                                                <i class="bi bi-<?= $tipoInfo['icon'] ?>"></i> <?= $tipoInfo['label'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($solicitud['fecha_solicitud'])) ?><br>
                                                <i class="bi bi-clock"></i> <?= date('H:i', strtotime($solicitud['fecha_solicitud'])) ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-info"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalVerDetallesSolicitud<?= $solicitud['id'] ?>"
                                                        title="Ver detalles completos">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalAprobarSolicitud<?= $solicitud['id'] ?>"
                                                        title="Aprobar solicitud">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalRechazarSolicitud<?= $solicitud['id'] ?>"
                                                        title="Rechazar solicitud">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- MODALES - Fuera de la tabla -->
                    <?php foreach (is_array($solicitudes) ? $solicitudes : [] as $solicitud): ?>
                        <!-- Modal Ver Detalles Solicitud -->
                        <div class="modal fade" id="modalVerDetallesSolicitud<?= $solicitud['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $solicitud['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalLabel<?= $solicitud['id'] ?>">
                                            <i class="bi bi-eye text-info"></i> Detalles de Solicitud #<?= str_pad($solicitud['id'], 5, '0', STR_PAD_LEFT) ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="bi bi-person"></i> Información del Solicitante</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <td><strong>Nombre:</strong></td>
                                                        <td><?= htmlspecialchars($solicitud['solicitante_nombre']) ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Email:</strong></td>
                                                        <td><?= htmlspecialchars($solicitud['solicitante_email'] ?? 'N/A') ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Teléfono:</strong></td>
                                                        <td><?= htmlspecialchars($solicitud['solicitante_telefono'] ?? 'N/A') ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="bi bi-building"></i> Información del Apartamento</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <td><strong>Bloque:</strong></td>
                                                        <td><?= htmlspecialchars($solicitud['apartamento_bloque'] ?? 'N/A') ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Escalera:</strong></td>
                                                        <td><?= htmlspecialchars($solicitud['apartamento_escalera'] ?? 'N/A') ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Piso:</strong></td>
                                                        <td><?= htmlspecialchars($solicitud['apartamento_piso'] ?? 'N/A') ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Número:</strong></td>
                                                        <td><?= htmlspecialchars($solicitud['apartamento_numero'] ?? 'N/A') ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="bi bi-tag"></i> Detalles de la Solicitud</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <td><strong>ID:</strong></td>
                                                        <td>#<?= str_pad($solicitud['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Tipo:</strong></td>
                                                        <td>
                                                            <?php
                                                            $tipoSolicitud = $solicitud['tipo_solicitud'] ?? '';
                                                            if (empty($tipoSolicitud)) {
                                                                echo '<span class="text-danger">No especificado</span>';
                                                            } else {
                                                                echo SolicitudHelper::getLabel($tipoSolicitud);
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Estado:</strong></td>
                                                        <td>
                                                            <span class="badge bg-warning">Pendiente</span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Fecha:</strong></td>
                                                        <td><?= date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])) ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="bi bi-controller"></i> Información del Control</h6>
                                                <?php if ($solicitud['control_id']): ?>
                                                    <table class="table table-sm">
                                                        <tr>
                                                            <td><strong>Número:</strong></td>
                                                            <td><?= htmlspecialchars($solicitud['control_numero'] ?? 'N/A') ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Estado Actual:</strong></td>
                                                            <td>
                                                                <?php
                                                                $estadoBadge = [
                                                                    'activo' => 'success',
                                                                    'suspendido' => 'warning',
                                                                    'desactivado' => 'secondary',
                                                                    'perdido' => 'danger',
                                                                    'bloqueado' => 'dark',
                                                                    'vacio' => 'light'
                                                                ];
                                                                $colorBadge = $estadoBadge[$solicitud['control_estado']] ?? 'secondary';
                                                                ?>
                                                                <span class="badge bg-<?= $colorBadge ?>">
                                                                    <?= ucfirst($solicitud['control_estado'] ?? 'desconocido') ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Asignado:</strong></td>
                                                            <td><?= $solicitud['control_fecha_asignacion'] ? date('d/m/Y', strtotime($solicitud['control_fecha_asignacion'])) : 'N/A' ?></td>
                                                        </tr>
                                                    </table>
                                                <?php else: ?>
                                                    <p class="text-muted">Esta solicitud no está asociada a un control específico.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <hr>

                                        <div>
                                            <h6><i class="bi bi-chat-quote"></i> Descripción/Motivo</h6>
                                            <div class="bg-light p-3 rounded border">
                                                <p class="mb-0">
                                                    <?php 
                                                    $motivo = $solicitud['motivo'] ?? '';
                                                    if (empty($motivo)) {
                                                        echo '<span class="text-danger">No se proporcionó descripción</span>';
                                                    } else {
                                                        echo nl2br(htmlspecialchars($motivo));
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <i class="bi bi-x"></i> Cerrar
                                        </button>
                                        <div class="ms-auto">
                                            <button type="button" class="btn btn-success me-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalAprobarSolicitud<?= $solicitud['id'] ?>"
                                                    data-bs-dismiss="modal">
                                                <i class="bi bi-check-circle"></i> Aprobar
                                            </button>
                                            <button type="button" class="btn btn-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalRechazarSolicitud<?= $solicitud['id'] ?>"
                                                    data-bs-dismiss="modal">
                                                <i class="bi bi-x-circle"></i> Rechazar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Aprobar Solicitud -->
                        <div class="modal fade" id="modalAprobarSolicitud<?= $solicitud['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="<?= url('operador/process-solicitud') ?>">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="solicitud_id" value="<?= $solicitud['id'] ?>">
                                        <input type="hidden" name="accion" value="aprobar">

                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="bi bi-check-circle text-success"></i> Aprobar Solicitud
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-success">
                                                <strong>Solicitud:</strong> <?= htmlspecialchars($solicitud['motivo']) ?>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Observaciones (opcional)</label>
                                                <textarea class="form-control" name="observaciones" rows="3"
                                                        placeholder="Añade cualquier observación relevante..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-check-circle"></i> Confirmar Aprobación
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Rechazar Solicitud -->
                        <div class="modal fade" id="modalRechazarSolicitud<?= $solicitud['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="<?= url('operador/process-solicitud') ?>">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="solicitud_id" value="<?= $solicitud['id'] ?>">
                                        <input type="hidden" name="accion" value="rechazar">

                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="bi bi-x-circle text-danger"></i> Rechazar Solicitud
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-warning">
                                                <strong>Solicitud:</strong> <?= htmlspecialchars($solicitud['motivo']) ?>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Motivo del rechazo <span class="text-danger">*</span></label>
                                                <textarea class="form-control" name="observaciones" rows="3"
                                                        placeholder="Explica por qué se rechaza esta solicitud..." required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-danger">
                                                <i class="bi bi-x-circle"></i> Confirmar Rechazo
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Asegurar funcionalidad correcta de modales
document.addEventListener('DOMContentLoaded', function() {
    // Limpiar backdrops huérfanos cuando se cierran modales
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            // Limpiar backdrops huérfanos
            setTimeout(() => {
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                    if (!document.querySelector('.modal.show')) {
                        backdrop.remove();
                    }
                });
            }, 100);
        });

        // Asegurar que el contenido del modal se muestre correctamente al abrirse
        modal.addEventListener('show.bs.modal', function() {
            // Forzar reflow para asegurar que el contenido se muestre
            const modalBody = this.querySelector('.modal-body');
            if (modalBody) {
                modalBody.style.display = 'block';
                modalBody.offsetHeight; // Trigger reflow
            }
        });
    });

    // Asegurar que los botones de cerrar funcionen correctamente
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = this.closest('.modal');
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
        });
    });

    // Prevenir conflictos entre modales anidados
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(trigger => {
        trigger.addEventListener('click', function() {
            // Cerrar cualquier modal abierto antes de abrir uno nuevo
            const openModal = document.querySelector('.modal.show');
            if (openModal && openModal !== document.querySelector(this.getAttribute('data-bs-target'))) {
                const bsModal = bootstrap.Modal.getInstance(openModal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>