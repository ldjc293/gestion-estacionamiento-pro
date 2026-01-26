<?php
$pageTitle = 'Registrar Pago Presencial';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('operador/dashboard')],
    ['label' => 'Registrar Pago Presencial', 'url' => '#']
];

require_once __DIR__ . '/../layouts/header.php';
?>

<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-area">
        <?php require_once __DIR__ . '/../layouts/alerts.php'; ?>

        <div class="container-fluid">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">
                                <i class="bi bi-cash-coin text-primary"></i>
                                <?php if (($_GET['modo'] ?? '') === 'adelantado'): ?>
                                    Pagos Adelantados
                                <?php else: ?>
                                    Registrar Pago Presencial
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted mb-0">Registra pagos presenciales de clientes del estacionamiento</p>
                        </div>
                        <?php if (isset($cliente)): ?>
                        <a href="<?= url('operador/registrar-pago-presencial') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Buscar otro cliente
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (($_GET['modo'] ?? '') === 'adelantado'): ?>
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-calendar-plus fs-4 text-info me-3"></i>
                    <div>
                        <h6 class="mb-1 text-info">Modo de Pagos Adelantados</h6>
                        <small>Registra pagos de meses futuros. Se mostrarán hasta 12 meses adelante incluyendo los meses pendientes actuales.</small>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <?php if (!isset($cliente)): ?>
                                <!-- Client Search Section -->
                                <?php include __DIR__ . '/components/client_search.php'; ?>
                            <?php else: ?>
                                <!-- Client Found & Payment Form -->
                                <?php include __DIR__ . '/components/client_found.php'; ?>
                                <?php include __DIR__ . '/components/payment_form.php'; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <?php include __DIR__ . '/components/payment_sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const baseUrl = URL_BASE;
const tasaBCV = <?= $tasaBCV ?>;
const CSRF_TOKEN = '<?= generateCSRFToken() ?>';

// Clase simplificada para inicialización inmediata
class RegistrarPagoPresencial {
    constructor() {
        this.tasaBCV = 0;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.updateContador();
        // Validar selección secuencial al cargar la página
        setTimeout(() => this.validarSeleccionSecuencial(), 100);
    }

    setupEventListeners() {
        // Configurar event listeners básicos
        this.setupTotalCalculation();
        this.setupCurrencyConversion();
        this.setupFormValidation();
        this.setupSelectionButtons();
    }

