<?php
$pageTitle = 'Administrar Residentes';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Apartamentos', 'url' => url('admin/apartamentos')],
    ['label' => 'Administrar Residentes', 'url' => '#']
];

require_once __DIR__ . '/../../layouts/header.php';
?>

<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../../layouts/alerts.php'; ?>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-people"></i> Residentes del Apartamento
                </h6>
                <a href="<?= url('admin/asignarResidente?id=' . $apartamentoId) ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-person-plus"></i> Agregar Otro Residente
                </a>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <strong>Apartamento:</strong>
                    <?= htmlspecialchars($apartamento->bloque) ?> -
                    Escalera <?= htmlspecialchars($apartamento->escalera) ?> -
                    Piso <?= $apartamento->piso == 0 ? 'PB' : $apartamento->piso ?> -
                    Apto. <?= htmlspecialchars($apartamento->numero_apartamento) ?>
                </div>

                <div class="row">
                    <?php foreach ($asignaciones as $asignacion): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 shadow-sm border-info">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-info fw-bold">
                                        <i class="bi bi-person"></i> <?= htmlspecialchars($asignacion['nombre_completo']) ?>
                                    </h6>
                                    <span class="badge bg-primary rounded-pill"><?= $asignacion['cantidad_controles'] ?> Controles</span>
                                </div>
                                <div class="card-body">
                                    <p class="mb-1"><i class="bi bi-envelope text-muted"></i> <?= htmlspecialchars($asignacion['email']) ?></p>
                                    <?php if (!empty($asignacion['telefono'])): ?>
                                        <p class="mb-3"><i class="bi bi-telephone text-muted"></i> <?= htmlspecialchars($asignacion['telefono']) ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($asignacion['controles_detalle'])): ?>
                                        <div class="mt-3">
                                            <small class="text-muted fw-bold d-block mb-2">Controles Físicos Asignados:</small>
                                            <ul class="list-group list-group-flush small">
                                                <?php foreach ($asignacion['controles_detalle'] as $ctrl): ?>
                                                    <li class="list-group-item px-0 py-1 d-flex justify-content-between align-items-center">
                                                        <span><i class="bi bi-hdd-network text-secondary"></i> N° <?= htmlspecialchars($ctrl['numero_control_completo']) ?></span>
                                                        <?php if ($ctrl['estado'] === 'activo'): ?>
                                                            <span class="badge bg-success" style="font-size: 0.65em;">Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger" style="font-size: 0.65em;">Inactivo</span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-3 text-center text-muted">
                                            <small><i class="bi bi-info-circle"></i> No tiene controles físicos vinculados.</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-white border-top-0 pt-0">
                                    <form action="<?= url('admin/processEditarResidente') ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de que desea remover a este residente del apartamento?');">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="asignacion_id" value="<?= $asignacion['id'] ?>">
                                        <input type="hidden" name="accion" value="remover">
                                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                            <i class="bi bi-person-x"></i> Remover Residente
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
