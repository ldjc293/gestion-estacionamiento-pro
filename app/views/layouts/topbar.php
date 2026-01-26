<?php
$userName = $_SESSION['user_nombre'] ?? 'Usuario';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_rol'] ?? 'cliente';

// Obtener iniciales para el avatar
$initials = '';
$nameParts = explode(' ', $userName);
if (count($nameParts) >= 2) {
    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
} else {
    $initials = strtoupper(substr($userName, 0, 2));
}

// Roles en español
$rolesES = [
    'cliente' => 'Cliente',
    'operador' => 'Operador',
    'consultor' => 'Consultor',
    'administrador' => 'Administrador'
];
?>

<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" id="mobileToggle">
            <i class="bi bi-list"></i>
        </button>
        <h5><?= $pageTitle ?? 'Dashboard' ?></h5>
        <?php if (isset($breadcrumb)): ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <?php foreach ($breadcrumb as $index => $item): ?>
                        <?php if ($index === count($breadcrumb) - 1): ?>
                            <li class="breadcrumb-item active" aria-current="page"><?= $item['label'] ?></li>
                        <?php else: ?>
                            <li class="breadcrumb-item">
                                <a href="<?= $item['url'] ?>"><?= $item['label'] ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>
    </div>

    <div class="topbar-right">
        <!-- Notificaciones (solo para clientes) -->
        <?php if ($userRole === 'cliente'): ?>
            <?php
            $notificacionesCount = 0;
            try {
                $sql = "SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leido = FALSE";
                $result = Database::fetchOne($sql, [$_SESSION['user_id']]);
                $notificacionesCount = $result ? $result['total'] : 0;
            } catch (Exception $e) {
                // Si hay error con la tabla de notificaciones, mostrar 0
                error_log("Error al obtener conteo de notificaciones: " . $e->getMessage());
                $notificacionesCount = 0;
            }
            ?>
            <div class="topbar-icon" onclick="window.location.href='<?= url('cliente/notificaciones') ?>'">
                <i class="bi bi-bell"></i>
                <?php if ($notificacionesCount > 0): ?>
                    <span class="badge"><?= $notificacionesCount ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Pagos pendientes (solo para operadores) -->
        <?php if (in_array($userRole, ['operador', 'administrador'])): ?>
            <?php
            $sql = "SELECT COUNT(*) as total FROM pagos WHERE estado_comprobante = 'pendiente'";
            $result = Database::fetchOne($sql);
            $pagosPendientesCount = $result ? $result['total'] : 0;
            ?>
            <div class="topbar-icon" onclick="window.location.href='<?= url('operador/pagos-pendientes') ?>'">
                <i class="bi bi-hourglass-split"></i>
                <?php if ($pagosPendientesCount > 0): ?>
                    <span class="badge"><?= $pagosPendientesCount ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Usuario -->
        <div class="dropdown">
            <div class="user-menu" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar"><?= $initials ?></div>
                <div class="user-info">
                    <div class="name"><?= htmlspecialchars($userName) ?></div>
                    <div class="role"><?= $rolesES[$userRole] ?? ucfirst($userRole) ?></div>
                </div>
                <i class="bi bi-chevron-down"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php if ($userRole === 'cliente'): ?>
                    <li>
                        <a class="dropdown-item" href="<?= url('cliente/perfil') ?>">
                            <i class="bi bi-person"></i> Mi Perfil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= url('cliente/cambiar-password') ?>">
                            <i class="bi bi-key"></i> Cambiar Contraseña
                        </a>
                    </li>
                <?php elseif ($userRole === 'operador'): ?>
                    <li>
                        <a class="dropdown-item" href="<?= url('operador/perfil') ?>">
                            <i class="bi bi-person"></i> Mi Perfil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= url('operador/cambiar-password') ?>">
                            <i class="bi bi-key"></i> Cambiar Contraseña
                        </a>
                    </li>
                <?php elseif ($userRole === 'consultor'): ?>
                    <li>
                        <a class="dropdown-item" href="<?= url('consultor/perfil') ?>">
                            <i class="bi bi-person"></i> Mi Perfil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= url('consultor/cambiar-password') ?>">
                            <i class="bi bi-key"></i> Cambiar Contraseña
                        </a>
                    </li>
                <?php elseif ($userRole === 'administrador'): ?>
                    <li>
                        <a class="dropdown-item" href="<?= url('admin/perfil') ?>">
                            <i class="bi bi-person"></i> Mi Perfil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= url('admin/cambiar-password') ?>">
                            <i class="bi bi-key"></i> Cambiar Contraseña
                        </a>
                    </li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="<?= url('auth/logout') ?>">
                        <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
