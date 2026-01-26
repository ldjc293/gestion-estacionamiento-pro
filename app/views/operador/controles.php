<?php
$pageTitle = 'Mapa de Controles';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('operador/dashboard')],
    ['label' => 'Mapa de Controles', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <!-- Estadísticas Generales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-key text-primary" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $estadisticas['total'] ?? 0 ?></h3>
                        <small class="text-muted">Total Controles</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $estadisticas['activos'] ?? 0 ?></h3>
                        <small class="text-muted">Activos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-dash-circle text-warning" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $estadisticas['vacios'] ?? 0 ?></h3>
                        <small class="text-muted">Disponibles</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-lock text-danger" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $estadisticas['bloqueados'] ?? 0 ?></h3>
                        <small class="text-muted">Bloqueados</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestañas de Vista -->
        <ul class="nav nav-tabs mb-4" id="vistaControlTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="vista-usuarios-tab" data-bs-toggle="tab" data-bs-target="#vista-usuarios" type="button">
                    <i class="bi bi-people"></i> Vista por Usuario/Apartamento
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="vista-posiciones-tab" data-bs-toggle="tab" data-bs-target="#vista-posiciones" type="button">
                    <i class="bi bi-grid-3x3"></i> Vista por Posiciones
                </button>
            </li>
        </ul>

        <div class="tab-content" id="vistaControlTabsContent">
            <!-- Vista por Usuario/Apartamento -->
            <div class="tab-pane fade show active" id="vista-usuarios" role="tabpanel">
                <?php
                // Obtener controles agrupados por usuario/apartamento
                $sql = "SELECT
                            u.id as usuario_id,
                            u.nombre_completo,
                            u.email,
                            CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento,
                            a.id as apartamento_id,
                            au.id as apartamento_usuario_id,
                            au.cantidad_controles,
                            COUNT(c.id) as controles_asignados,
                            GROUP_CONCAT(c.numero_control_completo ORDER BY c.posicion_numero SEPARATOR ', ') as lista_controles,
                            SUM(CASE WHEN c.estado = 'activo' THEN 1 ELSE 0 END) as controles_activos,
                            SUM(CASE WHEN c.estado = 'bloqueado' THEN 1 ELSE 0 END) as controles_bloqueados
                        FROM apartamento_usuario au
                        JOIN usuarios u ON u.id = au.usuario_id
                        JOIN apartamentos a ON a.id = au.apartamento_id
                        LEFT JOIN controles_estacionamiento c ON c.apartamento_usuario_id = au.id
                        WHERE au.activo = TRUE
                        GROUP BY au.id, u.id, u.nombre_completo, u.email, a.bloque, a.numero_apartamento, a.id, au.cantidad_controles
                        ORDER BY a.bloque, a.numero_apartamento";

                $controlesUsuarios = Database::fetchAll($sql);
                ?>

                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-people"></i> Controles por Usuario/Apartamento
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($controlesUsuarios)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mb-0 mt-3">No hay apartamentos con residentes asignados</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Apartamento</th>
                                            <th>Residente</th>
                                            <th>Email</th>
                                            <th>Cantidad Asignada</th>
                                            <th>Controles Registrados</th>
                                            <th>Estado</th>
                                            <th>Controles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($controlesUsuarios as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($item['apartamento']) ?></strong>
                                                </td>
                                                <td>
                                                    <i class="bi bi-person"></i>
                                                    <?= htmlspecialchars($item['nombre_completo']) ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?= htmlspecialchars($item['email']) ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?= $item['cantidad_controles'] ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($item['controles_asignados'] > 0): ?>
                                                        <span class="badge bg-info"><?= $item['controles_asignados'] ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($item['controles_activos'] > 0): ?>
                                                        <span class="badge bg-success"><?= $item['controles_activos'] ?> Activo(s)</span>
                                                    <?php endif; ?>
                                                    <?php if ($item['controles_bloqueados'] > 0): ?>
                                                        <span class="badge bg-danger"><?= $item['controles_bloqueados'] ?> Bloqueado(s)</span>
                                                    <?php endif; ?>
                                                    <?php if ($item['controles_asignados'] == 0): ?>
                                                        <span class="text-muted">Sin controles</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <?php if (!empty($item['lista_controles'])): ?>
                                                            <small><?= htmlspecialchars($item['lista_controles']) ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                        <a href="#gestionModal"
                                                           class="btn btn-sm btn-outline-success ms-2"
                                                           title="Gestionar Controles"
                                                           onclick="abrirGestionControles(<?= $item['usuario_id'] ?>, '<?= htmlspecialchars($item['nombre_completo']) ?>', '<?= htmlspecialchars($item['apartamento']) ?>')">
                                                            <i class="bi bi-gear"></i>
                                                        </a>
                                                    </div>
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

            <!-- Vista por Posiciones -->
            <div class="tab-pane fade" id="vista-posiciones" role="tabpanel">
                <!-- Búsqueda rápida -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-search"></i> Búsqueda de Controles
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="<?= url('operador/controles') ?>" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="buscar" placeholder="Buscar por posición (Ej: 15, 150A, 250B)">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="estado">
                                    <option value="">Todos los estados</option>
                                    <option value="activo">Activos</option>
                                    <option value="vacio">Disponibles</option>
                                    <option value="bloqueado">Bloqueados</option>
                                    <option value="suspendido">Suspendidos</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="<?= url('operador/controles') ?>" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-x-circle"></i> Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

        <!-- Mapa de Controles -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-map"></i> Mapa de Controles (Posiciones 1-250)
                </h6>
                <span class="badge bg-info">Total: <?= count($mapa ?? []) ?> posiciones</span>
            </div>
            <div class="card-body">
                <?php if (empty($mapa)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mb-0 mt-3">No hay controles registrados</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Posición</th>
                                    <th>Receptor A</th>
                                    <th>Receptor B</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mapa as $posicion => $receptores): ?>
                                    <tr>
                                        <td class="align-middle">
                                            <strong style="font-size: 1.1rem;"><?= $posicion ?></strong>
                                        </td>

                                        <!-- Receptor A -->
                                        <td style="width: 45%;">
                                            <?php if (isset($receptores['A'])): ?>
                                                <?php $controlA = $receptores['A']; ?>
                                                <div class="p-2 border rounded
                                                    <?php if ($controlA['estado'] == 'activo'): ?>bg-success bg-opacity-10 border-success
                                                    <?php elseif ($controlA['estado'] == 'bloqueado'): ?>bg-danger bg-opacity-10 border-danger
                                                    <?php elseif ($controlA['estado'] == 'vacio'): ?>bg-light border-secondary
                                                    <?php else: ?>bg-warning bg-opacity-10 border-warning<?php endif; ?>">

                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong><?= $controlA['numero_control_completo'] ?></strong>
                                                            <span class="badge
                                                                <?php if ($controlA['estado'] == 'activo'): ?>bg-success
                                                                <?php elseif ($controlA['estado'] == 'bloqueado'): ?>bg-danger
                                                                <?php elseif ($controlA['estado'] == 'vacio'): ?>bg-secondary
                                                                <?php else: ?>bg-warning<?php endif; ?> ms-2">
                                                                <?= ucfirst($controlA['estado']) ?>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($controlA['propietario_nombre'])): ?>
                                                        <div class="mt-1 small">
                                                            <i class="bi bi-person"></i>
                                                            <?= htmlspecialchars($controlA['propietario_nombre']) ?>
                                                        </div>
                                                        <?php if (!empty($controlA['apartamento'])): ?>
                                                            <div class="small text-muted">
                                                                <i class="bi bi-building"></i>
                                                                Apto. <?= htmlspecialchars($controlA['apartamento']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div class="mt-1 small text-muted">
                                                            <i class="bi bi-dash-circle"></i> Disponible
                                                        </div>
                                                        <div class="mt-2 text-center">
                                                            <a href="<?= url('operador/controles/asignar?id=' . $controlA['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-plus-circle"></i> Asignar
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Receptor B -->
                                        <td style="width: 45%;">
                                            <?php if (isset($receptores['B'])): ?>
                                                <?php $controlB = $receptores['B']; ?>
                                                <div class="p-2 border rounded
                                                    <?php if ($controlB['estado'] == 'activo'): ?>bg-success bg-opacity-10 border-success
                                                    <?php elseif ($controlB['estado'] == 'bloqueado'): ?>bg-danger bg-opacity-10 border-danger
                                                    <?php elseif ($controlB['estado'] == 'vacio'): ?>bg-light border-secondary
                                                    <?php else: ?>bg-warning bg-opacity-10 border-warning<?php endif; ?>">

                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong><?= $controlB['numero_control_completo'] ?></strong>
                                                            <span class="badge
                                                                <?php if ($controlB['estado'] == 'activo'): ?>bg-success
                                                                <?php elseif ($controlB['estado'] == 'bloqueado'): ?>bg-danger
                                                                <?php elseif ($controlB['estado'] == 'vacio'): ?>bg-secondary
                                                                <?php else: ?>bg-warning<?php endif; ?> ms-2">
                                                                <?= ucfirst($controlB['estado']) ?>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($controlB['propietario_nombre'])): ?>
                                                        <div class="mt-1 small">
                                                            <i class="bi bi-person"></i>
                                                            <?= htmlspecialchars($controlB['propietario_nombre']) ?>
                                                        </div>
                                                        <?php if (!empty($controlB['apartamento'])): ?>
                                                            <div class="small text-muted">
                                                                <i class="bi bi-building"></i>
                                                                Apto. <?= htmlspecialchars($controlB['apartamento']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div class="mt-1 small text-muted">
                                                            <i class="bi bi-dash-circle"></i> Disponible
                                                        </div>
                                                        <div class="mt-2 text-center">
                                                            <a href="<?= url('operador/controles/asignar?id=' . $controlB['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-plus-circle"></i> Asignar
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
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

                <!-- Leyenda -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle"></i> Leyenda
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="p-2 bg-success bg-opacity-10 border border-success rounded mb-2">
                                    <strong>Activo:</strong> Control asignado y en uso
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 bg-light border border-secondary rounded mb-2">
                                    <strong>Disponible:</strong> Control libre para asignar
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 bg-danger bg-opacity-10 border border-danger rounded mb-2">
                                    <strong>Bloqueado:</strong> Control bloqueado (morosidad)
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 bg-warning bg-opacity-10 border border-warning rounded mb-2">
                                    <strong>Otros:</strong> Suspendido, perdido, etc.
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="bi bi-eye"></i>
                            <strong>Modo Solo Lectura:</strong> Como operador, puedes visualizar todos los controles pero no modificar su estado. Solo los administradores pueden realizar cambios.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal para gestión de controles -->
<div class="modal fade" id="gestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-controller"></i>
                    Gestionar Controles - <span id="modalUsuarioNombre"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function abrirGestionControles(usuarioId, nombreUsuario, apartamento) {
    document.getElementById('modalUsuarioNombre').textContent = nombreUsuario + ' (' + apartamento + ')';

    // Load content via AJAX
    fetch('<?= url('operador/gestionar-controles-usuario-ajax') ?>?id=' + usuarioId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalContent').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('gestionModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error loading control management:', error);
            document.getElementById('modalContent').innerHTML = '<div class="alert alert-danger">Error al cargar la gestión de controles</div>';
        });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
