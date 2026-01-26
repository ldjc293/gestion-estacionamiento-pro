<?php
$pageTitle = 'Gestión de Apartamentos';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Apartamentos', 'url' => '#']
];

require_once __DIR__ . '/../../layouts/header.php';
?>

<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../../layouts/alerts.php'; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-building"></i> Gestión de Apartamentos
                </h6>
                <a href="<?= url('admin/crearApartamento') ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Crear Apartamento
                </a>
            </div>
            <div class="card-body">
                <!-- Barra de búsqueda y filtros -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" 
                                   class="form-control" 
                                   id="busqueda" 
                                   placeholder="Buscar por bloque, escalera, número o residente..."
                                   value="<?= htmlspecialchars($busqueda ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="filtro-bloque">
                            <option value="">Todos los bloques</option>
                            <?php foreach ($bloques as $b): ?>
                                <option value="<?= htmlspecialchars($b) ?>" 
                                        <?= (isset($bloque) && $bloque == $b) ? 'selected' : '' ?>>
                                    Bloque <?= htmlspecialchars($b) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="filtro-escalera">
                            <option value="">Todas las escaleras</option>
                            <?php foreach ($escaleras as $e): ?>
                                <option value="<?= htmlspecialchars($e) ?>" 
                                        <?= (isset($escalera) && $escalera == $e) ? 'selected' : '' ?>>
                                    Escalera <?= htmlspecialchars($e) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="por-pagina">
                            <option value="10" <?= ($porPagina ?? 20) == 10 ? 'selected' : '' ?>>10 por página</option>
                            <option value="20" <?= ($porPagina ?? 20) == 20 ? 'selected' : '' ?>>20 por página</option>
                            <option value="30" <?= ($porPagina ?? 20) == 30 ? 'selected' : '' ?>>30 por página</option>
                            <option value="40" <?= ($porPagina ?? 20) == 40 ? 'selected' : '' ?>>40 por página</option>
                            <option value="50" <?= ($porPagina ?? 20) == 50 ? 'selected' : '' ?>>50 por página</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" id="limpiar-filtros">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </button>
                    </div>
                </div>

                <!-- Información de resultados -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">
                        Mostrando <?= count($apartamentos) > 0 ? (($paginaActual - 1) * $porPagina + 1) : 0 ?> 
                        - <?= min($paginaActual * $porPagina, $totalApartamentos) ?> 
                        de <?= $totalApartamentos ?> apartamentos
                    </small>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="tabla-apartamentos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Bloque</th>
                                <th>Escalera</th>
                                <th>Piso</th>
                                <th>Número</th>
                                <th>Residente</th>
                                <th>Controles</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($apartamentos)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mb-0 mt-2">No se encontraron apartamentos</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($apartamentos as $apartamento): ?>
                                    <tr>
                                        <td><?= $apartamento['id'] ?></td>
                                        <td><?= htmlspecialchars($apartamento['bloque']) ?></td>
                                        <td><?= htmlspecialchars($apartamento['escalera']) ?></td>
                                        <td><?= $apartamento['piso'] ?></td>
                                        <td><strong><?= htmlspecialchars($apartamento['numero_apartamento']) ?></strong></td>
                                        <td>
                                            <?php if (isset($apartamento['residente_nombre']) && !empty($apartamento['residente_nombre'])): ?>
                                                <small>
                                                    <i class="bi bi-person"></i>
                                                    <?= htmlspecialchars($apartamento['residente_nombre']) ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">Sin asignar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($apartamento['cantidad_controles']) && $apartamento['cantidad_controles'] > 0): ?>
                                                <span class="badge bg-info"><?= $apartamento['cantidad_controles'] ?> controles</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($apartamento['activo']): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if (!isset($apartamento['residente_nombre']) || empty($apartamento['residente_nombre'])): ?>
                                                    <a href="<?= url('admin/asignarResidente?id=' . $apartamento['id']) ?>"
                                                       class="btn btn-outline-primary"
                                                       title="Asignar Residente">
                                                        <i class="bi bi-person-plus"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= url('admin/editarResidente?id=' . $apartamento['id']) ?>"
                                                       class="btn btn-outline-info"
                                                       title="Editar Residente">
                                                        <i class="bi bi-person-gear"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?= url('admin/editarApartamento?id=' . $apartamento['id']) ?>"
                                                   class="btn btn-outline-secondary"
                                                   title="Editar Apartamento">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <?php if ($totalPaginas > 1): ?>
                    <nav aria-label="Paginación de apartamentos">
                        <ul class="pagination justify-content-center">
                            <!-- Primera página -->
                            <li class="page-item <?= $paginaActual == 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="#" data-pagina="1">
                                    <i class="bi bi-chevron-double-left"></i>
                                </a>
                            </li>
                            
                            <!-- Página anterior -->
                            <li class="page-item <?= $paginaActual == 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="#" data-pagina="<?= max(1, $paginaActual - 1) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>

                            <!-- Números de página -->
                            <?php
                            $rango = 2; // Mostrar 2 páginas antes y después de la actual
                            $inicio = max(1, $paginaActual - $rango);
                            $fin = min($totalPaginas, $paginaActual + $rango);

                            for ($i = $inicio; $i <= $fin; $i++):
                            ?>
                                <li class="page-item <?= $i == $paginaActual ? 'active' : '' ?>">
                                    <a class="page-link" href="#" data-pagina="<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <!-- Página siguiente -->
                            <li class="page-item <?= $paginaActual == $totalPaginas ? 'disabled' : '' ?>">
                                <a class="page-link" href="#" data-pagina="<?= min($totalPaginas, $paginaActual + 1) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>

                            <!-- Última página -->
                            <li class="page-item <?= $paginaActual == $totalPaginas ? 'disabled' : '' ?>">
                                <a class="page-link" href="#" data-pagina="<?= $totalPaginas ?>">
                                    <i class="bi bi-chevron-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-building text-primary" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $totalApartamentos ?></h3>
                        <small class="text-muted">Total Apartamentos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-person-check text-success" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0">
                            <?php
                            $sql = "SELECT COUNT(DISTINCT a.id) as total 
                                    FROM apartamentos a 
                                    JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = TRUE
                                    WHERE a.activo = TRUE";
                            $asignados = Database::fetchOne($sql);
                            echo $asignados['total'] ?? 0;
                            ?>
                        </h3>
                        <small class="text-muted">Asignados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-house text-warning" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0">
                            <?php
                            $sql = "SELECT COUNT(DISTINCT a.id) as total 
                                    FROM apartamentos a 
                                    LEFT JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = TRUE
                                    WHERE a.activo = TRUE AND au.id IS NULL";
                            $disponibles = Database::fetchOne($sql);
                            echo $disponibles['total'] ?? 0;
                            ?>
                        </h3>
                        <small class="text-muted">Disponibles</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-key text-info" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0">
                            <?php
                            $sql = "SELECT COALESCE(SUM(au.cantidad_controles), 0) as total 
                                    FROM apartamento_usuario au 
                                    WHERE au.activo = TRUE";
                            $controles = Database::fetchOne($sql);
                            echo $controles['total'] ?? 0;
                            ?>
                        </h3>
                        <small class="text-muted">Total Controles</small>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Búsqueda interactiva con debounce
