<!-- Gestión de Tarifas -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-cash-coin text-primary me-2"></i>
                        Gestión de Tarifas Mensuales
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Información actual -->
                    <div class="alert alert-info border-0">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle text-info me-2"></i>
                            <div>
                                <strong>Tarifa Actual:</strong>
                                <?php if ($tarifaActual): ?>
                                    <span class="badge bg-success">$<?= number_format($tarifaActual->monto_mensual_usd, 2) ?> USD</span>
                                    por control/mes
                                    <small class="text-muted ms-2">
                                        Vigente desde: <?= date('d/m/Y', strtotime($tarifaActual->fecha_vigencia_inicio)) ?>
                                        <?php if ($tarifaActual->fecha_vigencia_fin): ?>
                                            hasta <?= date('d/m/Y', strtotime($tarifaActual->fecha_vigencia_fin)) ?>
                                        <?php endif; ?>
                                    </small>
                                <?php else: ?>
                                    <span class="badge bg-warning">No configurada</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Crear nueva tarifa -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-plus-circle me-2"></i>
                                Crear Nueva Tarifa
                            </h6>
                        </div>
                        <div class="card-body">
                            <form action="<?= url('admin/crear-tarifa') ?>" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Monto Mensual (USD) *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number"
                                                   class="form-control"
                                                   name="monto_mensual_usd"
                                                   step="0.01"
                                                   min="0.01"
                                                   required
                                                   placeholder="1.00">
                                        </div>
                                        <small class="text-muted">Monto por control de estacionamiento</small>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Fecha de Vigencia *</label>
                                        <input type="date"
                                               class="form-control"
                                               name="fecha_vigencia_inicio"
                                               value="<?= date('Y-m-d') ?>"
                                               required>
                                        <small class="text-muted">Fecha desde la cual aplica esta tarifa</small>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Fecha de Fin (Opcional)</label>
                                        <input type="date"
                                               class="form-control"
                                               name="fecha_vigencia_fin">
                                        <small class="text-muted">Fecha hasta la cual aplica (dejar vacío para indefinido)</small>
                                    </div>
                                </div>

                                <div class="alert alert-warning border-0">
                                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                    <strong>Importante:</strong> Al crear una nueva tarifa, la tarifa anterior se desactivará automáticamente y todos los pagos futuros usarán este nuevo monto.
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>Crear Tarifa
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Historial de tarifas -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-clock-history me-2"></i>
                                Historial de Tarifas
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($tarifas)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-cash-coin text-muted" style="font-size: 3rem;"></i>
                                    <h6 class="text-muted mt-3">No hay tarifas configuradas</h6>
                                    <p class="text-muted">Crea tu primera tarifa usando el formulario arriba</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Monto USD</th>
                                                <th>Vigencia Desde</th>
                                                <th>Vigencia Hasta</th>
                                                <th>Estado</th>
                                                <th>Creado Por</th>
                                                <th>Fecha Creación</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tarifas as $tarifa): ?>
                                                <tr>
                                                    <td>
                                                        <strong>$<?= number_format($tarifa->monto_mensual_usd, 2) ?></strong>
                                                        <small class="text-muted d-block">por control/mes</small>
                                                    </td>
                                                    <td>
                                                        <?= date('d/m/Y', strtotime($tarifa->fecha_vigencia_inicio)) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($tarifa->fecha_vigencia_fin): ?>
                                                            <?= date('d/m/Y', strtotime($tarifa->fecha_vigencia_fin)) ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Indefinido</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($tarifa->activo): ?>
                                                            <span class="badge bg-success">
                                                                <i class="bi bi-check-circle me-1"></i>Activa
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">
                                                                <i class="bi bi-x-circle me-1"></i>Inactiva
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($tarifa->creado_por_nombre ?? 'Sistema') ?>
                                                    </td>
                                                    <td>
                                                        <?= date('d/m/Y H:i', strtotime($tarifa->fecha_creacion)) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($tarifa->activo && $tarifa->id !== ($tarifaActual->id ?? null)): ?>
                                                            <form action="<?= url('admin/desactivar-tarifa') ?>" method="POST" class="d-inline"
                                                                  onsubmit="return confirm('¿Estás seguro de desactivar esta tarifa? Los pagos futuros usarán la tarifa anterior.')">
                                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                                <input type="hidden" name="tarifa_id" value="<?= $tarifa->id ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                    <i class="bi bi-x-circle me-1"></i>Desactivar
                                                                </button>
                                                            </form>
                                                        <?php elseif (!$tarifa->activo): ?>
                                                            <span class="text-muted small">Inactiva</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success small">Tarifa actual</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Información adicional -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-body">
                                    <h6 class="card-title text-info">
                                        <i class="bi bi-lightbulb me-2"></i>
                                        ¿Cómo funcionan las tarifas?
                                    </h6>
                                    <ul class="mb-0 small">
                                        <li>El monto se calcula por <strong>control de estacionamiento</strong></li>
                                        <li>Un apartamento con 2 controles paga 2x el monto mensual</li>
                                        <li>Los cambios de tarifa afectan <strong>inmediatamente</strong> a todos los pagos</li>
                                        <li>Las mensualidades existentes mantienen su monto original</li>
                                        <li>Solo los <strong>nuevos pagos</strong> usan la tarifa actual</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-body">
                                    <h6 class="card-title text-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Consideraciones importantes
                                    </h6>
                                    <ul class="mb-0 small">
                                        <li>Las tarifas no afectan pagos ya realizados</li>
                                        <li>Se recomienda comunicar cambios a los residentes</li>
                                        <li>Los aumentos entran en vigencia desde la fecha especificada</li>
                                        <li>Mantén un historial de cambios para auditorías</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>