    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery (opcional, solo si se necesita) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Common JS -->
    <script>
        // Mobile sidebar toggle
        document.getElementById('mobileToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Confirm delete/dangerous actions
        function confirmAction(message = '¿Estás seguro de realizar esta acción?') {
            return confirm(message);
        }

        // AJAX helper
        async function fetchJSON(url, options = {}) {
            try {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'Content-Type': 'application/json',
                        ...options.headers
                    }
                });
    
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
    
                return await response.json();
            } catch (error) {
                console.error('Fetch error:', error);
                throw error;
            }
        }
    
        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return false;
    
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return false;
            }
    
            return true;
        }
    
        // Number formatter
        function formatNumber(number, decimals = 2) {
            return Number(number).toFixed(decimals);
        }

        // Print helper
        function printElement(elementId) {
            const content = document.getElementById(elementId);
            if (!content) return;

            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Imprimir</title>');
            printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
            printWindow.document.write('</head><body>');
            printWindow.document.write(content.innerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }

        // Check if user is online
        window.addEventListener('online', () => {
            showToast('Conexión restablecida', 'success');
        });

        window.addEventListener('offline', () => {
            showToast('Sin conexión a Internet', 'warning');
        });
    </script>

    <?php if (isset($additionalJS)): ?>
        <?= $additionalJS ?>
    <?php endif; ?>

</body>
</html>
