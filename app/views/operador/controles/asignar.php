<?php
$pageTitle = 'Asignar Control';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('operador/dashboard')],
    ['label' => 'Mapa de Controles', 'url' => url('operador/controles')],
    ['label' => 'Asignar', 'url' => '#']
];

require_once __DIR__ . '/../../layouts/header.php';
?>

<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Asignar Control <?= htmlspecialchars($control->numero_control_completo) ?></h5>
                    </div>
                    <div class="card-body">
                        <?php require_once __DIR__ . '/../../layouts/alerts.php'; ?>

                        <form action="<?= url('operador/controles/asignar/process') ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="control_id" value="<?= $control->id ?>">

                            <div class="mb-4">
                                <label class="form-label">Control a Asignar</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($control->numero_control_completo) ?>" disabled>
                                <div class="form-text">Este control se encuentra actualmente en estado: <strong><?= ucfirst($control->estado) ?></strong></div>
                            </div>

                            <div class="mb-4">
                                <label for="apartamento_usuario_id" class="form-label">Seleccionar Residente / Apartamento</label>
                                <select name="apartamento_usuario_id" id="apartamento_usuario_id" class="form-select" required>
                                    <option value="">Seleccione un residente...</option>
                                    <?php foreach ($apartamentosUsuarios as $au): ?>
                                        <option value="<?= $au['id'] ?>">
                                            <?= htmlspecialchars($au['apartamento']) ?> - <?= htmlspecialchars($au['nombre_completo']) ?> 
                                            (Cupo: <?= $au['cantidad_controles'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Solo se muestran apartamentos con residentes activos.</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="<?= url('operador/controles') ?>" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Asignar Control
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
