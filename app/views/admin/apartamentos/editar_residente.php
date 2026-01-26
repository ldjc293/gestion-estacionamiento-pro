<?php
$pageTitle = 'Editar Residente';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Apartamentos', 'url' => url('admin/apartamentos')],
    ['label' => 'Editar Residente', 'url' => '#']
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
                    <i class="bi bi-person-gear"></i> Editar Residente de Apartamento
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

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Residente Actual</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Nombre:</strong> <?= htmlspecialchars($asignacion['nombre_completo']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($asignacion['email']) ?></p>
                        <p><strong>Controles asignados:</strong> <?= $asignacion['cantidad_controles'] ?></p>
                    </div>
                </div>

                <form action="<?= url('admin/processEditarResidente') ?>" method="POST" id="formEditarResidente">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="asignacion_id" value="<?= $asignacion['id'] ?>">
                    <input type="hidden" name="accion" id="accion" value="actualizar">

                    <div class="mb-3">
                        <label for="usuario_id" class="form-label">Cambiar Residente (Opcional)</label>
                        <select class="form-select" id="usuario_id" name="usuario_id">
                            <option value="<?= $asignacion['usuario_id'] ?>" selected>
                                Mantener actual: <?= htmlspecialchars($asignacion['nombre_completo']) ?>
                            </option>
                            <optgroup label="Cambiar a:">
                                <?php foreach ($clientes as $cliente): ?>
                                    <?php if ($cliente->id != $asignacion['usuario_id']): ?>
                                        <option value="<?= $cliente->id ?>">
                                            <?= htmlspecialchars($cliente->nombre_completo) ?>
                                            (<?= htmlspecialchars($cliente->email) ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                        <small class="text-muted">Selecciona otro cliente si deseas cambiar el residente</small>
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
                               value="<?= $asignacion['cantidad_controles'] ?>"
                               placeholder="Número de controles de estacionamiento">
                        <small class="text-muted">Cantidad de controles de estacionamiento (0-10)</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
                        <button type="button" class="btn btn-danger" onclick="confirmarRemover()">
                            <i class="bi bi-person-x"></i> Remover Residente
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

<script>
function confirmarRemover() {
    if (confirm('¿Está seguro de que desea remover al residente de este apartamento?\n\nEsta acción desactivará la asignación actual.')) {
        document.getElementById('accion').value = 'remover';
        document.getElementById('formEditarResidente').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
