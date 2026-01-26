<?php
/**
 * Vista: Gestionar Solicitudes
 * Para: Administradores y Operadores
 * Muestra todas las solicitudes pendientes de todos los tipos
 */

// Incluir helper de tipos de solicitud
require_once __DIR__ . '/../../../helpers/SolicitudHelper.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    redirect('auth/login');
}

$pageTitle = 'Gestionar Solicitudes';
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';
require_once __DIR__ . '/../../layouts/topbar.php';
?>

<div class="main-content">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-inbox"></i> Solicitudes Pendientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($solicitudesPendientes)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No hay solicitudes pendientes
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tipo</th>
                                            <th>Usuario/Solicitante</th>
                                            <th>Apartamento</th>
                                            <th>Detalles</th>
                                            <th>Fecha Solicitud</th>
                                            <th style="min-width: 140px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($solicitudesPendientes as $solicitud): ?>
                                            <tr id="solicitud-<?= $solicitud->id ?>">
                                                <td><?= $solicitud->id ?></td>
                                                <td>
                                                    <span class="badge bg-<?= getBadgeColorForTipo($solicitud->tipo_solicitud) ?>">
                                                        <?= SolicitudHelper::getLabel($solicitud->tipo_solicitud) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($solicitud->tipo_solicitud === 'registro_nuevo_usuario'): ?>
                                                        <?php $datos = $solicitud->getDatosNuevoUsuario(); ?>
                                                        <strong><?= htmlspecialchars($datos['nombre_completo']) ?></strong><br>
                                                        <small class="text-muted"><?= htmlspecialchars($datos['email']) ?></small>
                                                    <?php else: ?>
                                                        <?php
                                                        // Obtener información del usuario
                                                        $sql = "SELECT u.nombre_completo, u.email 
                                                                FROM apartamento_usuario au 
                                                                JOIN usuarios u ON u.id = au.usuario_id 
                                                                WHERE au.id = ?";
                                                        $usuario = Database::fetchOne($sql, [$solicitud->apartamento_usuario_id]);
                                                        ?>
                                                        <strong><?= htmlspecialchars($usuario['nombre_completo'] ?? 'N/A') ?></strong><br>
                                                        <small class="text-muted"><?= htmlspecialchars($usuario['email'] ?? 'N/A') ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($solicitud->tipo_solicitud === 'registro_nuevo_usuario'): ?>
                                                        <?php $datos = $solicitud->getDatosNuevoUsuario(); ?>
                                                        <?= $datos['bloque'] ?>-<?= $datos['escalera'] ?>-<?= $datos['apartamento'] ?>
                                                        <small class="text-muted">(Piso <?= $datos['piso'] ?>)</small>
                                                    <?php else: ?>
                                                        <?php
                                                        $sql = "SELECT a.bloque, a.escalera, a.piso, a.numero_apartamento 
                                                                FROM apartamento_usuario au 
                                                                JOIN apartamentos a ON a.id = au.apartamento_id 
                                                                WHERE au.id = ?";
                                                        $apto = Database::fetchOne($sql, [$solicitud->apartamento_usuario_id]);
                                                        ?>
                                                        <?= $apto['bloque'] ?? 'N/A' ?>-<?= $apto['escalera'] ?? 'N/A' ?>-<?= $apto['numero_apartamento'] ?? 'N/A' ?>
                                                        <small class="text-muted">(Piso <?= $apto['piso'] ?? 'N/A' ?>)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($solicitud->tipo_solicitud === 'registro_nuevo_usuario'): ?>
                                                        <?php $datos = $solicitud->getDatosNuevoUsuario(); ?>
                                                        <small>Controles: <?= $datos['cantidad_controles'] ?></small>
                                                    <?php elseif ($solicitud->tipo_solicitud === 'cambio_cantidad_controles'): ?>
                                                        <small>Nueva cantidad: <?= $solicitud->cantidad_controles_nueva ?></small>
                                                    <?php elseif (in_array($solicitud->tipo_solicitud, ['suspension_control', 'desactivacion_control', 'desincorporar_control', 'reportar_perdido'])): ?>
                                                        <?php
                                                        $sql = "SELECT numero_control_completo FROM controles_estacionamiento WHERE id = ?";
                                                        $control = Database::fetchOne($sql, [$solicitud->control_id]);
                                                        ?>
                                                        <small>Control: <?= $control['numero_control_completo'] ?? 'N/A' ?></small>
                                                    <?php endif; ?>
                                                    <?php if (!empty($solicitud->motivo)): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars(substr($solicitud->motivo, 0, 50)) ?><?= strlen($solicitud->motivo) > 50 ? '...' : '' ?></small>
                                                    <?php endif; ?>
                                                </td>
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

