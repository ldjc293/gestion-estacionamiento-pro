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
        <div class="col-md-8 col-lg-6">
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
                       autocomplete="off">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-search me-2"></i>Buscar Cliente
                </button>
            </div>

            <!-- Autocomplete dropdown -->
            <div id="autocompleteResults" class="position-relative mt-2">
                <div id="suggestionsList" class="list-group position-absolute w-100 shadow border-0" style="z-index: 1000; max-height: 250px; overflow-y: auto; display: none;"></div>
            </div>
        </div>
    </div>
</form>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="alert alert-light border text-center">
            <i class="bi bi-info-circle text-info me-2"></i>
            <strong>¿Cómo buscar?</strong><br>
            <small class="text-muted">
                Puedes buscar por: nombre completo, email, número de cédula, bloque, escalera, piso o apartamento
            </small>
        </div>
    </div>
</div>