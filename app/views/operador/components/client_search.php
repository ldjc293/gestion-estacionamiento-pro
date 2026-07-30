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
                <div id="suggestionsList" class="list-group position-absolute w-100 shadow-lg border rounded-3" style="z-index: 1050; max-height: 320px; overflow-y: auto; display: none; background: white;"></div>
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
                Escribe desde 1 letra para desplegar las sugerencias o buscar por: nombre, email, cédula, bloque o apartamento.
            </small>
        </div>
    </div>
</div>

<?php if (!empty($resultadosBusqueda) && !$cliente): ?>
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card border-primary shadow-sm">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-people-fill me-2"></i>Coincidencias encontradas (<?= count($resultadosBusqueda) ?>)</h6>
                    <small class="text-white-50">Por favor selecciona el cliente deseado:</small>
                </div>
                <div class="list-group list-group-flush text-start">
                    <?php foreach ($resultadosBusqueda as $res): ?>
                        <a href="?buscar=<?= urlencode($res['nombre_completo']) ?>&cliente_id=<?= $res['id'] ?><?= isset($_GET['modo']) ? '&modo=' . urlencode($_GET['modo']) : '' ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="text-primary fs-6"><?= htmlspecialchars($res['nombre_completo']) ?></strong>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($res['email'] ?? 'Sin correo') ?> 
                                    <?php if (!empty($res['cedula'])): ?>
                                        | <i class="bi bi-card-text me-1"></i>Cédula: <?= htmlspecialchars($res['cedula']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge bg-secondary px-3 py-2 fs-7"><?= htmlspecialchars($res['apartamento'] ?? 'Sin apartamento') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>