<?php
// Función helper para obtener el color del badge según el tipo
function getBadgeColorForTipo($tipo) {
    switch ($tipo) {
        case 'registro_nuevo_usuario':
            return 'primary';
        case 'cambio_cantidad_controles':
            return 'info';
        case 'suspension_control':
            return 'warning';
        case 'desactivacion_control':
            return 'danger';
        case 'desincorporar_control':
            return 'secondary';
        case 'reportar_perdido':
            return 'warning';
        case 'agregar_control':
            return 'success';
        case 'comprar_control':
            return 'primary';
        case 'solicitud_personalizada':
            return 'info';
        case 'cambio_estado_control':
            return 'secondary';
        default:
            return 'secondary';
    }
}
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Definir constantes de tipos de solicitud para JavaScript usando el helper
const TIPOS_SOLICITUD_JS = <?= json_encode(SolicitudHelper::getTiposSolicitud()) ?>;

const solicitudesData = <?= json_encode(array_map(function($s) {
    $data = [
        'id' => $s->id,
        'tipo' => $s->tipo_solicitud,
        'fecha' => $s->fecha_solicitud,
        'motivo' => $s->motivo
    ];
    
    if ($s->tipo_solicitud === 'registro_nuevo_usuario') {
        $data['datos'] = $s->getDatosNuevoUsuario();
    } else {
        // Obtener datos del usuario y apartamento
        $sql = "SELECT u.nombre_completo, u.email, a.bloque, a.escalera, a.piso, a.numero_apartamento
                FROM apartamento_usuario au
                JOIN usuarios u ON u.id = au.usuario_id
                JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE au.id = ?";
        $info = Database::fetchOne($sql, [$s->apartamento_usuario_id]);
        $data['usuario'] = $info;
        
        if ($s->tipo_solicitud === 'cambio_cantidad_controles') {
            $data['cantidad_nueva'] = $s->cantidad_controles_nueva;
        } elseif (in_array($s->tipo_solicitud, ['suspension_control', 'desactivacion_control', 'desincorporar_control', 'reportar_perdido'])) {
            $sql = "SELECT numero_control_completo FROM controles_estacionamiento WHERE id = ?";
            $control = Database::fetchOne($sql, [$s->control_id]);
            $data['control'] = $control['numero_control_completo'] ?? 'N/A';
        }
    }
    
    return $data;
}, $solicitudesPendientes)) ?>;

