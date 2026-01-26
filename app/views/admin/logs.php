<?php
$pageTitle = 'Logs del Sistema';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Logs', 'url' => '#']
];

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
                    <i class="bi bi-journal-text"></i> Logs de Actividad
                </h6>
                <div class="btn-group btn-group-sm">
                    <button onclick="exportarLogs()" class="btn btn-outline-primary">
                        <i class="bi bi-download"></i> Exportar
                    </button>
                    <button onclick="limpiarLogs()" class="btn btn-outline-danger">
                        <i class="bi bi-trash"></i> Limpiar Logs Antiguos
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Módulo</label>
                        <select name="modulo" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php if (isset($modulos)): ?>
                                <?php foreach ($modulos as $mod): ?>
                                    <?php if (!empty($mod['modulo'])): ?>
                                        <option value="<?= htmlspecialchars($mod['modulo']) ?>"
                                            <?= ($_GET['modulo'] ?? '') === $mod['modulo'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(ucfirst($mod['modulo'])) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Buscar</label>
                        <input type="text"
                               name="buscar"
                               class="form-control form-control-sm"
                               placeholder="Buscar en acciones..."
                               value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha</label>
                        <input type="date"
                               name="fecha"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars($_GET['fecha'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <a href="<?= url('admin/logs') ?>" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                    </div>
                </form>

                <!-- Tabla de Logs -->
                <?php if (empty($logs)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 80px;"></i>
                        <h5 class="mt-3">No hay logs</h5>
                        <p class="text-muted">No se encontraron registros con los filtros seleccionados</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th width="140">Fecha/Hora</th>
                                    <th width="100">Módulo</th>
                                    <th>Acción</th>
                                    <th width="180">Usuario</th>
                                    <th width="120">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><small class="text-muted">#<?= $log['id'] ?></small></td>
                                        <td>
                                            <small><?= date('d/m/Y H:i:s', strtotime($log['fecha_hora'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['modulo'])): ?>
                                                <span class="badge bg-info">
                                                    <?= htmlspecialchars(ucfirst($log['modulo'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($log['accion'] ?? 'Sin descripción') ?></small>
                                            <?php if (!empty($log['tabla_afectada'])): ?>
                                                <br><small class="text-muted">
                                                    <i class="bi bi-table"></i> <?= htmlspecialchars($log['tabla_afectada']) ?>
                                                    <?php if (!empty($log['registro_id'])): ?>
                                                        (ID: <?= $log['registro_id'] ?>)
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['usuario_nombre'])): ?>
                                                <small>
                                                    <i class="bi bi-person"></i>
                                                    <?= htmlspecialchars($log['usuario_nombre']) ?>
                                                </small>
                                                <?php if (!empty($log['usuario_email'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($log['usuario_email']) ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Sistema</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['ip_address'])): ?>
                                                <code style="font-size: 11px;"><?= htmlspecialchars($log['ip_address']) ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 text-muted small">
                        <i class="bi bi-info-circle"></i>
                        Mostrando <?= count($logs) ?> registros (máximo 500)
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<JS
<script>
function exportarLogs() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = URL_BASE + '/admin/exportLogs?' + params.toString();
}

function limpiarLogs() {
    const dias = prompt('¿Cuántos días de logs desea conservar?', '30');

    if (dias !== null && dias > 0) {
        if (confirm('¿Eliminar logs anteriores a ' + dias + ' días? Esta acción no se puede deshacer.')) {
            fetch(URL_BASE + '/admin/limpiarLogs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: '<?= generateCSRFToken() ?>',
                    dias: parseInt(dias)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Logs eliminados correctamente');
                    location.reload();
                } else {
                    alert(data.message || 'Error al eliminar logs');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        }
    }
}
</script>
JS;
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
