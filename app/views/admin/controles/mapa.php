<?php
$pageTitle = 'Mapa de Controles';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Mapa de Controles', 'url' => '#']
];

$activeTab = $_GET['tab'] ?? (isset($_GET['buscar']) || isset($_GET['estado']) ? 'posiciones' : 'usuarios');

require_once __DIR__ . '/../../layouts/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../../layouts/alerts.php'; ?>

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
                <button class="nav-link <?= $activeTab === 'usuarios' ? 'active' : '' ?>" id="vista-usuarios-tab" data-bs-toggle="tab" data-bs-target="#vista-usuarios" type="button">
                    <i class="bi bi-people"></i> Vista por Usuario/Apartamento
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'posiciones' ? 'active' : '' ?>" id="vista-posiciones-tab" data-bs-toggle="tab" data-bs-target="#vista-posiciones" type="button">
                    <i class="bi bi-grid-3x3"></i> Vista por Posiciones
                </button>
            </li>
        </ul>

        <div class="tab-content" id="vistaControlTabsContent">
            <!-- Vista por Usuario/Apartamento -->
            <div class="tab-pane fade <?= $activeTab === 'usuarios' ? 'show active' : '' ?>" id="vista-usuarios" role="tabpanel">
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
                            STRING_AGG(c.numero_control_completo, ', ' ORDER BY c.posicion_numero) as lista_controles,
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
                                                    <a href="<?= url('admin/usuarios/editar?id=' . $item['usuario_id']) ?>"
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Gestionar Controles">
                                                        <i class="bi bi-gear"></i>
                                                    </a>
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
            <div class="tab-pane fade <?= $activeTab === 'posiciones' ? 'show active' : '' ?>" id="vista-posiciones" role="tabpanel">
                <!-- Búsqueda rápida -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-search"></i> Búsqueda de Controles
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="<?= url('admin/controles') ?>" class="row g-3">
                            <input type="hidden" name="tab" value="posiciones">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="buscar" value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>" placeholder="Buscar por posición, residente o apto (Ej: 15, 150A, Juan)">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="estado">
                                    <option value="">Todos los estados</option>
                                    <option value="activo" <?= ($_GET['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activos</option>
                                    <option value="vacio" <?= ($_GET['estado'] ?? '') === 'vacio' ? 'selected' : '' ?>>Disponibles (Vacíos)</option>
                                    <option value="bloqueado" <?= ($_GET['estado'] ?? '') === 'bloqueado' ? 'selected' : '' ?>>Bloqueados</option>
                                    <option value="suspendido" <?= ($_GET['estado'] ?? '') === 'suspendido' ? 'selected' : '' ?>>Suspendidos</option>
                                    <option value="desactivado" <?= ($_GET['estado'] ?? '') === 'desactivado' ? 'selected' : '' ?>>Desactivados</option>
                                    <option value="perdido" <?= ($_GET['estado'] ?? '') === 'perdido' ? 'selected' : '' ?>>Perdidos</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="<?= url('admin/controles?tab=posiciones') ?>" class="btn btn-outline-secondary w-100">
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
                                    <th style="width: 10%; min-width: 45px;">Pos.</th>
                                    <th style="width: 45%; min-width: 155px;">Receptor A</th>
                                    <th style="width: 45%; min-width: 155px;">Receptor B</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mapa as $posicion => $receptores): ?>
                                    <tr>
                                        <td class="align-middle text-center px-1">
                                            <strong style="font-size: 1rem;"><?= $posicion ?></strong>
                                        </td>

                                        <!-- Receptor A -->
                                        <td style="width: 45%; min-width: 155px;" class="p-1 p-sm-2">
                                            <?php if (isset($receptores['A'])): ?>
                                                <?php $controlA = $receptores['A']; ?>
                                                <div class="p-1 p-sm-2 border rounded control-card-box
                                                    <?php if ($controlA['estado'] == 'activo'): ?>bg-success bg-opacity-10 border-success
                                                    <?php elseif ($controlA['estado'] == 'bloqueado'): ?>bg-danger bg-opacity-10 border-danger
                                                    <?php elseif ($controlA['estado'] == 'vacio'): ?>bg-light border-secondary
                                                    <?php else: ?>bg-warning bg-opacity-10 border-warning<?php endif; ?>">

                                                    <div class="d-flex justify-content-between align-items-center flex-wrap flex-sm-nowrap gap-1">
                                                        <div class="d-flex align-items-center">
                                                            <strong><?= $controlA['numero_control_completo'] ?></strong>
                                                            <span class="badge
                                                                <?php if ($controlA['estado'] == 'activo'): ?>bg-success
                                                                <?php elseif ($controlA['estado'] == 'bloqueado'): ?>bg-danger
                                                                <?php elseif ($controlA['estado'] == 'vacio'): ?>bg-secondary
                                                                <?php else: ?>bg-warning<?php endif; ?> ms-1">
                                                                <?= ucfirst($controlA['estado']) ?>
                                                            </span>
                                                            <?php if (!empty($controlA['nota'])): ?>
                                                                <span class="badge bg-info text-dark ms-1" style="font-size: 0.7rem;" title="Nota de control">
                                                                    <i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($controlA['nota']) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <select class="form-select form-select-sm estado-control-select"
                                                                style="width: auto;"
                                                                data-control-id="<?= $controlA['id'] ?>"
                                                                data-control-numero="<?= $controlA['numero_control_completo'] ?>"
                                                                data-estado-actual="<?= $controlA['estado'] ?>"
                                                                data-asignado="<?= !empty($controlA['propietario_nombre']) ? '1' : '0' ?>"
                                                                onchange="cambiarEstadoControl(this)">
                                                            <option value="">Cambiar...</option>
                                                            <option value="activo" <?= $controlA['estado'] == 'activo' ? 'selected' : '' ?>>Activo</option>
                                                            <option value="vacio" <?= $controlA['estado'] == 'vacio' ? 'selected' : '' ?>>Disponible</option>
                                                            <option value="bloqueado" <?= $controlA['estado'] == 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                                                            <option value="suspendido" <?= $controlA['estado'] == 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
                                                            <option value="desactivado" <?= $controlA['estado'] == 'desactivado' ? 'selected' : '' ?>>Desactivado</option>
                                                            <option value="perdido" <?= $controlA['estado'] == 'perdido' ? 'selected' : '' ?>>Perdido</option>
                                                        </select>
                                                    </div>

                                                    <?php if (!empty($controlA['propietario_nombre'])): ?>
                                                        <div class="mt-1 small d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <i class="bi bi-person"></i> <?= htmlspecialchars($controlA['propietario_nombre']) ?>
                                                                <?php if (!empty($controlA['apartamento'])): ?>
                                                                    <br><small class="text-muted"><i class="bi bi-building"></i> Apto. <?= htmlspecialchars($controlA['apartamento']) ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-link text-primary p-0 border-0 ms-1" style="font-size: 11px;" title="Reasignar control"
                                                                    onclick="abrirModalAsignarControl(<?= $controlA['id'] ?>, '<?= htmlspecialchars($controlA['numero_control_completo']) ?>', '<?= $controlA['estado'] ?>')">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="mt-2 d-flex justify-content-between align-items-center">
                                                            <small class="text-muted"><i class="bi bi-dash-circle"></i> Sin asignar</small>
                                                            <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 11px;"
                                                                    title="Asignar este control a un residente"
                                                                    onclick="abrirModalAsignarControl(<?= $controlA['id'] ?>, '<?= htmlspecialchars($controlA['numero_control_completo']) ?>', '<?= $controlA['estado'] ?>')">
                                                                <i class="bi bi-person-plus"></i> Asignar
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Receptor B -->
                                        <td style="width: 45%; min-width: 155px;" class="p-1 p-sm-2">
                                            <?php if (isset($receptores['B'])): ?>
                                                <?php $controlB = $receptores['B']; ?>
                                                <div class="p-1 p-sm-2 border rounded control-card-box
                                                    <?php if ($controlB['estado'] == 'activo'): ?>bg-success bg-opacity-10 border-success
                                                    <?php elseif ($controlB['estado'] == 'bloqueado'): ?>bg-danger bg-opacity-10 border-danger
                                                    <?php elseif ($controlB['estado'] == 'vacio'): ?>bg-light border-secondary
                                                    <?php else: ?>bg-warning bg-opacity-10 border-warning<?php endif; ?>">

                                                    <div class="d-flex justify-content-between align-items-center flex-wrap flex-sm-nowrap gap-1">
                                                        <div class="d-flex align-items-center">
                                                            <strong><?= $controlB['numero_control_completo'] ?></strong>
                                                            <span class="badge
                                                                <?php if ($controlB['estado'] == 'activo'): ?>bg-success
                                                                <?php elseif ($controlB['estado'] == 'bloqueado'): ?>bg-danger
                                                                <?php elseif ($controlB['estado'] == 'vacio'): ?>bg-secondary
                                                                <?php else: ?>bg-warning<?php endif; ?> ms-1">
                                                                <?= ucfirst($controlB['estado']) ?>
                                                            </span>
                                                            <?php if (!empty($controlB['nota'])): ?>
                                                                <span class="badge bg-info text-dark ms-1" style="font-size: 0.7rem;" title="Nota de control">
                                                                    <i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($controlB['nota']) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <select class="form-select form-select-sm estado-control-select"
                                                                style="width: auto;"
                                                                data-control-id="<?= $controlB['id'] ?>"
                                                                data-control-numero="<?= $controlB['numero_control_completo'] ?>"
                                                                data-estado-actual="<?= $controlB['estado'] ?>"
                                                                data-asignado="<?= !empty($controlB['propietario_nombre']) ? '1' : '0' ?>"
                                                                onchange="cambiarEstadoControl(this)">
                                                            <option value="">Cambiar...</option>
                                                            <option value="activo" <?= $controlB['estado'] == 'activo' ? 'selected' : '' ?>>Activo</option>
                                                            <option value="vacio" <?= $controlB['estado'] == 'vacio' ? 'selected' : '' ?>>Disponible</option>
                                                            <option value="bloqueado" <?= $controlB['estado'] == 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                                                            <option value="suspendido" <?= $controlB['estado'] == 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
                                                            <option value="desactivado" <?= $controlB['estado'] == 'desactivado' ? 'selected' : '' ?>>Desactivado</option>
                                                            <option value="perdido" <?= $controlB['estado'] == 'perdido' ? 'selected' : '' ?>>Perdido</option>
                                                        </select>
                                                    </div>

                                                    <?php if (!empty($controlB['propietario_nombre'])): ?>
                                                        <div class="mt-1 small d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <i class="bi bi-person"></i> <?= htmlspecialchars($controlB['propietario_nombre']) ?>
                                                                <?php if (!empty($controlB['apartamento'])): ?>
                                                                    <br><small class="text-muted"><i class="bi bi-building"></i> Apto. <?= htmlspecialchars($controlB['apartamento']) ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-link text-primary p-0 ms-1 border-0" style="font-size: 11px;" title="Reasignar control"
                                                                    onclick="abrirModalAsignarControl(<?= $controlB['id'] ?>, '<?= htmlspecialchars($controlB['numero_control_completo']) ?>', '<?= $controlB['estado'] ?>')">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="mt-2 d-flex justify-content-between align-items-center">
                                                            <small class="text-muted"><i class="bi bi-dash-circle"></i> Sin asignar</small>
                                                            <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 11px;"
                                                                    title="Asignar este control a un residente"
                                                                    onclick="abrirModalAsignarControl(<?= $controlB['id'] ?>, '<?= htmlspecialchars($controlB['numero_control_completo']) ?>', '<?= $controlB['estado'] ?>')">
                                                                <i class="bi bi-person-plus"></i> Asignar
                                                            </button>
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
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal para cambiar estado de control -->
<div class="modal fade" id="modalCambiarEstado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Estado del Control</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/cambiarEstadoControl') ?>" method="POST" id="formCambiarEstado">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="control_id" id="modal_control_id">

                    <div class="alert alert-info">
                        <strong>Control:</strong> <span id="modal_control_numero"></span><br>
                        <strong>Estado actual:</strong> <span id="modal_control_estado_actual" class="badge"></span>
                    </div>

                    <div class="mb-3">
                        <label for="modal_estado" class="form-label fw-bold">Nuevo Estado *</label>
                        <select class="form-select" name="estado" id="modal_estado" required>
                            <option value="">Seleccione...</option>
                            <option value="activo">Activo</option>
                            <option value="vacio">Disponible (Vacío)</option>
                            <option value="bloqueado">Bloqueado</option>
                            <option value="suspendido">Suspendido</option>
                            <option value="desactivado">Desactivado</option>
                            <option value="perdido">Perdido</option>
                        </select>
                        <small class="text-muted">Selecciona el nuevo estado del control</small>
                    </div>

                    <div class="mb-3">
                        <label for="modal_motivo" class="form-label fw-bold">Motivo *</label>
                        <textarea class="form-control"
                                  name="motivo"
                                  id="modal_motivo"
                                  required
                                  rows="3"
                                  maxlength="255"
                                  placeholder="Describe el motivo del cambio de estado"></textarea>
                        <small class="text-muted">Explica por qué se cambia el estado del control</small>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Importante:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Si cambias a "Disponible", el control se desasignará del propietario actual</li>
                            <li>Si cambias a "Bloqueado", el control quedará inactivo hasta desbloquearlo</li>
                            <li>Los cambios quedan registrados en el log del sistema</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Cambiar Estado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.LISTA_RESIDENTES = <?= json_encode($listaResidentes ?? []) ?>;

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#vistaControlTabs button[data-bs-toggle="tab"]').forEach(tabBtn => {
        tabBtn.addEventListener('shown.bs.tab', (e) => {
            const targetId = e.target.getAttribute('data-bs-target');
            const tabName = targetId === '#vista-posiciones' ? 'posiciones' : 'usuarios';
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('tab', tabName);
            window.history.replaceState(null, '', '?' + urlParams.toString());
        });
    });
});

