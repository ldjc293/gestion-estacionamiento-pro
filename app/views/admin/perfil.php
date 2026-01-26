<?php
$pageTitle = 'Mi Perfil';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('admin/dashboard')],
    ['label' => 'Mi Perfil', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="row">
            <div class="col-md-8">
                <!-- Información Personal -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-person"></i> Información Personal
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="<?= url('admin/update-perfil') ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label fw-bold">Nombre Completo</label>
                                <div class="col-sm-9">
                                    <input type="text"
                                           class="form-control"
                                           name="nombre_completo"
                                           value="<?= htmlspecialchars($usuario->nombre_completo) ?>"
                                           required
                                           minlength="3">
                                    <small class="text-muted">Puede editar su nombre completo</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label fw-bold">Email</label>
                                <div class="col-sm-9">
                                    <input type="email"
                                           class="form-control"
                                           name="email"
                                           value="<?= htmlspecialchars($usuario->email) ?>"
                                           required>
                                    <small class="text-muted">Asegúrese de usar un email válido y único</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label fw-bold">Cédula</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($usuario->cedula ?? 'No registrada') ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label fw-bold">Teléfono</label>
                                <div class="col-sm-9">
                                    <input type="tel"
                                           class="form-control"
                                           name="telefono"
                                           value="<?= htmlspecialchars($usuario->telefono ?? '') ?>"
                                           placeholder="Ej: +58 414 123 4567">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label fw-bold">Dirección</label>
                                <div class="col-sm-9">
                                    <textarea class="form-control"
                                              name="direccion"
                                              rows="3"
                                              placeholder="Ingresa tu dirección completa"><?= htmlspecialchars($usuario->direccion ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label fw-bold">Apartamento</label>
                                <div class="col-sm-9">
                                    <select class="form-select" name="apartamento_id">
                                        <option value="">Sin apartamento asignado</option>
                                        <?php foreach ($apartamentosDisponibles as $apto): ?>
                                            <?php
                                            $aptoLabel = "{$apto['bloque']}-{$apto['escalera']}-{$apto['piso']}-{$apto['numero_apartamento']}";
                                            $selected = (isset($apartamento) && $apartamento && $apartamento['apartamento_id'] == $apto['id']) ? 'selected' : '';
                                            ?>
                                            <option value="<?= $apto['id'] ?>" <?= $selected ?>>
                                                <?= htmlspecialchars($aptoLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Seleccione el apartamento al que pertenece</small>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Apartamento Asignado (si tiene) -->
                <?php if (isset($apartamento) && $apartamento): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-building"></i> Apartamento Asignado
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <small class="text-muted">Bloque</small>
                                    <h5><?= htmlspecialchars($apartamento['bloque']) ?></h5>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Escalera</small>
                                    <h5><?= htmlspecialchars($apartamento['escalera']) ?></h5>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Piso</small>
                                    <h5><?= htmlspecialchars($apartamento['piso']) ?></h5>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Número</small>
                                    <h5><?= htmlspecialchars($apartamento['numero_apartamento']) ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Controles de Estacionamiento (si tiene) -->
                <?php if (isset($controles) && !empty($controles)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-controller"></i> Controles de Estacionamiento
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($controles as $control): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="border rounded p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h5 class="mb-0">
                                                    <i class="bi bi-controller"></i>
                                                    Control #<?= htmlspecialchars($control['numero_control_completo']) ?>
                                                </h5>
                                                <?php
                                                $estadoBadge = [
                                                    'activo' => 'success',
                                                    'suspendido' => 'warning',
                                                    'desactivado' => 'secondary',
                                                    'perdido' => 'danger',
                                                    'bloqueado' => 'dark',
                                                    'vacio' => 'light'
                                                ];
                                                $colorBadge = $estadoBadge[$control['estado']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $colorBadge ?>">
                                                    <?= ucfirst($control['estado']) ?>
                                                </span>
                                            </div>
                                            <?php if ($control['fecha_asignacion']): ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar-check"></i>
                                                    Asignado: <?= date('d/m/Y', strtotime($control['fecha_asignacion'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Cambiar Contraseña -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-key"></i> Cambiar Contraseña
                        </h6>
                    </div>
                    <div class="card-body">
                        <a href="<?= url('admin/cambiar-password') ?>" class="btn btn-outline-primary">
                            <i class="bi bi-key"></i> Cambiar mi Contraseña
                        </a>
                        <p class="text-muted mt-2 mb-0">
                            <small>
                                <i class="bi bi-info-circle"></i>
                                Se recomienda cambiar tu contraseña cada 3 meses por seguridad
                            </small>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Avatar -->
                <div class="card mb-4 text-center">
                    <div class="card-body">
                        <?php
                        $initials = '';
                        $nameParts = explode(' ', $usuario->nombre_completo);
                        if (count($nameParts) >= 2) {
                            $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
                        } else {
                            $initials = strtoupper(substr($usuario->nombre_completo, 0, 2));
                        }
                        ?>
                        <div class="mx-auto mb-3" style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: 600;">
                            <?= $initials ?>
                        </div>
                        <h5><?= htmlspecialchars($usuario->nombre_completo) ?></h5>
                        <p class="text-muted mb-0"><?= htmlspecialchars($usuario->email) ?></p>
                        <span class="badge bg-danger mt-2">admin</span>
                    </div>
                </div>

                <!-- Información del Sistema -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle"></i> Información del Sistema
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Miembro desde</small>
                            <div><?= date('d/m/Y', strtotime($usuario->fecha_registro)) ?></div>
                        </div>

                        <?php if ($usuario->ultimo_acceso): ?>
                            <div class="mb-3">
                                <small class="text-muted">Último acceso</small>
                                <div><?= date('d/m/Y H:i', strtotime($usuario->ultimo_acceso)) ?></div>
                            </div>
                        <?php endif; ?>

                        <div>
                            <small class="text-muted">ID de Usuario</small>
                            <div><code>#<?= str_pad($usuario->id, 5, '0', STR_PAD_LEFT) ?></code></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
