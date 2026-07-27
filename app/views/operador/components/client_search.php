<!-- Client Search Component -->
<div class="text-center mb-4">
    <div class="mb-3">
        <i class="bi bi-search text-primary" style="font-size: 3rem;"></i>
    </div>
    <h5 class="text-muted mb-3">Buscar Cliente</h5>
    <p class="text-muted small">Ingresa el nombre, email, cédula o bloque del cliente para continuar</p>
</div>

<form method="GET" class="mb-4" id="searchForm">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="input-group input-group-lg shadow-sm">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text"
                       class="form-control border-start-0 ps-0"
                       id="clienteSearch"
                       name="buscar"
                       placeholder="Buscar por nombre, email, cédula, bloque..."
                       required
                       autofocus
                       value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>"
                       autocomplete="off">
                <input type="hidden" id="selectedClienteId" name="cliente_id" value="">
                <button type="submit" class="btn btn-primary px-3 px-md-4" title="Buscar Cliente">
                    <i class="bi bi-search"></i><span class="d-none d-sm-inline ms-2">Buscar Cliente</span>
                </button>
            </div>

            <!-- Autocomplete dropdown -->
            <div id="autocompleteResults" class="position-relative mt-2">
                <div id="suggestionsList" class="list-group position-absolute w-100 shadow border-0" style="z-index: 1000; max-height: 250px; overflow-y: auto; display: none;"></div>
            </div>
        </div>
    </div>
</form>

<div class="row mb-4">
    <div class="col-md-8 col-lg-6 mx-auto">
        <div class="alert alert-light border text-center">
            <i class="bi bi-info-circle text-info me-2"></i>
            <strong>¿Cómo buscar?</strong><br>
            <small class="text-muted">
                Puedes buscar por: nombre completo, email, número de cédula, bloque, escalera, piso o apartamento
            </small>
        </div>
    </div>
</div>

<?php if (!empty($resultadosBusqueda) && count($resultadosBusqueda) > 1 && !$cliente): ?>
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card border-warning shadow-sm">
                <div class="card-header bg-warning-subtle text-warning-emphasis py-3">
                    <h6 class="mb-0 text-warning-emphasis fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Múltiples coincidencias encontradas</h6>
                    <small class="text-muted">Por favor selecciona el cliente correcto de la lista:</small>
                </div>
                <div class="list-group list-group-flush text-start">
                    <?php foreach ($resultadosBusqueda as $res): ?>
                        <a href="?buscar=<?= urlencode($res['nombre_completo']) ?>&cliente_id=<?= $res['id'] ?><?= isset($_GET['modo']) ? '&modo=' . urlencode($_GET['modo']) : '' ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="text-primary"><?= htmlspecialchars($res['nombre_completo']) ?></strong>
                                <div class="text-muted small">
                                    <?= htmlspecialchars($res['email'] ?? 'Sin correo') ?> 
                                    <?php if (!empty($res['cedula'])): ?>
                                        | Cédula: <?= htmlspecialchars($res['cedula']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge bg-secondary"><?= htmlspecialchars($res['apartamento'] ?? 'Sin apartamento') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>