function abrirModalAsignarControl(controlId, controlNumero, estadoActual = 'activo') {
    let opcionesResidentes = '<option value="">-- Seleccionar Residente --</option>';
    if (window.LISTA_RESIDENTES && window.LISTA_RESIDENTES.length > 0) {
        window.LISTA_RESIDENTES.forEach(r => {
            opcionesResidentes += `<option value="${r.id}">${r.nombre_completo} (${r.apartamento})</option>`;
        });
    }

    const htmlContent = `
        <div class="text-start">
            <div class="mb-3">
                <label class="form-label fw-bold small mb-1">Residente a Asignar *:</label>
                <select id="swal_asignar_residente_id" class="form-select form-select-sm">
                    ${opcionesResidentes}
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small mb-1">Estatus del Control:</label>
                <select id="swal_asignar_estado" class="form-select form-select-sm">
                    <option value="activo" ${estadoActual === 'activo' ? 'selected' : ''}>Activo</option>
                    <option value="suspendido" ${estadoActual === 'suspendido' ? 'selected' : ''}>Suspendido</option>
                    <option value="bloqueado" ${estadoActual === 'bloqueado' ? 'selected' : ''}>Bloqueado</option>
                    <option value="desactivado" ${estadoActual === 'desactivado' ? 'selected' : ''}>Desactivado</option>
                </select>
            </div>
        </div>
    `;

    Swal.fire({
        title: 'Asignar Control #' + controlNumero,
        html: htmlContent,
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Guardar y Asignar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const resId = document.getElementById('swal_asignar_residente_id').value;
            const estado = document.getElementById('swal_asignar_estado').value;
            if (!resId) {
                Swal.showValidationMessage('Debe seleccionar un residente');
                return false;
            }
            return { residente_id: resId, estado: estado };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Asignando control...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData();
            formData.append('csrf_token', '<?= generateCSRFToken() ?>');
            formData.append('control_id', controlId);
            formData.append('apartamento_usuario_id', result.value.residente_id);
            formData.append('estado', result.value.estado);
            formData.append('motivo', 'Asignación directa desde panel de controles');

            fetch('<?= url("admin/cambiarEstadoControl") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Éxito!', data.message || 'Control asignado exitosamente', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'No se pudo asignar el control', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                location.reload();
            });
        }
    });
}