let searchTimeout;
const busquedaInput = document.getElementById('busqueda');
const filtroBloque = document.getElementById('filtro-bloque');
const filtroEscalera = document.getElementById('filtro-escalera');
const porPaginaSelect = document.getElementById('por-pagina');
const limpiarBtn = document.getElementById('limpiar-filtros');

// Función para aplicar filtros
function aplicarFiltros(pagina = 1) {
    const params = new URLSearchParams();
    
    const busqueda = busquedaInput.value.trim();
    const bloque = filtroBloque.value;
    const escalera = filtroEscalera.value;
    const porPagina = porPaginaSelect.value;
    
    if (busqueda) params.append('busqueda', busqueda);
    if (bloque) params.append('bloque', bloque);
    if (escalera) params.append('escalera', escalera);
    if (porPagina) params.append('por_pagina', porPagina);
    if (pagina > 1) params.append('pagina', pagina);
    
    // Redirigir con los parámetros
    window.location.href = '<?= url('admin/apartamentos') ?>' + (params.toString() ? '?' + params.toString() : '');
}

// Búsqueda en tiempo real con debounce (300ms)
busquedaInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        aplicarFiltros();
    }, 300);
});

// Filtros de bloque y escalera
filtroBloque.addEventListener('change', () => aplicarFiltros());
filtroEscalera.addEventListener('change', () => aplicarFiltros());
porPaginaSelect.addEventListener('change', () => aplicarFiltros());

// Limpiar filtros
limpiarBtn.addEventListener('click', function() {
    window.location.href = '<?= url('admin/apartamentos') ?>';
});

// Paginación
document.querySelectorAll('.pagination .page-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const pagina = this.getAttribute('data-pagina');
        if (pagina && !this.parentElement.classList.contains('disabled')) {
            aplicarFiltros(parseInt(pagina));
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
