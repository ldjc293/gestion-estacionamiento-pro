<?php
/**
 * Vista: Gestionar Solicitudes de Registro
 * Para: Administradores
 */

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    redirect('auth/login');
}

$pageTitle = 'Gestionar Solicitudes de Registro';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/topbar.php';
?>

<div class="main-content">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-person-check"></i> Solicitudes de Registro Pendientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($solicitudesPendientes)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No hay solicitudes de registro pendientes
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Email</th>
                                            <th>Teléfono</th>
                                            <th>Apartamento</th>
                                            <th>Controles</th>
                                            <th>Fecha Solicitud</th>
                                            <th style="min-width: 140px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($solicitudesPendientes as $solicitud): ?>
                                            <?php $datos = $solicitud->getDatosNuevoUsuario(); ?>
                                            <tr id="solicitud-<?= $solicitud->id ?>">
                                                <td><?= $solicitud->id ?></td>
                                                <td><?= htmlspecialchars($datos['nombre_completo']) ?></td>
                                                <td><?= htmlspecialchars($datos['email']) ?></td>
                                                <td><?= htmlspecialchars($datos['telefono'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?= $datos['bloque'] ?>-<?= $datos['escalera'] ?>-<?= $datos['apartamento'] ?>
                                                    <small class="text-muted">(Piso <?= $datos['piso'] ?>)</small>
                                                </td>
                                                <td><?= $datos['cantidad_controles'] ?></td>
                                                <td><?= formatDateTime($solicitud->fecha_solicitud) ?></td>
                                                <td class="text-nowrap">
                                                    <button class="btn btn-sm btn-info" onclick="verDetalles(<?= $solicitud->id ?>)" title="Ver detalles">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-success" onclick="aprobar(<?= $solicitud->id ?>)" title="Aprobar">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="rechazar(<?= $solicitud->id ?>)" title="Rechazar">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
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
        </div>
    </div>
</div>

<!-- Modal: Ver Detalles -->
<div class="modal fade" id="detallesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de la Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detallesContent">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>
</div>

<!-- Modal: Rechazar -->
<div class="modal fade" id="rechazarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rechazar Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rechazarSolicitudId">
                <div class="mb-3">
                    <label for="motivoRechazo" class="form-label">Motivo del Rechazo *</label>
                    <textarea class="form-control" id="motivoRechazo" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmarRechazo()">Rechazar</button>
            </div>
        </div>
    </div>
</div>

<script>
const solicitudesData = <?= json_encode(array_map(function($s) {
    return [
        'id' => $s->id,
        'datos' => $s->getDatosNuevoUsuario()
    ];
}, $solicitudesPendientes)) ?>;

function verDetalles(id) {
    const solicitud = solicitudesData.find(s => s.id === id);
    if (!solicitud) return;

    const datos = solicitud.datos;
    const html = `
        <div class="row">
            <div class="col-md-6">
                <h6>Información Personal</h6>
                <p><strong>Nombre:</strong> ${datos.nombre_completo}</p>
                <p><strong>Email:</strong> ${datos.email}</p>
                <p><strong>Teléfono:</strong> ${datos.telefono || 'N/A'}</p>
            </div>
            <div class="col-md-6">
                <h6>Apartamento</h6>
                <p><strong>Bloque:</strong> ${datos.bloque}</p>
                <p><strong>Escalera:</strong> ${datos.escalera}</p>
                <p><strong>Piso:</strong> ${datos.piso}</p>
                <p><strong>Apartamento:</strong> ${datos.apartamento}</p>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>Controles</h6>
                <p><strong>Cantidad:</strong> ${datos.cantidad_controles}</p>
            </div>
        </div>
        ${datos.comentarios ? `
        <div class="row mt-3">
            <div class="col-12">
                <h6>Comentarios</h6>
                <p>${datos.comentarios}</p>
            </div>
        </div>
        ` : ''}
    `;

    document.getElementById('detallesContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('detallesModal')).show();
}

function aprobar(id) {
    if (!confirm('¿Está seguro de aprobar esta solicitud? Se creará el usuario automáticamente.')) {
        return;
    }

    fetch('<?= url('admin-solicitudes/aprobar-solicitud') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `solicitud_id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            document.getElementById(`solicitud-${id}`).remove();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}

function rechazar(id) {
    document.getElementById('rechazarSolicitudId').value = id;
    document.getElementById('motivoRechazo').value = '';
    new bootstrap.Modal(document.getElementById('rechazarModal')).show();
}

function confirmarRechazo() {
    const id = document.getElementById('rechazarSolicitudId').value;
    const motivo = document.getElementById('motivoRechazo').value.trim();

    if (!motivo) {
        alert('Debe proporcionar un motivo de rechazo');
        return;
    }

    fetch('<?= url('admin-solicitudes/rechazar-solicitud') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `solicitud_id=${id}&motivo=${encodeURIComponent(motivo)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            document.getElementById(`solicitud-${id}`).remove();
            bootstrap.Modal.getInstance(document.getElementById('rechazarModal')).hide();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
