<?php
$pageTitle = 'Clientes y Controles Asignados';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('operador/dashboard')],
    ['label' => 'Clientes y Controles', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">
                            <i class="bi bi-people"></i> Clientes y Controles Asignados
                        </h4>
                        <small class="text-muted">Vista general de clientes con sus controles de estacionamiento</small>
                    </div>
                    <a href="<?= url('operador/vista-controles') ?>" class="btn btn-outline-primary">
                        <i class="bi bi-grid"></i> Ver Controles
                    </a>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Buscar</label>
                        <input type="text"
                               class="form-control"
                               name="busqueda"
                               value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>"
                               placeholder="Nombre, email, cédula o apartamento">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bloque</label>
                        <select class="form-select" name="bloque">
                            <option value="">Todos los bloques</option>
                            <?php for ($i = 27; $i <= 32; $i++): ?>
                                <option value="<?= $i ?>" <?= (isset($_GET['bloque']) && $_GET['bloque'] == $i) ? 'selected' : '' ?>>
                                    Bloque <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <a href="<?= url('operador/clientes-controles') ?>" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de Clientes -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-table"></i> Lista de Clientes
                    <span class="badge bg-primary ms-2"><?= count($clientes) ?> clientes</span>
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($clientes)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-people text-muted" style="font-size: 64px;"></i>
                        <p class="text-muted mt-3">No se encontraron clientes con los filtros aplicados</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Apartamento</th>
                                    <th>Controles Totales</th>
                                    <th>Estado de Controles</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientes as $cliente): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($cliente['nombre_completo']) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($cliente['email'] ?? 'Sin email') ?>
                                                    <?php if (!empty($cliente['cedula'])): ?>
                                                        <br><i class="bi bi-card-text"></i> <?= htmlspecialchars($cliente['cedula']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= htmlspecialchars($cliente['apartamento'] ?? 'Sin apartamento') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= $cliente['total_controles'] ?> controles
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if ($cliente['controles_activos'] > 0): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle"></i> <?= $cliente['controles_activos'] ?> activos
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($cliente['controles_bloqueados'] > 0): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-lock"></i> <?= $cliente['controles_bloqueados'] ?> bloqueados
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($cliente['controles_suspendidos'] > 0): ?>
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-pause-circle"></i> <?= $cliente['controles_suspendidos'] ?> suspendidos
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($cliente['controles_desactivados'] > 0): ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="bi bi-dash-circle"></i> <?= $cliente['controles_desactivados'] ?> desactivados
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($cliente['controles_perdidos'] > 0): ?>
                                                    <span class="badge bg-dark">
                                                        <i class="bi bi-question-circle"></i> <?= $cliente['controles_perdidos'] ?> perdidos
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('operador/controles') ?>"
                                                   class="btn btn-outline-success"
                                                   title="Gestionar Controles">
                                                    <i class="bi bi-controller"></i> Gestionar
                                                </a>
                                                <a href="<?= url('operador/registrar-pago-presencial?buscar=' . urlencode($cliente['email'])) ?>"
                                                   class="btn btn-primary"
                                                   title="Registrar Pago">
                                                    <i class="bi bi-cash-coin"></i> Pago
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>