function cambiarEstadoControl(selectElement) {
    const controlId = selectElement.dataset.controlId;
    const controlNumero = selectElement.dataset.controlNumero;
    const nuevoEstado = selectElement.value;
    const estadoActual = selectElement.dataset.estadoActual;
    const esAsignado = selectElement.dataset.asignado === '1';

    if (!nuevoEstado || nuevoEstado === estadoActual) {
        selectElement.value = estadoActual;
        return;
    }

    // Si el control NO está asignado y se cambió a un estado distinto a vacío, preguntar si desea asignarlo a un residente
    if (!esAsignado && nuevoEstado !== 'vacio') {
        Swal.fire({
            title: 'Control #' + controlNumero + ' sin residente',
            text: 'El control no tiene un residente asignado. ¿Desea asignárselo a un residente al cambiar su estatus a ' + nuevoEstado.toUpperCase() + '?',
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonColor: '#198754',
            denyButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, asignar a residente',
            denyButtonText: 'Solo cambiar estatus',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                abrirModalAsignarControl(controlId, controlNumero, nuevoEstado);
            } else if (result.isDenied) {
                ejecutarCambioEstadoDirecto(selectElement, controlId, controlNumero, nuevoEstado, estadoActual);
            } else {
                selectElement.value = estadoActual;
            }
        });
        return;
    }

    ejecutarCambioEstadoDirecto(selectElement, controlId, controlNumero, nuevoEstado, estadoActual);
}

function ejecutarCambioEstadoDirecto(selectElement, controlId, controlNumero, nuevoEstado, estadoActual) {
    const motivoPrompt = prompt('Por favor, ingrese el motivo para cambiar el estado del control ' + controlNumero + ':');
    if (!motivoPrompt || motivoPrompt.trim() === '') {
        selectElement.value = estadoActual;
        return;
    }

    fetch('<?= url("admin/cambiarEstadoControl") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            'csrf_token': '<?= generateCSRFToken() ?>',
            'control_id': controlId,
            'estado': nuevoEstado,
            'motivo': motivoPrompt.trim()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('¡Éxito!', data.message || 'Estatus actualizado correctamente', 'success')
                .then(() => location.reload());
        } else {
            selectElement.value = estadoActual;
            Swal.fire('Error', data.message || 'No se pudo cambiar el estado', 'error');
        }
    })
    .catch(error => {
        console.error(error);
        selectElement.value = estadoActual;
        Swal.fire('Error', 'Error de conexión. Por favor, intente nuevamente.', 'error');
    });
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
