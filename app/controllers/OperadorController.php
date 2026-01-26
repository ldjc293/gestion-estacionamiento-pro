<?php
/**
 * OperadorController - Funcionalidades para operadores
 *
 * Aprobar/rechazar pagos, registrar pagos presenciales, gestionar solicitudes
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/Mensualidad.php';
require_once __DIR__ . '/../models/Control.php';
require_once __DIR__ . '/../models/SolicitudCambio.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';

class OperadorController
{
    /**
     * Verificar que el usuario esté autenticado como operador
     */
    private function checkAuth(): ?Usuario
    {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['operador', 'administrador'])) {
            $this->handleAuthFailure();
            return null;
        }

        $usuario = Usuario::findById($_SESSION['user_id']);

        if (!$usuario || !$usuario->activo) {
            session_destroy();
            $this->handleAuthFailure();
            return null;
        }

        // 🔒 SEGURIDAD CRÍTICA: Verificar si el usuario debe cambiar contraseña obligatoriamente
        require_once __DIR__ . '/AuthController.php';
        AuthController::forzarCambioPasswordSiNecesario($usuario);

        return $usuario;
    }

    /**
     * Manejar fallo de autenticación (diferente para AJAX vs normal requests)
     */
    private function handleAuthFailure(): void
    {
        // Verificar si es una petición AJAX
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        error_log("Auth failure - Is AJAX: " . ($isAjax ? 'yes' : 'no') . ", X-Requested-With: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'not set'));

        if ($isAjax) {
            // Para AJAX, devolver JSON con error de autenticación
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Sesión expirada. Por favor, recarga la página e inicia sesión nuevamente.',
                'auth_error' => true
            ]);
            exit;
        } else {
            // Para requests normales, redirigir al login
            redirect('auth/login');
        }
    }

    /**
     * Dashboard del operador
     */
    public function dashboard(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Obtener pagos pendientes de aprobación
        $pagosPendientes = Pago::getPendientesAprobar();
        if (!is_array($pagosPendientes)) {
            $pagosPendientes = [];
        }

        // Estadísticas del día
        $estadisticasHoy = $this->getEstadisticasHoy();
        if (!is_array($estadisticasHoy)) {
            $estadisticasHoy = [];
        }

        // Estadísticas de morosidad
        $estadisticasMorosidad = $this->getEstadisticasMorosidad();
        if (!is_array($estadisticasMorosidad)) {
            $estadisticasMorosidad = [];
        }

        // Solicitudes pendientes
        $solicitudesPendientes = $this->getSolicitudesPendientes();
        if (!is_array($solicitudesPendientes)) {
            $solicitudesPendientes = [];
        }

        // Últimas actividades
        $ultimasActividades = $this->getUltimasActividades(10);
        if (!is_array($ultimasActividades)) {
            $ultimasActividades = [];
        }

        require_once __DIR__ . '/../views/operador/dashboard.php';
    }

    /**
     * Lista de pagos pendientes de aprobación
     */
    public function pagosPendientes(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $pagos = Pago::getPendientesAprobar();
        if (!is_array($pagos)) {
            $pagos = [];
        }

        require_once __DIR__ . '/../views/operador/pagos_pendientes.php';
    }

    /**
     * Ver detalle de pago para aprobar/rechazar
     */
    public function revisarPago(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $pagoId = intval($_GET['id'] ?? 0);

        if (!$pagoId) {
            redirect('operador/pagos-pendientes');
            return;
        }

        $pago = Pago::findById($pagoId);

        if (!$pago) {
            $_SESSION['error'] = 'Pago no encontrado';
            redirect('operador/pagos-pendientes');
            return;
        }

        // Obtener ID de usuario desde apartamento_usuario
        $sql = "SELECT usuario_id FROM apartamento_usuario WHERE id = ?";
        $result = Database::fetchOne($sql, [$pago->apartamento_usuario_id]);
        $usuarioId = $result['usuario_id'] ?? 0;

        // Obtener información del cliente
        $cliente = Usuario::findById($usuarioId);

        // Obtener mensualidades asociadas
        $mensualidades = Pago::getMensualidadesPago($pagoId);

        // Obtener otros pagos del cliente
        $otrosPagos = Pago::getByUsuario($usuarioId, 5);

        require_once __DIR__ . '/../views/operador/revisar_pago.php';
    }

    /**
     * Aprobar pago
     */
    public function aprobarPago(): void
    {
        writeLog("Iniciando aprobación de pago...", 'info');

        $usuario = $this->checkAuth();
        if (!$usuario) {
            writeLog("Error aprobación: Usuario no autenticado", 'error');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            writeLog("Error aprobación: Método no es POST", 'error');
            redirect('operador/pagos-pendientes');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            writeLog("Error aprobación: Token CSRF inválido", 'error');
            error_log("DEBUG: CSRF token inválido");
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('operador/pagos-pendientes');
            return;
        }

        $pagoId = intval($_POST['pago_id'] ?? 0);
        writeLog("Intentando aprobar pago ID: $pagoId", 'info');

        if (!$pagoId) {
            $_SESSION['error'] = 'Pago no encontrado';
            redirect('operador/pagos-pendientes');
            return;
        }

        $pago = Pago::findById($pagoId);

        if (!$pago) {
            writeLog("Error aprobación: Pago no encontrado en BD", 'error');
            $_SESSION['error'] = 'Pago no encontrado';
            redirect('operador/pagos-pendientes');
            return;
        }
        
        if ($pago->estado_comprobante !== 'pendiente' && $pago->estado_comprobante !== 'no_aplica') {
             writeLog("Error aprobación: Estado actual es {$pago->estado_comprobante}", 'warning');
             // Si ya está aprobado, redirigir con éxito
             if ($pago->estado_comprobante === 'aprobado') {
                 $_SESSION['success'] = 'El pago ya había sido aprobado';
                 redirect('operador/pagos-pendientes');
                 return;
             }
             $_SESSION['error'] = 'Pago no válido para aprobación';
             redirect('operador/pagos-pendientes');
             return;
        }

        // Aprobar
        if ($pago->aprobar($usuario->id)) {
            $_SESSION['success'] = 'Pago aprobado correctamente';
            writeLog("Pago ID $pagoId aprobado exitosamente por operador {$usuario->email}", 'info');
        } else {
            writeLog("Error al ejecutar método aprobar() del modelo Pago", 'error');
            $_SESSION['error'] = 'Error al aprobar el pago';
        }

        redirect('operador/pagos-pendientes');
    }

    /**
     * Rechazar pago
     */
    public function rechazarPago(): void
    {
        writeLog("Iniciando rechazo de pago...", 'info');
        
        $usuario = $this->checkAuth();
        if (!$usuario) {
            writeLog("Error rechazo: Usuario no autenticado", 'error');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            writeLog("Error rechazo: Método no es POST", 'error');
            redirect('operador/pagos-pendientes');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            writeLog("Error rechazo: Token CSRF inválido", 'error');
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('operador/pagos-pendientes');
            return;
        }

        $pagoId = intval($_POST['pago_id'] ?? 0);
        $motivo = sanitize($_POST['motivo_rechazo'] ?? '');
        
        writeLog("Intentando rechazar pago ID: $pagoId. Motivo: $motivo", 'info');

        if (!$pagoId) {
            $_SESSION['error'] = 'Pago no encontrado';
            redirect('operador/pagos-pendientes');
            return;
        }

        if (empty($motivo)) {
            $_SESSION['error'] = 'Debe especificar el motivo del rechazo';
            redirect('operador/revisar-pago?id=' . $pagoId);
            return;
        }

        $pago = Pago::findById($pagoId);

        if (!$pago) {
            writeLog("Error rechazo: Pago no encontrado en BD", 'error');
            $_SESSION['error'] = 'Pago no encontrado';
            redirect('operador/pagos-pendientes');
            return;
        }
        
        if ($pago->estado_comprobante !== 'pendiente' && $pago->estado_comprobante !== 'no_aplica') {
            writeLog("Error rechazo: Estado actual es {$pago->estado_comprobante}", 'warning');
            $_SESSION['error'] = 'Pago no válido para rechazo';
            redirect('operador/pagos-pendientes');
            return;
        }

        // Rechazar
        if ($pago->rechazar($usuario->id, $motivo)) {
            $_SESSION['success'] = 'Pago rechazado correctamente';
            writeLog("Pago ID $pagoId rechazado por operador {$usuario->email}. Motivo: $motivo", 'info');
        } else {
            writeLog("Error al ejecutar método rechazar() del modelo Pago", 'error');
            $_SESSION['error'] = 'Error al rechazar el pago';
        }

        redirect('operador/pagos-pendientes');
    }

    /**
     * Formulario para registrar pago presencial
     */
    public function registrarPagoPresencial(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Buscar cliente si se envió CI o email
        $cliente = null;
        $busqueda = sanitize($_GET['buscar'] ?? '');

        if ($busqueda) {
            $cliente = Usuario::buscarCliente($busqueda);

            if (!$cliente) {
                $_SESSION['error'] = 'Cliente no encontrado';
            }
        }

        // Obtener tarifa actual para cálculos dinámicos (siempre disponible)
        require_once __DIR__ . '/../models/ConfiguracionTarifa.php';
        $tarifaActual = ConfiguracionTarifa::getTarifaActual();

        // Obtener cantidad de controles del apartamento
        $cantidadControles = 0;
        if ($cliente) {
            $sqlControles = "SELECT cantidad_controles FROM apartamento_usuario WHERE usuario_id = ? AND activo = TRUE";
            $controlesData = Database::fetchOne($sqlControles, [$cliente->id]);
            $cantidadControles = $controlesData ? $controlesData['cantidad_controles'] : 0;
        }

        // Si se encontró cliente, obtener sus mensualidades pendientes (incluyendo futuras)
        $mensualidadesPendientes = [];
        $modoAdelantado = ($_GET['modo'] ?? '') === 'adelantado';

        // Manejar solicitud para generar mensualidades futuras
        if ($cliente && isset($_GET['generar_futuras'])) {
            $mesesAGenerar = intval($_GET['generar_futuras']);
            try {
                $generadas = Mensualidad::generarMensualidadesFuturas($cliente->id, $mesesAGenerar);
                $_SESSION['success'] = "Se han generado {$mesesAGenerar} mensualidades futuras para el cliente";
            } catch (Exception $e) {
                $_SESSION['error'] = "Error al generar mensualidades futuras: " . $e->getMessage();
            }
            // Redireccionar para limpiar el parámetro
            header('Location: ' . url('operador/registrar-pago-presencial') . '?buscar=' . urlencode($_GET['buscar'] ?? '') . '&modo=adelantado');
            exit;
        }

        if ($cliente) {
            // Permitir hasta 12 meses siempre
            $mesesAdelante = 12;
            $mensualidadesPendientes = Mensualidad::getMensualidadesParaPagoAdelantado($cliente->id, $mesesAdelante);
            if (!is_array($mensualidadesPendientes)) {
                $mensualidadesPendientes = [];
            }
        }

        // Obtener tasa BCV
        $tasaBCV = $this->getTasaBCVActual();

        // Asegurar que las variables de tarifa estén siempre disponibles
        if (!isset($tarifaActual)) {
            $tarifaActual = ConfiguracionTarifa::getTarifaActual();
        }
        if (!isset($cantidadControles)) {
            $cantidadControles = 0;
        }

        require_once __DIR__ . '/../views/operador/registrar_pago_presencial.php';
    }

    /**
     * Procesar registro de pago presencial
     */
    public function processRegistrarPagoPresencial(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('operador/registrar-pago-presencial');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('operador/registrar-pago-presencial');
            return;
        }

        $clienteId = intval($_POST['cliente_id'] ?? 0);
        $moneda = $_POST['moneda'] ?? '';
        $monto = floatval($_POST['monto'] ?? 0);
        $metodoPago = $_POST['metodo_pago'] ?? '';
        $referencia = sanitize($_POST['referencia'] ?? '');
        $fechaPago = $_POST['fecha_pago'] ?? date('Y-m-d');
        $mensualidadesSeleccionadas = $_POST['mensualidades'] ?? [];

        // Validaciones
        $cliente = Usuario::findById($clienteId);
        if (!$cliente || $cliente->rol !== 'cliente') {
            $_SESSION['error'] = 'Cliente inválido';
            redirect('operador/registrar-pago-presencial');
            return;
        }

        if (!in_array($moneda, ['USD', 'Bs'])) {
            $_SESSION['error'] = 'Moneda inválida';
            redirect('operador/registrar-pago-presencial');
            return;
        }

        if ($monto <= 0) {
            $_SESSION['error'] = 'El monto debe ser mayor a 0';
            redirect('operador/registrar-pago-presencial');
            return;
        }

        if (empty($mensualidadesSeleccionadas)) {
            $_SESSION['error'] = 'Debe seleccionar al menos una mensualidad';
            redirect('operador/registrar-pago-presencial');
            return;
        }

        // Validar y recalcular montos basados en tarifa actual
        require_once __DIR__ . '/../models/ConfiguracionTarifa.php';
        $tarifaActual = ConfiguracionTarifa::getTarifaActual();

        if (!$tarifaActual) {
            $_SESSION['error'] = 'No hay tarifa configurada. Contacte al administrador.';
            redirect('operador/registrar-pago-presencial');
            return;
        }

        // Obtener cantidad de controles del cliente
        $sqlControles = "SELECT cantidad_controles FROM apartamento_usuario WHERE usuario_id = ? AND activo = TRUE";
        $controlesData = Database::fetchOne($sqlControles, [$clienteId]);
        $cantidadControles = $controlesData ? $controlesData['cantidad_controles'] : 0;

        // Calcular monto esperado basado en tarifa actual
        $montoEsperadoUSD = $tarifaActual->monto_mensual_usd * count($mensualidadesSeleccionadas) * $cantidadControles;
        $tasaBCV = $this->getTasaBCVActual();

        // Validar que el monto pagado sea razonable (permitir pequeña variación por redondeo)
        $variacionPermitida = 0.10; // 10 centavos de variación
        if (abs($montoEsperadoUSD - $monto) > $variacionPermitida) {
            $_SESSION['error'] = sprintf(
                'El monto pagado (%.2f USD) no coincide con el monto esperado (%.2f USD) basado en la tarifa actual.',
                $monto,
                $montoEsperadoUSD
            );
            redirect('operador/registrar-pago-presencial');
            return;
        }

        // Obtener apartamento_usuario_id del cliente
        $sqlApartamento = "SELECT id FROM apartamento_usuario WHERE usuario_id = ? AND activo = TRUE LIMIT 1";
        $apartamentoData = Database::fetchOne($sqlApartamento, [$clienteId]);
        
        if (!$apartamentoData) {
            $_SESSION['error'] = 'El cliente no tiene un apartamento asignado';
            redirect('operador/registrar-pago-presencial');
            return;
        }
        
        $apartamentoUsuarioId = $apartamentoData['id'];
        
        // Determinar moneda_pago basado en moneda y método de pago
        $monedaPago = $moneda; // 'USD' o 'Bs'
        if ($moneda === 'Bs') {
            // Si es Bs, agregar el método de pago
            if ($metodoPago === 'transferencia') {
                $monedaPago = 'bs_transferencia';
            } elseif ($metodoPago === 'pago_movil') {
                $monedaPago = 'bs_pago_movil';
            } else {
                $monedaPago = 'bs_efectivo';
            }
        } elseif ($moneda === 'USD') {
            $monedaPago = 'usd_efectivo';
        }

        // Registrar y aprobar automáticamente (pago presencial)
        try {
            $pagoId = Pago::registrar([
                'apartamento_usuario_id' => $apartamentoUsuarioId,
                'moneda_pago' => $monedaPago,
                'fecha_pago' => $fechaPago,
                'mensualidades_ids' => $mensualidadesSeleccionadas,
                'registrado_por' => $usuario->id // Operador que registra
            ]);

            // Aprobar automáticamente
            $pago = Pago::findById($pagoId);
            $pago->aprobar($usuario->id);

            writeLog("Pago presencial registrado por operador {$usuario->email}: ID $pagoId, Moneda: $monedaPago", 'info');

            $_SESSION['success'] = 'Pago presencial registrado y aprobado correctamente';
            redirect('operador/dashboard');

        } catch (Exception $e) {
            writeLog("Error al registrar pago presencial: " . $e->getMessage(), 'error');
            $_SESSION['error'] = 'Error al registrar el pago';
            redirect('operador/registrar-pago-presencial');
        }
    }

    /**
     * Historial de todos los pagos
     */
    public function historialPagos(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Filtros
        $filtros = [
            'estado' => $_GET['estado'] ?? null,
            'mes' => $_GET['mes'] ?? null,
            'anio' => $_GET['anio'] ?? null,
            'cliente' => $_GET['cliente'] ?? null
        ];

        $pagos = Pago::getAllConFiltros($filtros);

        require_once __DIR__ . '/../views/operador/historial_pagos.php';
    }

    /**
     * Gestión de solicitudes de cambios
     */
    public function solicitudes(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Obtener TODAS las solicitudes pendientes
        $solicitudesPendientes = SolicitudCambio::getPendientes();

        // Usar la vista unificada de admin (funciona para ambos roles)
        require_once __DIR__ . '/../views/admin/solicitudes/index.php';
    }

    /**
     * Lista de clientes con información de controles asignados
     */
    public function clientesControles(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Filtros
        $filters = [];
        if (isset($_GET['bloque']) && !empty($_GET['bloque'])) {
            $filters['bloque'] = $_GET['bloque'];
        }
        if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
            $filters['busqueda'] = sanitize($_GET['busqueda']);
        }

        $clientes = Usuario::getClientesConControles($filters);

        require_once __DIR__ . '/../views/operador/clientes_controles.php';
    }

    /**
     * Vista de controles ordenados por receptor con filtros
     */
    /**
     * Vista de controles ordenados por receptor con filtros
     */
    public function vistaControles(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Filtros
        $filters = [];
        if (isset($_GET['estado']) && !empty($_GET['estado'])) {
            $filters['estado'] = $_GET['estado'];
        }
        if (isset($_GET['receptor']) && !empty($_GET['receptor'])) {
            $filters['receptor'] = $_GET['receptor'];
        }
        if (isset($_GET['bloque']) && !empty($_GET['bloque'])) {
            $filters['bloque'] = $_GET['bloque'];
        }
        if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
            $filters['busqueda'] = sanitize($_GET['busqueda']);
        }

        $controles = Control::getControlesConPropietarios($filters);

        require_once __DIR__ . '/../views/operador/vista_controles.php';
    }

    /**
     * Asignar control (Operador)
     */
    public function asignarControl(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $controlId = intval($_GET['id'] ?? 0);

        if (!$controlId) {
            redirect('operador/controles');
            return;
        }

        $control = Control::findById($controlId);

        if (!$control || $control->estado !== 'vacio') {
            $_SESSION['error'] = 'Control no disponible';
            redirect('operador/controles');
            return;
        }

        // Obtener apartamentos con residentes
        $sql = "SELECT au.id, au.cantidad_controles,
                       u.nombre_completo,
                       CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento
                FROM apartamento_usuario au
                JOIN usuarios u ON u.id = au.usuario_id
                JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE au.activo = TRUE
                ORDER BY a.bloque, a.numero_apartamento";

        $apartamentosUsuarios = Database::fetchAll($sql);

        require_once __DIR__ . '/../views/operador/controles/asignar.php';
    }

    /**
     * Procesar asignación de control (Operador)
     */
    public function processAsignarControl(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('operador/controles');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('operador/controles');
            return;
        }

        $controlId = intval($_POST['control_id'] ?? 0);
        $apartamentoUsuarioId = intval($_POST['apartamento_usuario_id'] ?? 0);

        $control = Control::findById($controlId);

        if (!$control) {
            $_SESSION['error'] = 'Control no encontrado';
            redirect('operador/controles');
            return;
        }

        if ($control->asignar($apartamentoUsuarioId, $usuario->id)) {
            $_SESSION['success'] = 'Control asignado correctamente';
            writeLog("Control {$control->numero_control_completo} asignado por operador {$usuario->email}", 'info');
        } else {
            $_SESSION['error'] = 'Error al asignar el control';
        }

        redirect('operador/controles');
    }

    /**
     * Aprobar solicitud de registro con asignación manual de controles (Operador)
     */
    public function aprobarSolicitudRegistro(): void
    {
        $operador = $this->checkAuth();
        if (!$operador) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // Leer datos JSON del body
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($data['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
            exit;
        }

        $solicitudId = (int)($data['solicitud_id'] ?? 0);

        if ($solicitudId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de solicitud inválido']);
            exit;
        }

        $solicitud = SolicitudCambio::findById($solicitudId);

        if (!$solicitud) {
            echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
            exit;
        }

        if ($solicitud->tipo_solicitud !== 'registro_nuevo_usuario') {
            echo json_encode(['success' => false, 'message' => 'Esta función solo es válida para solicitudes de registro de nuevos usuarios']);
            exit;
        }

        // Preparar datos de asignación
        $datosAsignacion = [
            'cantidad_controles' => intval($data['cantidad_controles'] ?? 0),
            'controles' => $data['controles'] ?? [],
            'bloque' => sanitize($data['bloque'] ?? ''),
            'escalera' => sanitize($data['escalera'] ?? ''),
            'apartamento' => sanitize($data['apartamento'] ?? ''),
            'piso' => intval($data['piso'] ?? 0)
        ];

        // Validaciones
        if ($datosAsignacion['cantidad_controles'] <= 0 || $datosAsignacion['cantidad_controles'] > 10) {
            echo json_encode(['success' => false, 'message' => 'La cantidad de controles debe estar entre 1 y 10']);
            exit;
        }

        if (count($datosAsignacion['controles']) !== $datosAsignacion['cantidad_controles']) {
            echo json_encode(['success' => false, 'message' => 'La cantidad de controles seleccionados no coincide con la cantidad especificada']);
            exit;
        }

        if (empty($datosAsignacion['bloque']) || empty($datosAsignacion['escalera']) || empty($datosAsignacion['apartamento'])) {
            echo json_encode(['success' => false, 'message' => 'Los datos del apartamento son obligatorios']);
            exit;
        }

        try {
            $usuarioId = $solicitud->crearUsuarioConAsignacionManual($operador->id, $datosAsignacion);

            if ($usuarioId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuario creado exitosamente y controles asignados',
                    'usuario_id' => $usuarioId
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear el usuario']);
            }
        } catch (Exception $e) {
            writeLog("Error en aprobarSolicitudRegistro (operador): " . $e->getMessage(), 'error');
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        exit;
    }

    /**
     * Aprobar/rechazar solicitud
     */
    public function processSolicitud(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('operador/solicitudes');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('operador/solicitudes');
            return;
        }

        $solicitudId = intval($_POST['solicitud_id'] ?? 0);
        $accion = $_POST['accion'] ?? '';
        $observaciones = sanitize($_POST['observaciones'] ?? '');

        if (!in_array($accion, ['aprobar', 'rechazar'])) {
            $_SESSION['error'] = 'Acción inválida';
            redirect('operador/solicitudes');
            return;
        }

        $sql = "UPDATE solicitudes_cambios
                SET estado = ?,
                    aprobado_por = ?,
                    fecha_respuesta = NOW(),
                    observaciones = ?
                WHERE id = ? AND estado = 'pendiente'";

        $estado = $accion === 'aprobar' ? 'aprobada' : 'rechazada';

        $result = Database::execute($sql, [$estado, $usuario->id, $observaciones, $solicitudId]);

        if ($result > 0) {
            $_SESSION['success'] = "Solicitud {$estado} correctamente";
            writeLog("Solicitud ID $solicitudId {$estado} por operador {$usuario->email}", 'info');
        } else {
            $_SESSION['error'] = 'Error al procesar la solicitud';
        }

        redirect('operador/solicitudes');
    }

    /**
     * Buscar cliente (AJAX)
     */
    public function buscarCliente(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        header('Content-Type: application/json');

        $criterio = sanitize($_GET['q'] ?? '');

        if (strlen($criterio) < 2) {
            echo json_encode([]);
            exit;
        }

        $clientes = Usuario::buscarClientes($criterio);

        echo json_encode($clientes);
        exit;
    }

    // ==================== HELPERS ====================

    /**
     * Obtener estadísticas de hoy
     */
    private function getEstadisticasHoy(): array
    {
        $sql = "SELECT
                    COUNT(*) as total_pagos,
                    SUM(CASE WHEN estado_comprobante = 'aprobado' THEN 1 ELSE 0 END) as aprobados_hoy,
                    SUM(CASE WHEN estado_comprobante = 'rechazado' THEN 1 ELSE 0 END) as rechazados_hoy,
                    SUM(CASE WHEN estado_comprobante = 'aprobado' THEN monto_usd ELSE 0 END) as total_usd,
                    SUM(CASE WHEN estado_comprobante = 'aprobado' THEN monto_bs ELSE 0 END) as total_bs
                FROM pagos
                WHERE DATE(fecha_pago) = CURDATE()";

        $result = Database::fetchOne($sql);
        return is_array($result) ? $result : [
            'total_pagos' => 0,
            'aprobados_hoy' => 0,
            'rechazados_hoy' => 0,
            'total_usd' => 0,
            'total_bs' => 0
        ];
    }

    /**
     * Obtener últimas actividades
     */
    private function getUltimasActividades(int $limit = 10): array
    {
        $sql = "SELECT la.*, u.nombre_completo as usuario_nombre
                FROM logs_actividad la
                LEFT JOIN usuarios u ON u.id = la.usuario_id
                ORDER BY la.fecha_hora DESC
                LIMIT ?";

        $result = Database::fetchAll($sql, [$limit]);
        return is_array($result) ? $result : [];
    }

    /**
     * Obtener solicitudes pendientes
     */
    private function getSolicitudesPendientes(): array
    {
        $sql = "SELECT s.id, s.tipo_solicitud, s.cantidad_controles_nueva, s.control_id, s.motivo,
                        s.estado, s.fecha_solicitud, s.aprobado_por, s.fecha_respuesta, s.observaciones,
                        u.nombre_completo as solicitante_nombre,
                        u.email as solicitante_email,
                        u.telefono as solicitante_telefono,
                        a.bloque as apartamento_bloque,
                        a.escalera as apartamento_escalera,
                        a.piso as apartamento_piso,
                        a.numero_apartamento as apartamento_numero,
                        c.numero_control_completo as control_numero,
                        c.estado as control_estado,
                        c.fecha_asignacion as control_fecha_asignacion
                 FROM solicitudes_cambios s
                 JOIN apartamento_usuario au ON au.id = s.apartamento_usuario_id
                 JOIN usuarios u ON u.id = au.usuario_id
                 JOIN apartamentos a ON a.id = au.apartamento_id
                 LEFT JOIN controles_estacionamiento c ON c.id = s.control_id
                 WHERE s.estado = 'pendiente'
                 ORDER BY s.fecha_solicitud DESC";

        return Database::fetchAll($sql);
    }

    /**
     * Obtener tasa BCV actual
     */
    private function getTasaBCVActual(): float
    {
        $sql = "SELECT tasa_usd_bs FROM tasa_cambio_bcv ORDER BY fecha_registro DESC LIMIT 1";
        $result = Database::fetchOne($sql);

        return $result ? floatval($result['tasa_usd_bs']) : 36.50;
    }

    private function getEstadisticasMorosidad(): array
    {
        $sql = "SELECT COUNT(DISTINCT usuario_id) as total_morosos
                FROM vista_morosidad
                WHERE meses_pendientes >= 1";

        return Database::fetchOne($sql) ?: [];
    }

    /**
     * Consultar tasa de cambio desde la página oficial del BCV
     *
     * @return float|null Tasa USD/BS o null si falla
     */
    private function obtenerTasaDesdeBCV(): ?float
    {
        try {
            $url = 'https://www.bcv.org.ve/';

            // Inicializar cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$html) {
                writeLog("Error consultando BCV: HTTP $httpCode", 'error');
                return null;
            }

            // Patrones de búsqueda para extraer la tasa USD
            $patterns = [
                // Patrón 1: Buscar "Dólar" seguido de números
                '/<strong>D[oó]lar.*?<\/strong>.*?<strong[^>]*>\s*([\d,\.]+)\s*<\/strong>/is',

                // Patrón 2: Buscar en divs con clase de monedas
                '/<div[^>]*class="[^"]*moneda[^"]*"[^>]*>.*?USD.*?<strong[^>]*>\s*([\d,\.]+)\s*<\/strong>/is',

                // Patrón 3: Buscar directamente USD (principal para bcv.org.ve)
                '/USD.*?<strong[^>]*>\s*([\d,\.]+)\s*<\/strong>/is',

                // Patrón 4: Buscar en div con id="dolar"
                '/<div[^>]*id="dolar"[^>]*>.*?<strong[^>]*>\s*([\d,\.]+)\s*<\/strong>/is',

                // Patrón 5: Buscar en tabla de tasas
                '/<td[^>]*>.*?USD.*?<\/td>.*?<td[^>]*>\s*([\d,\.]+)\s*<\/td>/is'
            ];

            foreach ($patterns as $i => $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    // Limpiar el número (eliminar puntos de miles, reemplazar coma por punto)
                    $tasaStr = trim($matches[1]);
                    $tasaStr = str_replace('.', '', $tasaStr); // Eliminar separadores de miles
                    $tasaStr = str_replace(',', '.', $tasaStr); // Reemplazar coma decimal por punto

                    $tasa = floatval($tasaStr);

                    // Validar que la tasa esté en un rango razonable (entre 1 y 100,000 Bs/USD)
                    if ($tasa >= 1 && $tasa <= 100000) {
                        writeLog("Tasa BCV consultada exitosamente: $tasa Bs/USD (patrón " . ($i + 1) . ")", 'info');
                        return $tasa;
                    }
                }
            }

            writeLog("No se pudo extraer la tasa del HTML del BCV", 'error');
            return null;

        } catch (Exception $e) {
            writeLog("Excepción al consultar BCV: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Obtiene la tasa desde una fuente alternativa
     */
    private function obtenerTasaAlternativa(): ?float
    {
        try {
            // Usar exchangerate.host como alternativa
            $url = BCV_API_URL;

            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0'
                ]
            ]);

            $json = @file_get_contents($url, false, $context);

            if ($json === false) {
                return null;
            }

            $data = json_decode($json, true);

            // exchangerate.host devuelve rates.VES
            if (isset($data['rates']['VES'])) {
                $tasa = (float) $data['rates']['VES'];
                if ($tasa >= 10 && $tasa <= 100) {
                    return $tasa;
                }
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Actualizar tasa BCV (AJAX)
     */
    public function actualizarTasaBCV(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // Validar CSRF - Leer del body JSON si es una petición JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $receivedToken = $data['csrf_token'] ?? $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? 'no_session_token';

        if (!ValidationHelper::validateCSRFToken($receivedToken)) {
            error_log("CSRF validation failed. Received: '$receivedToken', Session: '$sessionToken'");
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
            exit;
        }

        try {
            // Obtener la tasa actualizada desde el sitio web del BCV
            $tasaNueva = $this->obtenerTasaDesdeBCV();

            // Si falla el BCV, intentar fuente alternativa
            if ($tasaNueva === null) {
                writeLog("BCV directo no disponible, intentando fuente alternativa", 'warning');
                $tasaNueva = $this->obtenerTasaAlternativa();
            }

            // Si todo falla, usar simulación como último recurso
            if ($tasaNueva === null) {
                writeLog("APIs no disponibles, usando simulación", 'warning');
                $tasaActual = $this->getTasaBCVActual();
                $tasaNueva = $tasaActual + (mt_rand(-50, 50) / 100); // Simular cambio pequeño
            }

            // Validar que la tasa sea razonable (entre 1 y 500 Bs)
            if ($tasaNueva < 1 || $tasaNueva > 500) {
                writeLog("Tasa obtenida fuera de rango: $tasaNueva", 'error');
                echo json_encode(['success' => false, 'message' => "Tasa obtenida inválida: $tasaNueva Bs"]);
                exit;
            }

            // Obtener tasa anterior para calcular variación
            $tasaAnterior = $this->getTasaBCVActual();

            // Insertar nueva tasa en la base de datos
            $sql = "INSERT INTO tasa_cambio_bcv (tasa_usd_bs, registrado_por, fecha_registro, fuente)
                    VALUES (?, ?, NOW(), ?)";

            $fuente = $tasaNueva !== null && $tasaNueva !== $tasaAnterior ? 'BCV_WEB' : 'SIMULADO';
            $result = Database::execute($sql, [$tasaNueva, $usuario->id, $fuente]);

            if ($result) {
                $variacion = $tasaAnterior ? (($tasaNueva - $tasaAnterior) / $tasaAnterior) * 100 : 0;
                $signo = $variacion >= 0 ? '+' : '';

                writeLog("Tasa BCV actualizada por operador {$usuario->email}: $tasaAnterior -> $tasaNueva (Variación: {$signo}" . number_format($variacion, 2) . "%)", 'info');

                echo json_encode([
                    'success' => true,
                    'message' => 'Tasa BCV actualizada correctamente (Variación: ' . $signo . number_format($variacion, 2) . '%)',
                    'nueva_tasa' => $tasaNueva,
                    'tasa_anterior' => $tasaAnterior,
                    'variacion' => round($variacion, 2),
                    'fuente' => $fuente,
                    'fecha' => date('d/m/Y H:i')
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al guardar la nueva tasa']);
            }
        } catch (Exception $e) {
            writeLog("Error al actualizar tasa BCV: " . $e->getMessage(), 'error');
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }

        exit;
    }

    /**
     * Mapa de controles (igual que administrador, solo visualización)
     */
    public function controles(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $mapa = Control::getMapaControles();
        $estadisticas = Control::getEstadisticas();

        require_once __DIR__ . '/../views/operador/controles.php';
    }


    /**
     * Gestionar controles de un usuario específico (AJAX para modal)
     */
    public function gestionarControlesUsuarioAjax(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $usuarioId = intval($_GET['id'] ?? 0);

        if (!$usuarioId) {
            echo '<div class="alert alert-danger">Usuario no especificado</div>';
            exit;
        }

        $usuarioGestionado = Usuario::findById($usuarioId);

        if (!$usuarioGestionado) {
            echo '<div class="alert alert-danger">Usuario no encontrado</div>';
            exit;
        }

        // Obtener apartamento del usuario
        $sql = "SELECT au.id as apartamento_usuario_id, au.cantidad_controles,
                       a.bloque, a.escalera, a.piso, a.numero_apartamento
                FROM apartamento_usuario au
                JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE au.usuario_id = ? AND au.activo = 1
                LIMIT 1";
        $apartamento = Database::fetchOne($sql, [$usuarioId]);

        if (!$apartamento) {
            echo '<div class="alert alert-danger">El usuario no tiene un apartamento asignado</div>';
            exit;
        }

        // Obtener controles actuales del usuario
        $controlesActuales = Control::getByApartamentoUsuario($apartamento['apartamento_usuario_id']);

        // Obtener controles disponibles para asignar
        $controlesDisponibles = Control::getVacios();

        // Renderizar contenido del modal
        ob_start();
        ?>
        <!-- Información del Usuario -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nombre:</strong> <?= htmlspecialchars($usuarioGestionado->nombre_completo) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($usuarioGestionado->email) ?></p>
                        <p><strong>Teléfono:</strong> <?= htmlspecialchars($usuarioGestionado->telefono ?? 'No especificado') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Apartamento:</strong> <?= htmlspecialchars($apartamento['bloque'] . '-' . $apartamento['numero_apartamento']) ?></p>
                        <p><strong>Controles Asignados:</strong> <?= count($controlesActuales) ?> / <?= $apartamento['cantidad_controles'] ?></p>
                        <p><strong>Rol:</strong> <span class="badge bg-primary"><?= ucfirst($usuarioGestionado->rol) ?></span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Controles Actuales -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-key"></i> Controles Actuales
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($controlesActuales)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-key" style="font-size: 2rem;"></i>
                                <p class="mb-0 mt-2">No hay controles asignados</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($controlesActuales as $control): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($control->numero_control_completo) ?></strong>
                                            <span class="badge
                                                <?php if ($control->estado === 'activo'): ?>bg-success
                                                <?php elseif ($control->estado === 'bloqueado'): ?>bg-danger
                                                <?php else: ?>bg-warning<?php endif; ?> ms-2">
                                                <?= ucfirst($control->estado) ?>
                                            </span>
                                            <?php if ($control->fecha_asignacion): ?>
                                                <br><small class="text-muted">
                                                    Asignado: <?= date('d/m/Y', strtotime($control->fecha_asignacion)) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="removerControlAjax(<?= $control->id ?>, '<?= htmlspecialchars($control->numero_control_completo) ?>')">
                                            <i class="bi bi-trash"></i> Remover
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Asignar Nuevos Controles -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-plus-circle"></i> Asignar Nuevo Control
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($controlesDisponibles)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                                <p class="mb-0 mt-2">No hay controles disponibles</p>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="<?= url('operador/asignar-control-usuario') ?>" id="formAsignarControl">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="usuario_id" value="<?= $usuarioGestionado->id ?>">

                                <div class="mb-3">
                                    <label for="control_id" class="form-label">Seleccionar Control Disponible</label>
                                    <select class="form-select" name="control_id" id="control_id" required>
                                        <option value="">-- Seleccionar control --</option>
                                        <?php foreach ($controlesDisponibles as $control): ?>
                                            <option value="<?= $control['id'] ?>">
                                                <?= htmlspecialchars($control['numero_control_completo']) ?> (Posición <?= $control['posicion_numero'] ?>, Receptor <?= $control['receptor'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-plus-circle"></i> Asignar Control
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function removerControlAjax(controlId, controlNumero) {
            if (confirm('¿Está seguro de que desea remover el control ' + controlNumero + ' del usuario?')) {
                const motivo = prompt('Motivo de la remoción:');
                if (motivo && motivo.trim() !== '') {
                    // Enviar petición AJAX
                    fetch('<?= url('operador/remover-control-usuario') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'csrf_token': '<?= generateCSRFToken() ?>',
                            'usuario_id': '<?= $usuarioGestionado->id ?>',
                            'control_id': controlId,
                            'motivo': motivo.trim()
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Recargar el modal
                            location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Error desconocido'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error de conexión');
                    });
                }
            }
        }

        // Manejar envío del formulario de asignación
        document.getElementById('formAsignarControl')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    // Recargar el modal si fue exitoso
                    location.reload();
                } else {
                    return response.text();
                }
            })
            .then(text => {
                if (text && text.includes('error')) {
                    alert('Error al asignar el control');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión');
            });
        });
        </script>
        <?php
        echo ob_get_clean();
        exit;
    }

    /**
     * Remover control de un usuario
     */
    public function removerControlUsuario(): void
    {
        $operador = $this->checkAuth();
        if (!$operador) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('operador/clientes-controles');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Token de seguridad inválido']);
            } else {
                $_SESSION['error'] = 'Token de seguridad inválido';
                redirect('operador/clientes-controles');
            }
            return;
        }

        $controlId = intval($_POST['control_id'] ?? 0);
        $usuarioId = intval($_POST['usuario_id'] ?? 0);
        $motivo = sanitize($_POST['motivo'] ?? 'Removido por operador');

        $control = Control::findById($controlId);
        $usuario = Usuario::findById($usuarioId);

        if (!$control || !$usuario) {
            $errorMsg = 'Datos inválidos';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMsg]);
            } else {
                $_SESSION['error'] = $errorMsg;
                redirect('operador/gestionar-controles-usuario?id=' . $usuarioId);
            }
            return;
        }

        if ($control->desasignar($motivo, $operador->id)) {
            $successMsg = 'Control removido correctamente';
            writeLog("Control {$control->numero_control_completo} removido del usuario {$usuario->email} por operador {$operador->email}", 'info');

            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => true, 'message' => $successMsg]);
            } else {
                $_SESSION['success'] = $successMsg;
                redirect('operador/gestionar-controles-usuario?id=' . $usuarioId);
            }
        } else {
            $errorMsg = 'Error al remover el control';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMsg]);
            } else {
                $_SESSION['error'] = $errorMsg;
                redirect('operador/gestionar-controles-usuario?id=' . $usuarioId);
            }
        }
    }

    /**
     * Asignar control a un usuario
     */
    public function asignarControlUsuario(): void
    {
        $operador = $this->checkAuth();
        if (!$operador) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('operador/clientes-controles');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Token de seguridad inválido']);
            } else {
                $_SESSION['error'] = 'Token de seguridad inválido';
                redirect('operador/clientes-controles');
            }
            return;
        }

        $controlId = intval($_POST['control_id'] ?? 0);
        $usuarioId = intval($_POST['usuario_id'] ?? 0);

        $control = Control::findById($controlId);
        $usuario = Usuario::findById($usuarioId);

        if (!$control || !$usuario) {
            $errorMsg = 'Datos inválidos';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMsg]);
            } else {
                $_SESSION['error'] = $errorMsg;
                redirect('operador/gestionar-controles-usuario?id=' . $usuarioId);
            }
            return;
        }

        // Obtener apartamento_usuario_id del usuario
        $sql = "SELECT id FROM apartamento_usuario WHERE usuario_id = ? AND activo = 1 LIMIT 1";
        $apartamentoUsuario = Database::fetchOne($sql, [$usuarioId]);

        if (!$apartamentoUsuario) {
            $errorMsg = 'El usuario no tiene un apartamento asignado';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMsg]);
            } else {
                $_SESSION['error'] = $errorMsg;
                redirect('operador/gestionar-controles-usuario?id=' . $usuarioId);
            }
            return;
        }

        if ($control->asignar($apartamentoUsuario['id'], $operador->id)) {
            $successMsg = 'Control asignado correctamente';
            writeLog("Control {$control->numero_control_completo} asignado al usuario {$usuario->email} por operador {$operador->email}", 'info');

            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => true, 'message' => $successMsg]);
            } else {
                $_SESSION['success'] = $successMsg;
                redirect('operador/gestionar-controles-usuario?id=' . $usuarioId);
            }
        } else {
            $errorMsg = 'Error al asignar el control';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMsg]);
            } else {
                $_SESSION['error'] = $errorMsg;
                redirect('operador/gestionar-controles-usuario?id=' . $usuarioId);
            }
        }
    }

    /**
     * Cambiar estado de un control
     */
    public function cambiarEstadoControl(): void
    {
        $operador = $this->checkAuth();
        if (!$operador) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            } else {
                redirect('operador/vista-controles');
            }
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $errorMsg = 'Token de seguridad inválido';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMsg]);
            } else {
                $_SESSION['error'] = $errorMsg;
                redirect('operador/vista-controles');
            }
            return;
        }

        $controlId = intval($_POST['control_id'] ?? 0);
        $nuevoEstado = $_POST['estado'] ?? '';
        $motivo = sanitize($_POST['motivo'] ?? '');

        // Validar estado
        $estadosPermitidos = ['activo', 'vacio', 'bloqueado', 'suspendido', 'desactivado', 'perdido'];
        if (!in_array($nuevoEstado, $estadosPermitidos)) {
            $errorMsg = 'Estado no válido';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMsg]);
            } else {
                $_SESSION['error'] = $errorMsg;
                redirect('operador/vista-controles');
            }
            return;
        }

        $control = Control::findById($controlId);

        if (!$control) {
            $errorMsg = 'Control no encontrado';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMsg]);
            } else {
                $_SESSION['error'] = $errorMsg;
                redirect('operador/vista-controles');
            }
            return;
        }

        $success = false;
        $successMessage = '';
        $errorMessage = '';

        // Cambiar estado según el tipo
        if ($nuevoEstado === 'vacio') {
            // Desasignar control
            if ($control->desasignar($motivo, $operador->id)) {
                $success = true;
                $successMessage = "Control {$control->numero_control_completo} desasignado correctamente";
            } else {
                $errorMessage = 'Error al desasignar el control';
            }
        } elseif ($nuevoEstado === 'bloqueado') {
            // Bloquear control
            if ($control->bloquear($motivo)) {
                $success = true;
                $successMessage = "Control {$control->numero_control_completo} bloqueado correctamente";
            } else {
                $errorMessage = 'Error al bloquear el control';
            }
        } elseif ($nuevoEstado === 'activo' && $control->estado === 'bloqueado') {
            // Desbloquear control
            if ($control->desbloquear($operador->id)) {
                $success = true;
                $successMessage = "Control {$control->numero_control_completo} desbloqueado correctamente";
            } else {
                $errorMessage = 'Error al desbloquear el control';
            }
        } else {
            // Cambiar a otro estado
            if ($control->cambiarEstado($nuevoEstado, $motivo, $operador->id)) {
                $success = true;
                $successMessage = "Estado del control {$control->numero_control_completo} actualizado correctamente";
            } else {
                $errorMessage = 'Error al cambiar el estado del control';
            }
        }

        writeLog("Operador {$operador->email} cambió estado del control {$control->numero_control_completo} a: $nuevoEstado", 'info');

        // Responder según el tipo de solicitud
        if ($this->isAjaxRequest()) {
            if ($success) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => $successMessage,
                    'nuevo_estado' => $nuevoEstado,
                    'control_numero' => $control->numero_control_completo
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => $errorMessage]);
            }
        } else {
            if ($success) {
                $_SESSION['success'] = $successMessage;
            } else {
                $_SESSION['error'] = $errorMessage;
            }
            redirect('operador/vista-controles');
        }
    }

    /**
     * Descargar recibo de pago (PDF)
     */
    public function descargarRecibo(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $pagoId = intval($_GET['id'] ?? 0);

        if (!$pagoId) {
            redirect('operador/historial-pagos');
            return;
        }

        $pago = Pago::findById($pagoId);

        if (!$pago) {
            $_SESSION['error'] = 'Pago no encontrado';
            redirect('operador/historial-pagos');
            return;
        }

        if ($pago->estado_comprobante !== 'aprobado') {
            $_SESSION['error'] = 'Solo se pueden descargar recibos de pagos aprobados';
            redirect('operador/historial-pagos');
            return;
        }

        // Generar PDF
        $rutaPdf = $pago->generarRecibo();

        if (!$rutaPdf || !file_exists($rutaPdf)) {
            $_SESSION['error'] = 'Error al generar el recibo';
            redirect('operador/historial-pagos');
            return;
        }

        // Descargar
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($rutaPdf) . '"');
        header('Content-Length: ' . filesize($rutaPdf));
        readfile($rutaPdf);
        exit;
    }

    /**
     * Verificar si es una petición AJAX
     */
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Enviar respuesta JSON
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // ==================== GESTIÓN DE PERFIL ====================

    /**
     * Ver perfil del operador
     */
    public function perfil(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Obtener información del apartamento (si tiene)
        $sql = "SELECT a.id as apartamento_id, a.bloque, a.escalera, a.piso, a.numero_apartamento
                FROM apartamento_usuario au
                JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE au.usuario_id = ? AND au.activo = 1
                LIMIT 1";
        $apartamento = Database::fetchOne($sql, [$usuario->id]);

        // Obtener controles asignados (si tiene)
        $sql = "SELECT ce.numero_control_completo, ce.estado, ce.fecha_asignacion
                FROM apartamento_usuario au
                LEFT JOIN controles_estacionamiento ce ON ce.apartamento_usuario_id = au.id
                WHERE au.usuario_id = ? AND au.activo = 1
                ORDER BY ce.numero_control_completo";
        $controles = Database::fetchAll($sql, [$usuario->id]);

        // Filtrar controles válidos (que no sean NULL)
        $controles = array_filter($controles, function($c) {
            return !empty($c['numero_control_completo']);
        });

        // Obtener todos los apartamentos disponibles para el selector
        $sql = "SELECT id, bloque, escalera, piso, numero_apartamento
                FROM apartamentos
                WHERE activo = 1
                ORDER BY bloque, escalera, piso, numero_apartamento";
        $apartamentosDisponibles = Database::fetchAll($sql);

        require_once __DIR__ . '/../views/operador/perfil.php';
    }

    /**
     * Actualizar perfil del operador
     */
    public function updatePerfil(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('operador/perfil');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('operador/perfil');
            return;
        }

        $nombreCompleto = sanitize($_POST['nombre_completo'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $telefono = sanitize($_POST['telefono'] ?? '');
        $direccion = sanitize($_POST['direccion'] ?? '');
        $apartamentoId = intval($_POST['apartamento_id'] ?? 0);

        // Validar nombre completo
        if (empty($nombreCompleto) || strlen($nombreCompleto) < 3) {
            $_SESSION['error'] = 'El nombre completo debe tener al menos 3 caracteres';
            redirect('operador/perfil');
            return;
        }

        // Validar email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Formato de email inválido';
            redirect('operador/perfil');
            return;
        }

        // Verificar si el email ya está en uso por otro usuario
        if ($email !== $usuario->email) {
            $existingUser = Usuario::findByEmail($email);
            if ($existingUser && $existingUser->id !== $usuario->id) {
                $_SESSION['error'] = 'El email ya está en uso por otro usuario';
                redirect('operador/perfil');
                return;
            }
        }

        // Validar teléfono
        if (!empty($telefono) && !ValidationHelper::validatePhone($telefono)) {
            $_SESSION['error'] = 'Formato de teléfono inválido';
            redirect('operador/perfil');
            return;
        }

        try {
            // Actualizar datos personales
            $usuario->update([
                'nombre_completo' => $nombreCompleto,
                'email' => $email,
                'telefono' => $telefono,
                'direccion' => $direccion
            ]);

            // Actualizar sesión si cambió el email o nombre
            if ($email !== $_SESSION['user_email']) {
                $_SESSION['user_email'] = $email;
            }

            if ($nombreCompleto !== $_SESSION['user_nombre']) {
                $_SESSION['user_nombre'] = $nombreCompleto;
            }

            // Manejar cambio de apartamento
            // Obtener apartamento actual
            $sql = "SELECT id, apartamento_id FROM apartamento_usuario
                    WHERE usuario_id = ? AND activo = 1 LIMIT 1";
            $asignacionActual = Database::fetchOne($sql, [$usuario->id]);

            if ($apartamentoId > 0) {
                // Usuario quiere tener un apartamento asignado
                if ($asignacionActual) {
                    // Ya tiene un apartamento, verificar si cambió
                    if ($asignacionActual['apartamento_id'] != $apartamentoId) {
                        // Desactivar asignación anterior
                        $sql = "UPDATE apartamento_usuario SET activo = 0 WHERE id = ?";
                        Database::execute($sql, [$asignacionActual['id']]);

                        // Crear nueva asignación
                        $sql = "INSERT INTO apartamento_usuario (usuario_id, apartamento_id, activo, fecha_asignacion)
                                VALUES (?, ?, 1, NOW())";
                        Database::execute($sql, [$usuario->id, $apartamentoId]);

                        writeLog("Apartamento cambiado para usuario {$usuario->email} (ID: {$usuario->id})", 'info');
                    }
                } else {
                    // No tiene apartamento, crear nueva asignación
                    $sql = "INSERT INTO apartamento_usuario (usuario_id, apartamento_id, activo, fecha_asignacion)
                            VALUES (?, ?, 1, NOW())";
                    Database::execute($sql, [$usuario->id, $apartamentoId]);

                    writeLog("Apartamento asignado a usuario {$usuario->email} (ID: {$usuario->id})", 'info');
                }
            } else {
                // Usuario no quiere apartamento asignado
                if ($asignacionActual) {
                    // Desactivar asignación actual
                    $sql = "UPDATE apartamento_usuario SET activo = 0 WHERE id = ?";
                    Database::execute($sql, [$asignacionActual['id']]);

                    writeLog("Apartamento desasignado de usuario {$usuario->email} (ID: {$usuario->id})", 'info');
                }
            }

            $_SESSION['success'] = 'Perfil actualizado correctamente';

        } catch (Exception $e) {
            writeLog("Error al actualizar perfil de operador: " . $e->getMessage(), 'error');
            $_SESSION['error'] = 'Error al actualizar el perfil. Intente nuevamente.';
        }

        redirect('operador/perfil');
    }
}
