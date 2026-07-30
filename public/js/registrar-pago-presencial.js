/**
 * JavaScript para la página de registrar pago presencial
 * Extiende la funcionalidad básica con características avanzadas
 */

// Extender la clase existente con funcionalidades avanzadas
if (window.registrarPago) {
    // Agregar funcionalidades avanzadas a la clase existente
    window.registrarPago.setupAdvancedFeatures = function() {
        this.setupAutocomplete();
        this.setupAdvancedButtons();
    };

    // Autocompletar de clientes
    window.registrarPago.setupAutocomplete = function() {
        let debounceTimer;
        const searchInput = document.getElementById('clienteSearch');
        const suggestionsList = document.getElementById('suggestionsList');

        if (!searchInput || !suggestionsList) return;

        const executeSearch = (searchTerm) => {
            clearTimeout(debounceTimer);

            if (searchTerm.length < 1) {
                suggestionsList.style.display = 'none';
                suggestionsList.innerHTML = '';
                return;
            }

            debounceTimer = setTimeout(() => {
                this.buscarClientes(searchTerm);
            }, 200);
        };

        searchInput.addEventListener('input', (e) => {
            executeSearch(e.target.value.trim());
        });

        searchInput.addEventListener('focus', (e) => {
            const searchTerm = e.target.value.trim();
            if (searchTerm.length >= 1) {
                executeSearch(searchTerm);
            }
        });

        // Cerrar autocompletar al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#autocompleteResults') && !e.target.closest('#clienteSearch')) {
                suggestionsList.style.display = 'none';
            }
        });
    };

    // Búsqueda de clientes
    window.registrarPago.buscarClientes = async function(searchTerm) {
        try {
            const response = await fetch(`${baseUrl}/operador/buscar-cliente?q=${encodeURIComponent(searchTerm)}`);
            const data = await response.json();

            const suggestionsList = document.getElementById('suggestionsList');
            if (!suggestionsList) return;

            if (Array.isArray(data) && data.length > 0) {
                let html = '';
                data.forEach(cliente => {
                    const nombreEscaped = cliente.nombre_completo.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                    const emailInfo = cliente.email ? `<span class="text-muted small me-2"><i class="bi bi-envelope"></i> ${cliente.email}</span>` : '';
                    const cedulaInfo = cliente.cedula ? `<span class="text-muted small me-2"><i class="bi bi-card-text"></i> ${cliente.cedula}</span>` : '';
                    const aptoInfo = cliente.apartamento ? `<span class="badge bg-secondary px-2 py-1">${cliente.apartamento}</span>` : '';

                    html += `<button type="button" class="list-group-item list-group-item-action py-3 text-start border-bottom"
                               onclick="registrarPago.seleccionarCliente('${cliente.id}', '${nombreEscaped}')"
                               data-cliente-id="${cliente.id}">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-primary fs-6">${cliente.nombre_completo}</strong>
                                    ${aptoInfo}
                                </div>
                                <div class="small">
                                    ${emailInfo} ${cedulaInfo}
                                </div>
                            </button>`;
                });
                suggestionsList.innerHTML = html;
                suggestionsList.style.display = 'block';
            } else {
                suggestionsList.innerHTML = '<div class="list-group-item text-muted small p-3 text-center">No se encontraron clientes coincidentes</div>';
                suggestionsList.style.display = 'block';
            }
        } catch (error) {
            console.error('Error en autocompletar:', error);
            const suggestionsList = document.getElementById('suggestionsList');
            if (suggestionsList) suggestionsList.style.display = 'none';
        }
    };

    // Seleccionar cliente
    window.registrarPago.seleccionarCliente = function(clienteId, clienteNombre) {
        document.getElementById('clienteSearch').value = clienteNombre;
        const hiddenId = document.getElementById('selectedClienteId');
        if (hiddenId) {
            hiddenId.value = clienteId;
        }
        const suggestionsList = document.getElementById('suggestionsList');
        if (suggestionsList) {
            suggestionsList.style.display = 'none';
        }
        document.getElementById('searchForm').submit();
    };

    // Generar mensualidades futuras
    window.registrarPago.generarMensualidadesFuturas = function(meses) {
        const generandoDiv = document.getElementById('generando-mensuales');
        if (generandoDiv) {
            generandoDiv.style.display = 'block';
        }

        setTimeout(() => {
            const url = new URL(window.location);
            url.searchParams.set('generar_futuras', meses);
            window.location.href = url.toString();
        }, 1500);
    };

    // Configurar botones avanzados
    window.registrarPago.setupAdvancedButtons = function() {
        // Botón para generar mensualidades futuras
        const generarButtons = document.querySelectorAll('[data-generar-meses]');
        generarButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const meses = button.dataset.generarMeses;
                this.generarMensualidadesFuturas(meses);
            });
        });

        // Actualizar símbolo de moneda
        const monedaSelect = document.getElementById('moneda');
        if (monedaSelect) {
            // Función para actualizar el símbolo
            const actualizarSimboloMoneda = () => {
                const symbol = document.getElementById('moneda-symbol');
                if (symbol) {
                    symbol.textContent = monedaSelect.value === 'USD' ? '$' : 'Bs';
                }
            };

            // Actualizar inmediatamente al cargar
            actualizarSimboloMoneda();

            // Actualizar cuando cambie la selección
            monedaSelect.addEventListener('change', actualizarSimboloMoneda);
        }
    };

    // Inicializar funcionalidades avanzadas
    window.registrarPago.setupAdvancedFeatures();
}

// Funciones globales ahora definidas en el script inline de la página