function verDetalles(id) {
    const solicitud = solicitudesData.find(s => s.id === id);
    if (!solicitud) return;

    let html = `<div class="row">`;
    
    if (solicitud.tipo === 'registro_nuevo_usuario') {
        const datos = solicitud.datos;
        html += `
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
                <p><strong>Controles:</strong> ${datos.cantidad_controles}</p>
            </div>
        `;
        if (datos.comentarios) {
            html += `
                <div class="col-12 mt-3">
                    <h6>Comentarios</h6>
                    <p>${datos.comentarios}</p>
                </div>
            `;
        }
    } else {
        const usuario = solicitud.usuario;
        html += `
            <div class="col-md-6">
                <h6>Usuario</h6>
                <p><strong>Nombre:</strong> ${usuario.nombre_completo}</p>
                <p><strong>Email:</strong> ${usuario.email}</p>
            </div>
            <div class="col-md-6">
                <h6>Apartamento</h6>
                <p><strong>Ubicación:</strong> ${usuario.bloque}-${usuario.escalera}-${usuario.numero_apartamento} (Piso ${usuario.piso})</p>
            </div>
            <div class="col-12 mt-3">
                <h6>Detalles de la Solicitud</h6>
                <p><strong>Tipo:</strong> ${TIPOS_SOLICITUD_JS[solicitud.tipo] || solicitud.tipo}</p>
        `;
        
        if (solicitud.tipo === 'cambio_cantidad_controles') {
            html += `<p><strong>Nueva cantidad de controles:</strong> ${solicitud.cantidad_nueva}</p>`;
        } else if (solicitud.control) {
            html += `<p><strong>Control:</strong> ${solicitud.control}</p>`;
        }
        
        if (solicitud.motivo) {
            html += `<p><strong>Motivo:</strong> ${solicitud.motivo}</p>`;
        }
        
        html += `</div>`;
    }
    
    html += `</div>`;

    document.getElementById('detallesContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('detallesModal')).show();
}

function aprobar(id) {
    const solicitud = solicitudesData.find(s => s.id === id);

    // Si es solicitud de registro nuevo usuario, mostrar modal de asignación de controles
    if (solicitud && solicitud.tipo === 'registro_nuevo_usuario') {
        mostrarModalAsignacionControles(id, solicitud);
        return;
    }

    // Para otros tipos de solicitud, proceder normalmente
    Swal.fire({
        title: '¿Aprobar solicitud?',
        text: "Esta acción procesará la solicitud y aplicará los cambios necesarios.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            procesarAprobacion(id);
        }
    });
}

