<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - <?= APP_NAME ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --sidebar-width: 260px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 0;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h4 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            color: white;
        }

        .sidebar-header .subtitle {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .sidebar-menu {
            padding: 16px 0;
        }

        .menu-section {
            margin-bottom: 24px;
        }

        .menu-section-title {
            padding: 8px 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.5px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: white;
            border-left-color: var(--primary-color);
        }

        .menu-item.active {
            background-color: rgba(37, 99, 235, 0.15);
            color: white;
            border-left-color: var(--primary-color);
        }

        .menu-item i {
            width: 24px;
            font-size: 18px;
            margin-right: 12px;
        }

        .menu-item .badge {
            margin-left: auto;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        /* Topbar */
        .topbar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .topbar-left h5 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
        }

        .topbar-left .breadcrumb {
            margin: 4px 0 0 0;
            padding: 0;
            background: none;
            font-size: 13px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-icon {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }

        .topbar-icon:hover {
            background: #e2e8f0;
        }

        .topbar-icon .badge {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--danger-color);
            color: white;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .user-menu:hover {
            background: #f1f5f9;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-info .name {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }

        .user-info .role {
            font-size: 12px;
            color: #64748b;
            text-transform: capitalize;
        }

        /* Content Area */
        .content-area {
            padding: 32px;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 20px 24px;
            font-weight: 600;
            color: #1e293b;
        }

        .card-body {
            padding: 24px;
        }

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .stat-card .label {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .stat-card .change {
            font-size: 13px;
            font-weight: 500;
        }

        .stat-card .change.positive {
            color: var(--success-color);
        }

        .stat-card .change.negative {
            color: var(--danger-color);
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }

        .alert-dismissible .btn-close {
            padding: 20px;
        }

        /* Buttons */
        .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        /* Tables */
        .table {
            font-size: 14px;
        }

        .table thead th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
            color: #475569;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: background 0.2s;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            font-weight: 500;
            border-radius: 6px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content-area {
                padding: 16px;
            }

            .topbar {
                padding: 12px 16px;
            }
        }

        /* Mobile toggle */
        .mobile-toggle {
            display: none;
            background: var(--primary-color);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .mobile-toggle {
                display: flex;
            }
        }

        /* Loading spinner */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
    </style>

    <?php if (isset($additionalCSS)): ?>
        <?= $additionalCSS ?>
    <?php endif; ?>

    <!-- Global JavaScript Constants -->
    <script>
        const URL_BASE = '<?= APP_URL ?>';

        // Format currency
        function formatUSD(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        }

        function formatBs(amount) {
            return new Intl.NumberFormat('es-VE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount) + ' Bs';
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Copiado al portapapeles', 'success');
            }).catch(err => {
                console.error('Error al copiar: ', err);
                showToast('Error al copiar', 'danger');
            });
        }

        function formatBs(amount) {
            return new Intl.NumberFormat('es-VE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount) + ' Bs';
        }

        // Format date
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('es-ES', options);
        }

        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Copiado al portapapeles', 'success');
            });
        }

        // Simple toast notification
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toastContainer') || createToastContainer();
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');

            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;

            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(container);
            return container;
        }

        // Loading state for buttons
        function setButtonLoading(button, loading = true) {
            if (loading) {
                button.disabled = true;
                button.dataset.originalText = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Procesando...';
            } else {
                button.disabled = false;
                button.innerHTML = button.dataset.originalText || button.innerHTML;
            }
        }

        // Debounce function for search inputs
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    </script>

    <!-- Confirmaciones y prevención de doble-submit -->
    <script src="<?= url('public/js/confirm-actions.js') ?>"></script>
</head>
<body>