    setupTotalCalculation() {
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('mensualidad-checkbox')) {
                this.validarSeleccionSecuencial();
                this.updateContador();
                this.calcularTotal();
            }
        });
    }

    validarSeleccionSecuencial() {
        const items = Array.from(document.querySelectorAll('.mensualidad-item'));
        
        if (items.length === 0) return;
        
        // Ordenar por fecha (usando el atributo data-mes en formato YYYY-MM)
        items.sort((a, b) => {
            const fechaA = new Date(a.dataset.mes + '-01');
            const fechaB = new Date(b.dataset.mes + '-01');
            return fechaA - fechaB;
        });
        
        // Encontrar el primer mes no seleccionado
        let primerMesNoSeleccionado = -1;
        
        items.forEach((item, index) => {
            const checkbox = item.querySelector('.mensualidad-checkbox');
            
            if (!checkbox.checked && primerMesNoSeleccionado === -1) {
                primerMesNoSeleccionado = index;
            }
        });
        
        // Deshabilitar todos los meses después del primer mes no seleccionado
        items.forEach((item, index) => {
            const checkbox = item.querySelector('.mensualidad-checkbox');
            
            // Si hay un mes no seleccionado y este mes está después de él
            if (primerMesNoSeleccionado !== -1 && index > primerMesNoSeleccionado && !checkbox.checked) {
                checkbox.disabled = true;
                item.classList.add('disabled-month');
            } else {
                // Solo habilitar si no está ya seleccionado
                if (!checkbox.checked) {
                    checkbox.disabled = false;
                }
                item.classList.remove('disabled-month');
            }
        });
    }

    setupCurrencyConversion() {
        const monedaSelect = document.getElementById('moneda');
        const montoInput = document.getElementById('monto');

        if (monedaSelect && montoInput) {
            // Función para actualizar el símbolo de moneda
            const actualizarSimboloMoneda = () => {
                const symbol = document.getElementById('moneda-symbol');
                if (symbol) {
                    symbol.textContent = monedaSelect.value === 'USD' ? '$' : 'Bs';
                }
            };

            // Actualizar símbolo inmediatamente
            actualizarSimboloMoneda();

            // Actualizar símbolo cuando cambie la moneda
            monedaSelect.addEventListener('change', actualizarSimboloMoneda);

            // Actualizar conversión cuando cambien los valores
            [monedaSelect, montoInput].forEach(element => {
                element.addEventListener('change', () => this.actualizarConversion());
                element.addEventListener('input', () => this.actualizarConversion());
            });
        }
    }

    setupFormValidation() {
        const form = document.getElementById('formPago');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            const checkboxes = document.querySelectorAll('.mensualidad-checkbox:checked');

            if (checkboxes.length === 0) {
                e.preventDefault();
                this.showAlert('Debes seleccionar al menos una mensualidad', 'danger');
                return false;
            }

            const btn = document.getElementById('btnSubmit');
            if (btn) {
                this.setButtonLoading(btn, true);
            }
        });
    }

    setupSelectionButtons() {
        // Botones de selección rápida
        const buttons = {
            'seleccionar-todos': () => this.seleccionarTodos(),
            'deseleccionar-todos': () => this.deseleccionarTodos(),
            'seleccionar-pendientes': () => this.seleccionarPorTipo('pendiente'),
            'seleccionar-futuros': () => this.seleccionarPorTipo('futuro'),
            'seleccionar-3-meses': () => this.seleccionarSiguientesMeses(3),
            'seleccionar-6-meses': () => this.seleccionarProximosMeses(6),
            'seleccionar-rango-4-11': () => this.seleccionarPorRango(3, 11),
            'seleccionar-ultimos-6': () => this.seleccionarUltimosMeses(6)
        };

        Object.keys(buttons).forEach(id => {
            const button = document.getElementById(id);
            if (button) {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    buttons[id]();
                });
            }
        });

        // Botón para actualizar tasa BCV
        const btnActualizarTasa = document.getElementById('btnActualizarTasa');
        if (btnActualizarTasa) {
            btnActualizarTasa.addEventListener('click', (e) => {
                e.preventDefault();
                this.actualizarTasaBCV();
            });
        }
    }

    seleccionarTodos() {
        const checkboxes = document.querySelectorAll('.mensualidad-checkbox');
        checkboxes.forEach(checkbox => {
            if (!checkbox.disabled) {
                checkbox.checked = true;
            }
        });
        this.validarSeleccionSecuencial();
        this.updateContador();
    }

    deseleccionarTodos() {
        const checkboxes = document.querySelectorAll('.mensualidad-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        this.validarSeleccionSecuencial();
        this.updateContador();
    }

    seleccionarPorTipo(tipo) {
        document.querySelectorAll('.mensualidad-checkbox').forEach(checkbox => {
            const item = checkbox.closest('.mensualidad-item');
            if (item && item.dataset.tipo === tipo && !checkbox.disabled) {
                checkbox.checked = true;
            } else {
                checkbox.checked = false;
            }
        });
        this.validarSeleccionSecuencial();
        this.updateContador();
    }

    seleccionarSiguientesMeses(cantidad) {
        const items = Array.from(document.querySelectorAll('.mensualidad-item'));
        this.deseleccionarTodos();

        items.slice(0, cantidad).forEach(item => {
            const checkbox = item.querySelector('.mensualidad-checkbox');
            if (checkbox && !checkbox.disabled) {
                checkbox.checked = true;
            }
        });
        this.validarSeleccionSecuencial();
        this.updateContador();
    }

    seleccionarProximosMeses(cantidad) {
        this.deseleccionarTodos();
        const items = Array.from(document.querySelectorAll('.mensualidad-item'));
        const hoy = new Date();

        items.forEach(item => {
            const checkbox = item.querySelector('.mensualidad-checkbox');
            if (checkbox && !checkbox.disabled) {
                const mesTexto = item.dataset.mes;
                const fechaItem = new Date(mesTexto + ' 1');
                const mesesDiff = Math.floor((fechaItem - hoy) / (1000 * 60 * 60 * 24 * 30));

                if (mesesDiff >= 0 && mesesDiff < cantidad) {
                    checkbox.checked = true;
                }
            }
        });
        this.validarSeleccionSecuencial();
        this.updateContador();
    }

    seleccionarPorRango(mesesInicio, mesesFin) {
        this.deseleccionarTodos();
        const items = Array.from(document.querySelectorAll('.mensualidad-item'));
        const hoy = new Date();

        items.forEach(item => {
            const checkbox = item.querySelector('.mensualidad-checkbox');
            if (checkbox && !checkbox.disabled) {
                const mesTexto = item.dataset.mes;
                const fechaItem = new Date(mesTexto + ' 1');
                const mesesDiff = Math.floor((fechaItem - hoy) / (1000 * 60 * 60 * 24 * 30));

                if (mesesDiff >= mesesInicio && mesesDiff <= mesesFin) {
                    checkbox.checked = true;
                }
            }
        });
        this.validarSeleccionSecuencial();
        this.updateContador();
    }

    seleccionarUltimosMeses(cantidad) {
        this.deseleccionarTodos();
        const items = Array.from(document.querySelectorAll('.mensualidad-item'));

        items.slice(-cantidad).forEach(item => {
            const checkbox = item.querySelector('.mensualidad-checkbox');
            if (checkbox && !checkbox.disabled) {
                checkbox.checked = true;
            }
        });
        this.validarSeleccionSecuencial();
        this.updateContador();
    }

    updateContador() {
        const checkboxes = document.querySelectorAll('.mensualidad-checkbox:checked');
        const count = checkboxes.length;

        const countElement = document.getElementById('mensualidadesCount');
        if (countElement) {
            countElement.textContent = count;
        }

        const sidebarCount = document.getElementById('sidebar-mensualidades');
        if (sidebarCount) {
            sidebarCount.textContent = count;
        }

        this.calcularTotal();
    }

    calcularTotal() {
        const checkboxes = document.querySelectorAll('.mensualidad-checkbox:checked');
        let totalUSD = 0;

        checkboxes.forEach(checkbox => {
            const item = checkbox.closest('.mensualidad-item');
            if (item) {
                totalUSD += parseFloat(item.dataset.monto || 0);
            }
        });

        const totalBS = totalUSD * this.tasaBCV;

        const totalUSDElement = document.getElementById('totalUSD');
        const totalBSElement = document.getElementById('totalBS');
        const montoInput = document.getElementById('monto');

        if (totalUSDElement) {
            totalUSDElement.textContent = this.formatUSD(totalUSD);
        }

        if (totalBSElement) {
            totalBSElement.textContent = this.formatBs(totalBS);
        }

        const sidebarTotal = document.getElementById('sidebar-total');
        if (sidebarTotal) {
            sidebarTotal.textContent = this.formatUSD(totalUSD);
        }

        if (montoInput) {
            montoInput.value = totalUSD.toFixed(2);
        }

        this.actualizarConversion();
    }

    actualizarConversion() {
        const moneda = document.getElementById('moneda')?.value;
        const monto = parseFloat(document.getElementById('monto')?.value) || 0;
        const divConversion = document.getElementById('montoConversion');

        if (!divConversion) return;

        if (monto > 0) {
            if (moneda === 'USD') {
                const montoBs = monto * this.tasaBCV;
                divConversion.textContent = `Equivalente: ${this.formatBs(montoBs)}`;
            } else {
                const montoUSD = monto / this.tasaBCV;
                divConversion.textContent = `Equivalente: ${this.formatUSD(montoUSD)}`;
            }
        } else {
            divConversion.textContent = '';
        }
    }

    setButtonLoading(button, loading) {
        if (loading) {
            button.disabled = true;
            button.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
        } else {
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-check-circle"></i> Registrar y Aprobar Pago';
        }
    }

    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alertDiv);

        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    formatUSD(amount) {
        return '$' + amount.toFixed(2);
    }

    formatBs(amount) {
        return amount.toFixed(2) + ' Bs';
    }

    setTasaBCV(tasa) {
        this.tasaBCV = tasa;
    }

    async actualizarTasaBCV() {
        const btn = document.getElementById('btnActualizarTasa');
        const actualizandoDiv = document.getElementById('actualizandoTasa');
        const tasaElement = document.getElementById('tasaBCV');
        const fechaElement = document.getElementById('tasaFecha');

        if (!btn || !actualizandoDiv) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Consultando BCV...';
        actualizandoDiv.style.display = 'block';

        let response; // Declarar fuera del try para acceso en catch

        try {
            const csrfToken = this.getCsrfToken();
            console.log('Enviando CSRF token:', csrfToken);

            response = await fetch(`${baseUrl}/operador/actualizar-tasa-bcv`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ csrf_token: csrfToken })
            });

            // Verificar si la respuesta es JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // Mostrar el contenido de la respuesta para debugging
                const text = await response.text();
                console.error('Respuesta no JSON:', text.substring(0, 500));
                throw new Error('Respuesta del servidor no es JSON válida');
            }

            const data = await response.json();
            console.log('Respuesta del servidor:', data);

            // Verificar si hay error de autenticación
            if (response.status === 401 && data.auth_error) {
                this.showAlert(data.message || 'Sesión expirada. Recarga la página.', 'warning');
                return;
            }

            if (data.success) {
                if (tasaElement) {
                    tasaElement.textContent = this.formatBs(data.nueva_tasa);
                }
                if (fechaElement) {
                    const fuente = data.fuente ? ` (${data.fuente})` : '';
                    fechaElement.textContent = `Actualizado: ${data.fecha}${fuente}`;
                }

                this.tasaBCV = data.nueva_tasa;
                this.actualizarConversion();

                // Mostrar información detallada de la actualización
                let mensaje = 'Tasa BCV actualizada correctamente';
                if (data.fuente) {
                    mensaje += ` desde ${data.fuente}`;
                }
                if (data.variacion !== undefined) {
                    const signo = data.variacion >= 0 ? '+' : '';
                    mensaje += ` (Variación: ${signo}${data.variacion}%)`;
                }

                this.showAlert(mensaje, 'success');
            } else {
                throw new Error(data.message || 'Error al actualizar la tasa');
            }
        } catch (error) {
            console.error('Error actualizando tasa:', error);
            if (response) {
                console.error('Response status:', response.status);
                console.error('Response headers:', response.headers);
            }
            this.showAlert(error.message || 'Error al actualizar la tasa BCV. Intenta nuevamente.', 'danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
            actualizandoDiv.style.display = 'none';
        }
    }

    getCsrfToken() {
        // Primero intentar obtener del token global definido en header.php
        if (typeof CSRF_TOKEN !== 'undefined' && CSRF_TOKEN) {
            return CSRF_TOKEN;
        }
        // Fallback: buscar en el formulario
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        return tokenInput ? tokenInput.value : '';
    }
}

