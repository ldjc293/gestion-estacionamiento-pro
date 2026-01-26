<?php
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$rol = $_SESSION['user_rol'] ?? 'cliente';

/**
 * Verificar si una ruta está activa
 */
function isActive(string $path): bool {
    global $currentPath;
    return strpos($currentPath, $path) !== false;
}
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4>
            <i class="bi bi-p-square-fill"></i>
            <?= APP_NAME ?>
        </h4>
        <div class="subtitle">Control de Estacionamiento</div>
    </div>

    <div class="sidebar-menu">
        <?php if ($rol === 'cliente'): ?>
            <!-- MENÚ CLIENTE -->
            <div class="menu-section">
                <div class="menu-section-title">Principal</div>
                <a href="<?= url('cliente/dashboard') ?>" class="menu-item <?= isActive('cliente/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?= url('cliente/estado-cuenta') ?>" class="menu-item <?= isActive('cliente/estado-cuenta') ? 'active' : '' ?>">
                    <i class="bi bi-wallet2"></i>
                    <span>Estado de Cuenta</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Pagos</div>
                <a href="<?= url('cliente/registrar-pago') ?>" class="menu-item <?= isActive('cliente/registrar-pago') ? 'active' : '' ?>">
                    <i class="bi bi-upload"></i>
                    <span>Registrar Pago</span>
                </a>
                <a href="<?= url('cliente/historial-pagos') ?>" class="menu-item <?= isActive('cliente/historial-pagos') ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i>
                    <span>Historial de Pagos</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Mi Información</div>
                <a href="<?= url('cliente/controles') ?>" class="menu-item <?= isActive('cliente/controles') ? 'active' : '' ?>">
                    <i class="bi bi-controller"></i>
                    <span>Mis Controles</span>
                </a>
                <a href="<?= url('cliente/perfil') ?>" class="menu-item <?= isActive('cliente/perfil') ? 'active' : '' ?>">
                    <i class="bi bi-person"></i>
                    <span>Mi Perfil</span>
                </a>
                <a href="<?= url('cliente/notificaciones') ?>" class="menu-item <?= isActive('cliente/notificaciones') ? 'active' : '' ?>">
                    <i class="bi bi-bell"></i>
                    <span>Notificaciones</span>
                    <?php
                    $notifCount = 0;
                    try {
                        $sql = "SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leido = FALSE";
                        $result = Database::fetchOne($sql, [$_SESSION['user_id']]);
                        $notifCount = $result ? $result['total'] : 0;
                    } catch (Exception $e) {
                        error_log("Error al obtener conteo de notificaciones en sidebar: " . $e->getMessage());
                        $notifCount = 0;
                    }
                    if ($notifCount > 0):
                    ?>
                        <span class="badge bg-danger"><?= $notifCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url('cliente/solicitudes') ?>" class="menu-item <?= isActive('cliente/solicitudes') ? 'active' : '' ?>">
                    <i class="bi bi-envelope-plus"></i>
                    <span>Generar Solicitud</span>
                </a>
            </div>

        <?php elseif ($rol === 'operador'): ?>
            <!-- MENÚ OPERADOR -->
            <div class="menu-section">
                <div class="menu-section-title">Principal</div>
                <a href="<?= url('operador/dashboard') ?>" class="menu-item <?= isActive('operador/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Pagos</div>
                <a href="<?= url('operador/pagos-pendientes') ?>" class="menu-item <?= isActive('operador/pagos-pendientes') ? 'active' : '' ?>">
                    <i class="bi bi-hourglass-split"></i>
                    <span>Pagos Pendientes</span>
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM pagos WHERE estado_comprobante = 'pendiente'";
                    $result = Database::fetchOne($sql);
                    if ($result && $result['total'] > 0):
                    ?>
                        <span class="badge bg-warning"><?= $result['total'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url('operador/registrar-pago-presencial') ?>" class="menu-item <?= isActive('operador/registrar-pago-presencial') ? 'active' : '' ?>">
                    <i class="bi bi-cash-coin"></i>
                    <span>Registrar Pago</span>
                </a>
                <a href="<?= url('operador/historial-pagos') ?>" class="menu-item <?= isActive('operador/historial-pagos') ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i>
                    <span>Historial</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Gestión</div>
                <a href="<?= url('operador/clientes-controles') ?>" class="menu-item <?= isActive('operador/clientes-controles') ? 'active' : '' ?>">
                    <i class="bi bi-people"></i>
                    <span>Clientes y Controles</span>
                </a>
                <a href="<?= url('operador/vista-controles') ?>" class="menu-item <?= isActive('operador/vista-controles') ? 'active' : '' ?>">
                    <i class="bi bi-grid-3x3"></i>
                    <span>Vista de Controles</span>
                </a>
                <a href="<?= url('operador/solicitudes') ?>" class="menu-item <?= isActive('operador/solicitudes') ? 'active' : '' ?>">
                    <i class="bi bi-inbox"></i>
                    <span>Solicitudes</span>
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM solicitudes_cambios WHERE estado = 'pendiente'";
                    $result = Database::fetchOne($sql);
                    if ($result && $result['total'] > 0):
                    ?>
                        <span class="badge bg-info"><?= $result['total'] ?></span>
                    <?php endif; ?>
                </a>
            </div>

        <?php elseif ($rol === 'consultor'): ?>
            <!-- MENÚ CONSULTOR -->
            <div class="menu-section">
                <div class="menu-section-title">Principal</div>
                <a href="<?= url('consultor/dashboard') ?>" class="menu-item <?= isActive('consultor/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?= url('consultor/buscar') ?>" class="menu-item <?= isActive('consultor/buscar') ? 'active' : '' ?>">
                    <i class="bi bi-search"></i>
                    <span>Buscar</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Reportes</div>
                <a href="<?= url('consultor/reporte-morosidad') ?>" class="menu-item <?= isActive('consultor/reporte-morosidad') ? 'active' : '' ?>">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Morosidad</span>
                </a>
                <a href="<?= url('consultor/reporte-pagos') ?>" class="menu-item <?= isActive('consultor/reporte-pagos') ? 'active' : '' ?>">
                    <i class="bi bi-cash-stack"></i>
                    <span>Pagos</span>
                </a>
                <a href="<?= url('consultor/reporte-controles') ?>" class="menu-item <?= isActive('consultor/reporte-controles') ? 'active' : '' ?>">
                    <i class="bi bi-controller"></i>
                    <span>Controles</span>
                </a>
                <a href="<?= url('consultor/reporte-apartamentos') ?>" class="menu-item <?= isActive('consultor/reporte-apartamentos') ? 'active' : '' ?>">
                    <i class="bi bi-building"></i>
                    <span>Apartamentos</span>
                </a>
                <a href="<?= url('consultor/reporte-financiero') ?>" class="menu-item <?= isActive('consultor/reporte-financiero') ? 'active' : '' ?>">
                    <i class="bi bi-graph-up"></i>
                    <span>Financiero</span>
                </a>
            </div>

        <?php elseif ($rol === 'administrador'): ?>
            <!-- MENÚ ADMINISTRADOR -->
            <div class="menu-section">
                <div class="menu-section-title">Principal</div>
                <a href="<?= url('admin/dashboard') ?>" class="menu-item <?= isActive('admin/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Gestión</div>
                <a href="<?= url('admin/usuarios') ?>" class="menu-item <?= isActive('admin/usuarios') ? 'active' : '' ?>">
                    <i class="bi bi-people"></i>
                    <span>Usuarios</span>
                </a>
                <a href="<?= url('admin/apartamentos') ?>" class="menu-item <?= isActive('admin/apartamentos') ? 'active' : '' ?>">
                    <i class="bi bi-building"></i>
                    <span>Apartamentos</span>
                </a>
                <a href="<?= url('admin/controles') ?>" class="menu-item <?= isActive('admin/controles') ? 'active' : '' ?>">
                    <i class="bi bi-controller"></i>
                    <span>Controles</span>
                </a>
                <a href="<?= url('admin/solicitudes') ?>" class="menu-item <?= isActive('admin/solicitudes') ? 'active' : '' ?>">
                    <i class="bi bi-inbox"></i>
                    <span>Solicitudes</span>
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM solicitudes_cambios WHERE estado = 'pendiente'";
                    $result = Database::fetchOne($sql);
                    if ($result && $result['total'] > 0):
                    ?>
                        <span class="badge bg-info"><?= $result['total'] ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Sistema</div>
                <a href="<?= url('admin/configuracion') ?>" class="menu-item <?= isActive('admin/configuracion') ? 'active' : '' ?>">
                    <i class="bi bi-gear"></i>
                    <span>Configuración</span>
                </a>
                <a href="<?= url('admin/logs') ?>" class="menu-item <?= isActive('admin/logs') ? 'active' : '' ?>">
                    <i class="bi bi-file-text"></i>
                    <span>Logs de Actividad</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- MENÚ COMÚN PARA TODOS -->
        <div class="menu-section">
            <div class="menu-section-title">Cuenta</div>
            <a href="<?= url('auth/logout') ?>" class="menu-item">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>
</div>
