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

        if (!searchInput) return;

        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.trim();

            clearTimeout(debounceTimer);

            if (searchTerm.length < 2) {
                suggestionsList.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                this.buscarClientes(searchTerm);
            }, 300);
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
            const response = await fetch(`${baseUrl}operador/buscar-cliente?q=${encodeURIComponent(searchTerm)}`);
            const data = await response.json();

            const suggestionsList = document.getElementById('suggestionsList');

            if (data.length > 0) {
                let html = '';
                data.forEach(cliente => {
                    const displayName = cliente.nombre_completo +
                                      (cliente.email ? ' (' + cliente.email + ')' : '') +
                                      (cliente.apartamento ? ' - ' + cliente.apartamento : '');
                    html += `<a href="#" class="list-group-item list-group-item-action"
                               onclick="registrarPago.seleccionarCliente('${cliente.id}', '${cliente.nombre_completo.replace(/'/g, "\\'")}')"
                               data-cliente-id="${cliente.id}">
                                <div class="fw-bold">${cliente.nombre_completo}</div>
                                <small class="text-muted">${cliente.email || ''} ${cliente.apartamento ? ' - ' + cliente.apartamento : ''}</small>
                            </a>`;
                });
                suggestionsList.innerHTML = html;
                suggestionsList.style.display = 'block';
            } else {
                suggestionsList.style.display = 'none';
            }
        } catch (error) {
            console.error('Error en autocompletar:', error);
            document.getElementById('suggestionsList').style.display = 'none';
        }
    };

    // Seleccionar cliente
    window.registrarPago.seleccionarCliente = function(clienteId, clienteNombre) {
        document.getElementById('clienteSearch').value = clienteNombre;
        document.getElementById('suggestionsList').style.display = 'none';
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