// Initialize the payment system immediately
window.registrarPago = new RegistrarPagoPresencial();
if (window.registrarPago && window.registrarPago.setTasaBCV) {
    window.registrarPago.setTasaBCV(tasaBCV);
}

// Global functions for immediate availability with fallbacks
function actualizarTasaBCV() {
    if (window.registrarPago && window.registrarPago.actualizarTasaBCV) {
        window.registrarPago.actualizarTasaBCV();
    } else {
        // Fallback básico si el sistema no está listo
        console.warn('Sistema de pago no disponible, intentando recargar...');
        // Simular una actualización básica
        const btn = document.getElementById('btnActualizarTasa');
        const tasaElement = document.getElementById('tasaBCV');
        if (btn && tasaElement) {
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
                alert('Función no disponible. Recarga la página e intenta nuevamente.');
            }, 2000);
        }
    }
}

function seleccionarTodos() {
    if (window.registrarPago && window.registrarPago.seleccionarTodos) {
        window.registrarPago.seleccionarTodos();
    } else {
        // Fallback básico
        document.querySelectorAll('.mensualidad-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
        actualizarContador();
    }
}

function deseleccionarTodos() {
    if (window.registrarPago && window.registrarPago.deseleccionarTodos) {
        window.registrarPago.deseleccionarTodos();
    } else {
        // Fallback básico
        document.querySelectorAll('.mensualidad-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        actualizarContador();
    }
}

function seleccionarPorTipo(tipo) {
    if (window.registrarPago && window.registrarPago.seleccionarPorTipo) {
        window.registrarPago.seleccionarPorTipo(tipo);
    }
}

function seleccionarSiguientesMeses(cantidad) {
    if (window.registrarPago && window.registrarPago.seleccionarSiguientesMeses) {
        window.registrarPago.seleccionarSiguientesMeses(cantidad);
    }
}

function seleccionarProximosMeses(cantidad) {
    if (window.registrarPago && window.registrarPago.seleccionarProximosMeses) {
        window.registrarPago.seleccionarProximosMeses(cantidad);
    }
}

function seleccionarPorRango(inicio, fin) {
    if (window.registrarPago && window.registrarPago.seleccionarPorRango) {
        window.registrarPago.seleccionarPorRango(inicio, fin);
    }
}

function seleccionarUltimosMeses(cantidad) {
    if (window.registrarPago && window.registrarPago.seleccionarUltimosMeses) {
        window.registrarPago.seleccionarUltimosMeses(cantidad);
    }
}

function actualizarContador() {
    if (window.registrarPago && window.registrarPago.updateContador) {
        window.registrarPago.updateContador();
    } else {
        // Fallback básico
        const checkboxes = document.querySelectorAll('.mensualidad-checkbox:checked');
        const count = checkboxes.length;
        const countElement = document.getElementById('mensualidadesCount');
        if (countElement) {
            countElement.textContent = count;
        }
    }
}

function calcularTotal() {
    if (window.registrarPago && window.registrarPago.calcularTotal) {
        window.registrarPago.calcularTotal();
    }
}

function actualizarConversion() {
    if (window.registrarPago && window.registrarPago.actualizarConversion) {
        window.registrarPago.actualizarConversion();
    }
}

function seleccionarCliente(id, nombre) {
    if (window.registrarPago && window.registrarPago.seleccionarCliente) {
        window.registrarPago.seleccionarCliente(id, nombre);
    }
}

function generarMensualidadesFuturas(meses) {
    if (window.registrarPago && window.registrarPago.generarMensualidadesFuturas) {
        window.registrarPago.generarMensualidadesFuturas(meses);
    }
}
</script>
<script src="<?= url('js/registrar-pago-presencial.js') ?>"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