function mostrarModalAsignacionControles(solicitudId, solicitud) {
    const datos = solicitud.datos;

    // Crear modal HTML
    const modalHtml = `
        <div class="modal fade" id="modalAsignacionControles" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Asignar Controles - Nuevo Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formAsignacionControles">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="solicitud_id" value="${solicitudId}">

                            <!-- Información del usuario -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6>Información del Usuario</h6>
                                    <p><strong>Nombre:</strong> ${datos.nombre_completo}</p>
                                    <p><strong>Email:</strong> ${datos.email}</p>
                                    <p><strong>Teléfono:</strong> ${datos.telefono || 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Apartamento Solicitado</h6>
                                    <p><strong>Ubicación:</strong> ${datos.bloque}-${datos.escalera}-${datos.apartamento} (Piso ${datos.piso})</p>
                                    <p><strong>Controles solicitados:</strong> ${datos.cantidad_controles}</p>
                                </div>
                            </div>

                            <!-- Modificar cantidad de controles -->
                            <div class="mb-3">
                                <label for="cantidadControles" class="form-label">Cantidad de Controles *</label>
                                <input type="number" class="form-control" id="cantidadControles" name="cantidad_controles"
                                       value="${datos.cantidad_controles}" min="1" max="10" required>
                                <div class="form-text">Modifique si es necesario cambiar la cantidad solicitada</div>
                            </div>

                            <!-- Modificar datos del apartamento (opcional) -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="bloque" class="form-label">Bloque</label>
                                    <input type="text" class="form-control" id="bloque" name="bloque" value="${datos.bloque}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="escalera" class="form-label">Escalera</label>
                                    <input type="text" class="form-control" id="escalera" name="escalera" value="${datos.escalera}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="apartamento" class="form-label">Apartamento</label>
                                    <input type="text" class="form-control" id="apartamento" name="apartamento" value="${datos.apartamento}" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="piso" class="form-label">Piso</label>
                                <input type="number" class="form-control" id="piso" name="piso" value="${datos.piso}" required>
                            </div>

                            <!-- Selección de controles específicos -->
                            <div class="mb-3">
                                <label class="form-label">Seleccionar Controles Específicos</label>
                                <div id="controlesSeleccion" class="border p-3">
                                    <div class="text-center">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                        Seleccione la cantidad de controles primero...
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="confirmarAsignacionControles()">Aprobar y Asignar Controles</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalAsignacionControles'));
    modal.show();

    // Cargar controles disponibles
    cargarControlesDisponibles();

    // Limpiar modal cuando se cierre
    document.getElementById('modalAsignacionControles').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });

    // Actualizar controles disponibles cuando cambie la cantidad
    document.getElementById('cantidadControles').addEventListener('change', cargarControlesDisponibles);
}

function cargarControlesDisponibles() {
    const cantidad = parseInt(document.getElementById('cantidadControles').value);
    const contenedor = document.getElementById('controlesSeleccion');

    if (cantidad <= 0) {
        contenedor.innerHTML = '<div class="text-center text-muted">Seleccione la cantidad de controles primero...</div>';
        return;
    }

    contenedor.innerHTML = `
        <div class="text-center">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            Cargando controles disponibles...
        </div>
    `;

    fetch('<?= url('api/controles-disponibles') ?>?cantidad=' + cantidad)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="row">';

                // Crear un select para cada control que se va a asignar
                for (let i = 1; i <= cantidad; i++) {
                    html += `
                        <div class="col-md-6 mb-3">
                            <label for="control${i}" class="form-label">Control #${i}</label>
                            <select class="form-select control-select" name="controles[]" id="control${i}" required>
                                <option value="">-- Seleccionar control --</option>
                    `;

                    // Mostrar TODOS los controles disponibles en cada select
                    data.controles.forEach(control => {
                        html += `<option value="${control.id}">${control.numero_control_completo} (Pos. ${control.posicion_numero}, Rec. ${control.receptor})</option>`;
                    });

                    html += `
                            </select>
                        </div>
                    `;
                }

                html += '</div>';
                html += `<div class="alert alert-info"><small><i class="bi bi-info-circle"></i> Controles disponibles: ${data.cantidad_disponible}. Cada control debe ser único. Los controles seleccionados se asignarán automáticamente al usuario.</small></div>`;

                contenedor.innerHTML = html;

                // Agregar event listeners para validar selección duplicada
                document.querySelectorAll('.control-select').forEach(select => {
                    select.addEventListener('change', validarSeleccionUnica);
                });

            } else {
                contenedor.innerHTML = `<div class="alert alert-warning">${data.message || 'No hay suficientes controles disponibles para la cantidad solicitada.'}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            contenedor.innerHTML = '<div class="alert alert-danger">Error al cargar controles disponibles.</div>';
        });
}

function validarSeleccionUnica() {
    const selects = document.querySelectorAll('.control-select');
    const valoresSeleccionados = new Set();

    selects.forEach(select => {
        const valor = select.value;
        if (valor) {
            if (valoresSeleccionados.has(valor)) {
                select.setCustomValidity('Este control ya ha sido seleccionado');
                select.classList.add('is-invalid');
            } else {
                select.setCustomValidity('');
                select.classList.remove('is-invalid');
                valoresSeleccionados.add(valor);
            }
        } else {
            select.setCustomValidity('');
            select.classList.remove('is-invalid');
        }
    });
}

