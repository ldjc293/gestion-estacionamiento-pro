<?php
/**
 * Admin Controller - Gestión de solicitudes de registro
 */

require_once __DIR__ . '/../models/SolicitudCambio.php';

class AdminSolicitudesController
{
    /**
     * Listar solicitudes de registro pendientes
     */
    public function listarSolicitudesRegistro(): void
    {
        // Verificar autenticación y permisos
        if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
            redirect('auth/login');
        }

        // Obtener solicitudes pendientes
        $solicitudesPendientes = SolicitudCambio::getSolicitudesRegistro('pendiente');

        // Debug log
        error_log("AdminSolicitudesController: Encontradas " . count($solicitudesPendientes) . " solicitudes pendientes");
        foreach ($solicitudesPendientes as $s) {
            error_log(" - Solicitud ID: {$s->id}, Tipo: {$s->tipo_solicitud}");
        }

        require_once __DIR__ . '/../views/admin/gestionar_solicitudes_registro.php';
    }

    /**
     * Aprobar solicitud de registro
     */
    public function aprobarSolicitud(): void
    {
        header('Content-Type: application/json');

        // Verificar autenticación y permisos
        if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $solicitudId = (int)($_POST['solicitud_id'] ?? 0);
        $observaciones = trim($_POST['observaciones'] ?? '');

        if ($solicitudId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de solicitud inválido']);
            return;
        }

        $solicitud = SolicitudCambio::findById($solicitudId);

        if (!$solicitud) {
            echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
            return;
        }

        if ($solicitud->aprobar($_SESSION['user_id'], $observaciones)) {
            echo json_encode(['success' => true, 'message' => 'Solicitud aprobada exitosamente. Usuario creado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al aprobar la solicitud']);
        }
    }

    /**
     * Rechazar solicitud de registro
     */
    public function rechazarSolicitud(): void
    {
        header('Content-Type: application/json');

        // Verificar autenticación y permisos
        if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $solicitudId = (int)($_POST['solicitud_id'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');

        if ($solicitudId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de solicitud inválido']);
            return;
        }

        if (empty($motivo)) {
            echo json_encode(['success' => false, 'message' => 'Debe proporcionar un motivo de rechazo']);
            return;
        }

        $solicitud = SolicitudCambio::findById($solicitudId);

        if (!$solicitud) {
            echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
            return;
        }

        if ($solicitud->rechazar($_SESSION['user_id'], $motivo)) {
            echo json_encode(['success' => true, 'message' => 'Solicitud rechazada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al rechazar la solicitud']);
        }
    }
}
