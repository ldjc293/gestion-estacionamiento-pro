<?php
$pageTitle = 'Notificaciones';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('cliente/dashboard')],
    ['label' => 'Notificaciones', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-bell"></i> Todas las Notificaciones
                </h6>
                <div class="btn-group btn-group-sm">
                    <form action="<?= url('cliente/marcar-todas-leidas') ?>" method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-check-all"></i> Marcar Todas como Leídas
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros como pestañas -->
                    <div class="mb-4">
                        <ul class="nav nav-tabs" id="notificationTabs" role="tablist">
                            <?php
                            // Calcular conteos por tipo usando todas las notificaciones
                            $conteos = [
                                'todas' => count($todasNotificaciones),
                                'pago' => 0,
                                'mensualidad' => 0,
                                'control' => 0,
                                'sistema' => 0
                            ];

                            // Mapear tipos de base de datos a categorías de filtro
                            $tipoMapping = [
                                'pago' => ['pago_aprobado', 'comprobante_rechazado'],
                                'mensualidad' => ['mensualidad_generada', 'mensualidad_vencida', 'alerta_3_meses'],
                                'control' => ['control_asignado', 'control_bloqueado'],
                                'sistema' => ['sistema', 'bloqueo', 'morosidad', 'bienvenida']
                            ];

                            if (!empty($todasNotificaciones)) {
                                foreach ($todasNotificaciones as $notif) {
                                    foreach ($tipoMapping as $categoria => $tipos) {
                                        if (in_array($notif['tipo'], $tipos)) {
                                            $conteos[$categoria]++;
                                            break; // Una notificación solo pertenece a una categoría
                                        }
                                    }
                                }
                            }

                            $tipos = [
                                '' => ['label' => 'Todas', 'icon' => 'list'],
                                'pago' => ['label' => 'Pagos', 'icon' => 'cash'],
                                'mensualidad' => ['label' => 'Mensualidades', 'icon' => 'calendar'],
                                'control' => ['label' => 'Controles', 'icon' => 'tag'],
                                'sistema' => ['label' => 'Sistema', 'icon' => 'gear']
                            ];

                            foreach ($tipos as $tipo => $config):
                                $active = ($tipo === ($_GET['tipo'] ?? ''));
                                $count = $conteos[$tipo === '' ? 'todas' : $tipo];
                            ?>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link <?= $active ? 'active' : '' ?>"
                                   id="tab-<?= $tipo ?: 'todas' ?>"
                                   href="<?= url('cliente/notificaciones' . ($tipo ? "?tipo={$tipo}" : '')) ?>"
                                   role="tab">
                                    <i class="bi bi-<?= $config['icon'] ?> me-1"></i>
                                    <?= $config['label'] ?>
                                    <?php if ($count > 0): ?>
                                        <span class="badge bg-secondary ms-1"><?= $count ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Contenido de las pestañas -->
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-content" role="tabpanel">
                            <?php if (empty($notificaciones)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-bell-slash text-muted" style="font-size: 80px;"></i>
                                    <h5 class="text-muted mt-3">
                                        <?php if (isset($_GET['tipo'])): ?>
                                            No hay notificaciones de este tipo
                                        <?php else: ?>
                                            No tienes notificaciones
                                        <?php endif; ?>
                                    </h5>
                                    <p class="text-muted">
                                        <?php if (isset($_GET['tipo'])): ?>
                                            Cuando recibas notificaciones de este tipo aparecerán aquí
                                        <?php else: ?>
                                            Cuando recibas notificaciones aparecerán aquí
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <!-- Lista de Notificaciones -->
                                <div class="list-group">
                                    <?php foreach ($notificaciones as $notif): ?>
                                        <div class="list-group-item <?= !$notif['leido'] ? 'list-group-item-light' : '' ?>">
                                            <div class="d-flex w-100 justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <?php
                                                        // Icono según tipo
                                                        $iconos = [
                                                            'pago' => ['icon' => 'cash-coin', 'color' => 'success'],
                                                            'mensualidad' => ['icon' => 'calendar-event', 'color' => 'primary'],
                                                            'control' => ['icon' => 'tag', 'color' => 'info'],
                                                            'morosidad' => ['icon' => 'exclamation-triangle', 'color' => 'warning'],
                                                            'bloqueo' => ['icon' => 'lock', 'color' => 'danger'],
                                                            'sistema' => ['icon' => 'gear', 'color' => 'secondary']
                                                        ];
                                                        $icon = $iconos[$notif['tipo']] ?? $iconos['sistema'];
                                                        ?>
                                                        <span class="badge bg-<?= $icon['color'] ?> me-2">
                                                            <i class="bi bi-<?= $icon['icon'] ?>"></i>
                                                        </span>
                                                        <h6 class="mb-0">
                                                            <?= htmlspecialchars($notif['titulo']) ?>
                                                            <?php if (!$notif['leido']): ?>
                                                                <span class="badge bg-danger ms-2">Nuevo</span>
                                                            <?php endif; ?>
                                                        </h6>
                                                    </div>
                                                    <p class="mb-2"><?= nl2br(htmlspecialchars($notif['mensaje'])) ?></p>
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock"></i>
                                                        <?= date('d/m/Y H:i', strtotime($notif['fecha_creacion'])) ?>
                                                    </small>
                                                </div>
                                                <div class="ms-3">
                                                    <?php if (!$notif['leido']): ?>
                                                        <form action="<?= url('cliente/marcar-leida') ?>" method="POST" style="display: inline;">
                                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                            <input type="hidden" name="notificacion_id" value="<?= $notif['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Marcar como leída">
                                                                <i class="bi bi-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Paginación (si implementas) -->
                    <?php if (isset($totalPaginas) && $totalPaginas > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                    <li class="page-item <?= ($paginaActual ?? 1) === $i ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= url('cliente/notificaciones?pagina=' . $i) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para las pestañas de notificaciones */
.nav-tabs .nav-link {
    border: 1px solid #dee2e6;
    border-bottom: none;
    background-color: #f8f9fa;
    color: #6c757d;
    font-weight: 500;
    transition: all 0.2s ease;
}

.nav-tabs .nav-link:hover {
    background-color: #e9ecef;
    color: #495057;
}

.nav-tabs .nav-link.active {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

.nav-tabs .nav-link.active:hover {
    background-color: #0056b3;
    color: white;
}

.nav-tabs .badge {
    font-size: 0.75em;
    margin-left: 0.25rem;
}

/* Espaciado para el contenido de las pestañas */
.tab-content {
    border: 1px solid #dee2e6;
    border-top: none;
    padding: 1rem;
    background-color: white;
    border-radius: 0 0 0.375rem 0.375rem;
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