function confirmarAsignacionControles() {
    const form = document.getElementById('formAsignacionControles');
    const selects = document.querySelectorAll('.control-select');
    const cantidadSolicitada = parseInt(document.getElementById('cantidadControles').value);

    // Validar que todos los selects tengan un valor seleccionado
    let controlesSeleccionados = [];
    let controlesValidos = true;

    selects.forEach(select => {
        const valor = select.value;
        if (!valor) {
            controlesValidos = false;
            select.classList.add('is-invalid');
        } else {
            select.classList.remove('is-invalid');
            controlesSeleccionados.push(valor);
        }
    });

    if (!controlesValidos) {
        Swal.fire('Atención', 'Debe seleccionar un control para cada campo requerido', 'warning');
        return;
    }

    // Validar que no haya controles duplicados
    const controlesUnicos = new Set(controlesSeleccionados);
    if (controlesUnicos.size !== controlesSeleccionados.length) {
        Swal.fire('Atención', 'No puede seleccionar el mismo control más de una vez', 'warning');
        return;
    }

    // Ocultar modal
    bootstrap.Modal.getInstance(document.getElementById('modalAsignacionControles')).hide();

    // Mostrar loading
    Swal.fire({
        title: 'Procesando...',
        text: 'Creando usuario y asignando controles específicos',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Preparar datos para enviar
    const formData = new FormData(form);
    const data = {
        csrf_token: formData.get('csrf_token'),
        solicitud_id: formData.get('solicitud_id'),
        cantidad_controles: cantidadSolicitada,
        controles: controlesSeleccionados,
        bloque: formData.get('bloque'),
        escalera: formData.get('escalera'),
        apartamento: formData.get('apartamento'),
        piso: formData.get('piso')
    };

    // Determinar la ruta según el rol del usuario
    const isOperador = '<?= $_SESSION['user_rol'] ?>' === 'operador';
    const url = isOperador ? '<?= url('operador/aprobar-solicitud-registro') ?>' : '<?= url('admin/aprobar-solicitud-registro') ?>';

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Aprobada!',
                text: 'Usuario creado exitosamente y controles asignados',
                icon: 'success'
            }).then(() => {
                // Remover fila y recargar si es necesario
                const solicitudId = formData.get('solicitud_id');
                const row = document.getElementById(`solicitud-${solicitudId}`);
                if (row) {
                    row.style.transition = 'all 0.5s';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        if (document.querySelectorAll('tbody tr').length === 0) {
                            location.reload();
                        }
                    }, 500);
                }
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Ocurrió un error al procesar la solicitud', 'error');
    });
}

function procesarAprobacion(id) {
    // Mostrar loading
    Swal.fire({
        title: 'Procesando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Determinar la ruta según el rol del usuario
    const isOperador = '<?= $_SESSION['user_rol'] ?>' === 'operador';
    const url = isOperador ? '<?= url('operador/process-solicitud') ?>' : '<?= url('admin/aprobar-solicitud') ?>';

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `solicitud_id=${id}&accion=aprobar&csrf_token=<?= $_SESSION['csrf_token'] ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Aprobada!',
                text: data.message,
                icon: 'success'
            }).then(() => {
                // Remover fila y recargar si es necesario
                const row = document.getElementById(`solicitud-${id}`);
                if (row) {
                    row.style.transition = 'all 0.5s';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        if (document.querySelectorAll('tbody tr').length === 0) {
                            location.reload();
                        }
                    }, 500);
                }
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Ocurrió un error al procesar la solicitud', 'error');
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
        Swal.fire('Atención', 'Debe proporcionar un motivo de rechazo', 'warning');
        return;
    }

    // Ocultar modal
    bootstrap.Modal.getInstance(document.getElementById('rechazarModal')).hide();

    // Mostrar loading
    Swal.fire({
        title: 'Procesando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Determinar la ruta según el rol del usuario
    const isOperador = '<?= $_SESSION['user_rol'] ?>' === 'operador';
    const url = isOperador ? '<?= url('operador/process-solicitud') ?>' : '<?= url('admin/rechazar-solicitud') ?>';

    const bodyData = isOperador
        ? `solicitud_id=${id}&accion=rechazar&observaciones=${encodeURIComponent(motivo)}&csrf_token=<?= $_SESSION['csrf_token'] ?>`
        : `solicitud_id=${id}&motivo=${encodeURIComponent(motivo)}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: bodyData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Rechazada!',
                text: data.message,
                icon: 'success'
            }).then(() => {
                const row = document.getElementById(`solicitud-${id}`);
                if (row) {
                    row.style.transition = 'all 0.5s';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        if (document.querySelectorAll('tbody tr').length === 0) {
                            location.reload();
                        }
                    }, 500);
                }
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Ocurrió un error al procesar la solicitud', 'error');
    });
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
