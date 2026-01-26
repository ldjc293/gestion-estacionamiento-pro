<?php
$pageTitle = 'Crear Apartamento';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Apartamentos', 'url' => url('admin/apartamentos')],
    ['label' => 'Crear', 'url' => '#']
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
                    <i class="bi bi-building"></i> Crear Nuevo Apartamento
                </h6>
            </div>
            <div class="card-body">
                <form action="<?= url('admin/processCrearApartamento') ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bloque" class="form-label">Bloque *</label>
                                <input type="text"
                                       class="form-control"
                                       id="bloque"
                                       name="bloque"
                                       required
                                       maxlength="10"
                                       placeholder="Ej: A, B, Torre 1">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="escalera" class="form-label">Escalera *</label>
                                <input type="text"
                                       class="form-control"
                                       id="escalera"
                                       name="escalera"
                                       required
                                       maxlength="10"
                                       placeholder="Ej: 1, 2, A, B">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="piso" class="form-label">Piso *</label>
                                <input type="number"
                                       class="form-control"
                                       id="piso"
                                       name="piso"
                                       required
                                       min="0"
                                       max="50"
                                       placeholder="Ej: 1, 2, 3...">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="numero_apartamento" class="form-label">Número de Apartamento *</label>
                                <input type="text"
                                       class="form-control"
                                       id="numero_apartamento"
                                       name="numero_apartamento"
                                       required
                                       maxlength="10"
                                       placeholder="Ej: 101, 102, A, B">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Nota:</strong> Todos los campos son obligatorios. El apartamento se creará en estado activo.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Crear Apartamento
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
