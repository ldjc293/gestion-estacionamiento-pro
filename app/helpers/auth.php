<?php
/**
 * Authentication and Authorization Helper Functions
 */

require_once __DIR__ . '/../../config/constants.php';

/**
 * Check if a user role has a specific permission
 *
 * @param string $permission Permission to check
 * @param string $role User role
 * @return bool True if user has permission, false otherwise
 */
function hasPermission(string $permission, string $role): bool
{
    // Define permissions for each role
    $permissions = [
        'cliente' => [
            'view_dashboard',
            'view_profile',
            'edit_profile',
            'view_payments',
            'make_payment',
            'view_monthly_payments',
            'view_apartments',
            'view_controls'
        ],
        'operador' => [
            'view_dashboard',
            'view_profile',
            'edit_profile',
            'view_payments',
            'make_payment',
            'view_monthly_payments',
            'view_apartments',
            'view_controls',
            'add_control',
            'edit_control',
            'delete_control',
            'view_clients',
            'search_clients'
        ],
        'consultor' => [
            'view_dashboard',
            'view_profile',
            'edit_profile',
            'view_payments',
            'view_monthly_payments',
            'view_apartments',
            'view_controls',
            'view_clients',
            'search_clients',
            'view_reports',
            'export_reports'
        ],
        'administrador' => [
            'view_dashboard',
            'view_profile',
            'edit_profile',
            'view_payments',
            'make_payment',
            'view_monthly_payments',
            'view_apartments',
            'add_apartment',
            'edit_apartment',
            'delete_apartment',
            'view_controls',
            'add_control',
            'edit_control',
            'delete_control',
            'view_clients',
            'search_clients',
            'add_client',
            'edit_client',
            'delete_client',
            'activate_client',
            'deactivate_client',
            'view_users',
            'add_user',
            'edit_user',
            'delete_user',
            'activate_user',
            'deactivate_user',
            'view_reports',
            'export_reports',
            'view_settings',
            'edit_settings',
            'view_logs',
            'manage_exemptions'
        ]
    ];

    // Check if role exists and has the permission
    return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
}

/**
 * Check if user is logged in
 *
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user
 *
 * @return array|null User data or null if not logged in
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return $_SESSION['user'] ?? null;
}

/**
 * Get current user role
 *
 * @return string|null User role or null if not logged in
 */
function getCurrentUserRole(): ?string
{
    $user = getCurrentUser();
    return $user['rol'] ?? null;
}

/**
 * Check if current user has a specific permission
 *
 * @param string $permission Permission to check
 * @return bool True if user has permission, false otherwise
 */
function currentUserHasPermission(string $permission): bool
{
    $role = getCurrentUserRole();
    if (!$role) {
        return false;
    }

    return hasPermission($permission, $role);
}

/**
 * Require user to be logged in, otherwise redirect to login page
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /login');
        exit;
    }
}

/**
 * Require user to have a specific permission, otherwise show error page
 *
 * @param string $permission Permission to check
 */
function requirePermission(string $permission): void
{
    requireLogin();
    
    if (!currentUserHasPermission($permission)) {
        header('HTTP/1.0 403 Forbidden');
        include __DIR__ . '/../views/errors/403.php';
        exit;
    }
}

/**
 * Require user to have a specific role, otherwise redirect to dashboard
 *
 * @param array $roles Allowed roles
 */
function requireRole(array $roles): void
{
    requireLogin();
    
    $userRole = getCurrentUserRole();
    if (!$userRole || !in_array($userRole, $roles)) {
        header('Location: /dashboard');
        exit;
    }
}

/**
 * Check if user is admin
 *
 * @return bool True if user is admin, false otherwise
 */
function isAdmin(): bool
{
    return getCurrentUserRole() === 'administrador';
}

/**
 * Check if user is operator
 *
 * @return bool True if user is operator, false otherwise
 */
function isOperator(): bool
{
    return getCurrentUserRole() === 'operador';
}

/**
 * Check if user is consultant
 *
 * @return bool True if user is consultant, false otherwise
 */
function isConsultant(): bool
{
    return getCurrentUserRole() === 'consultor';
}

/**
 * Check if user is client
 *
 * @return bool True if user is client, false otherwise
 */
function isClient(): bool
{
    return getCurrentUserRole() === 'cliente';
}