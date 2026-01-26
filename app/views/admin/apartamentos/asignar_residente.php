<?php
$pageTitle = 'Asignar Residente';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Apartamentos', 'url' => url('admin/apartamentos')],
    ['label' => 'Asignar Residente', 'url' => '#']
];

require_once __DIR__ . '/../../layouts/header.php';
?>

<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../../layouts/alerts.php'; ?>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-person-plus"></i> Asignar Residente a Apartamento
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <strong>Apartamento:</strong>
                    <?= htmlspecialchars($apartamento->bloque) ?> -
                    Escalera <?= htmlspecialchars($apartamento->escalera) ?> -
                    Piso <?= $apartamento->piso ?> -
                    Apto. <?= htmlspecialchars($apartamento->numero_apartamento) ?>
                </div>

                <form action="<?= url('admin/processAsignarResidente') ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="apartamento_id" value="<?= $apartamento->id ?>">

                    <div class="mb-3">
                        <label for="usuario_id" class="form-label">Seleccionar Cliente *</label>
                        <select class="form-select" id="usuario_id" name="usuario_id" required>
                            <option value="">-- Seleccione un cliente --</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente->id ?>">
                                    <?= htmlspecialchars($cliente->nombre_completo) ?>
                                    (<?= htmlspecialchars($cliente->email) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad_controles" class="form-label">Cantidad de Controles *</label>
                        <input type="number"
                               class="form-control"
                               id="cantidad_controles"
                               name="cantidad_controles"
                               required
                               min="0"
                               max="10"
                               value="2"
                               placeholder="Número de controles de estacionamiento">
                        <small class="text-muted">Cantidad de controles de estacionamiento a asignar (0-10)</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Asignar Residente
                        </button>
                        <a href="<?= url('admin/apartamentos') ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
