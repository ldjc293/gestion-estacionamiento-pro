<!-- Client Found Component -->
<div class="alert alert-success border-0 shadow-sm mb-4">
    <div class="d-flex align-items-center">
        <i class="bi bi-check-circle-fill fs-4 text-success me-3"></i>
        <div class="flex-grow-1">
            <h6 class="mb-1 text-success">Cliente Encontrado</h6>
            <strong class="fs-5"><?= htmlspecialchars($cliente->nombre_completo) ?></strong>
            <?php if (isset($cliente->email) && $cliente->email): ?>
                <br><small class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($cliente->email) ?></small>
            <?php endif; ?>
            <?php if (isset($cliente->apartamento_info)): ?>
                <br><small class="text-muted"><i class="bi bi-house me-1"></i><?= htmlspecialchars($cliente->apartamento_info) ?></small>
            <?php endif; ?>
        </div>
        <div class="text-end">
            <small class="text-muted d-block">ID: <?= $cliente->id ?></small>
            <span class="badge bg-success">Activo</span>
        </div>
    </div>
</div>