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

        // Buscar cliente
        $cliente = null;
        $busqueda = sanitize($_GET['buscar'] ?? '');
        $clienteId = isset($_GET['cliente_id']) && !empty($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
        $resultadosBusqueda = [];

        if ($clienteId) {
            $cliente = Usuario::findById($clienteId);
            if (!$cliente) {
                $_SESSION['error'] = 'Cliente no encontrado';
            }
        } elseif ($busqueda) {
            $resultadosBusqueda = Usuario::buscarClientes($busqueda);
            
            // Si hay exactamente 1 coincidencia Y el texto buscado coincide exactamente con email o cédula
            if (count($resultadosBusqueda) === 1) {
                $bClean = strtolower(trim($busqueda));
                $eClean = strtolower(trim($resultadosBusqueda[0]['email'] ?? ''));
                $cClean = strtolower(trim($resultadosBusqueda[0]['cedula'] ?? ''));
                if ($bClean === $eClean || ($cClean !== '' && $bClean === $cClean)) {
                    $cliente = Usuario::findById($resultadosBusqueda[0]['id']);
                }
            }
            
            if (!$cliente && empty($resultadosBusqueda)) {
                $_SESSION['error'] = 'No se encontraron clientes coincidentes con la búsqueda.';
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
            $clienteIdParam = $cliente ? '&cliente_id=' . $cliente->id : '';
            header('Location: ' . url('operador/registrar-pago-presencial') . '?buscar=' . urlencode($_GET['buscar'] ?? '') . $clienteIdParam . '&modo=adelantado');
            exit;
        }

        if ($cliente) {
            // Permitir hasta 12 meses siempre
            $mesesAdelante = 12;
            try {
                $mensualidadesPendientes = Mensualidad::getMensualidadesParaPagoAdelantado($cliente->id, $mesesAdelante);
            } catch (Exception $e) {
                $_SESSION['error'] = 'Atención: ' . $e->getMessage() . '. Asigne un apartamento al cliente para poder gestionar sus mensualidades.';
                $mensualidadesPendientes = [];
            }
            if (!is_array($mensualidadesPendientes)) {
                $mensualidadesPendientes = [];
            }
        }

        // Obtener tasa BCV
        $tasaBCVData = $this->getTasaBCVInfo();
        $tasaBCV = $tasaBCVData['tasa_usd_bs'];
        $tasaBCVFecha = $tasaBCVData['fecha_registro'];

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

        // Definir URL de redirección en caso de error para no perder el contexto del cliente
        $redirectUrl = 'operador/registrar-pago-presencial?buscar=' . urlencode($cliente->nombre_completo) . '&cliente_id=' . $cliente->id;

        if (!in_array($moneda, ['USD', 'Bs'])) {
            $_SESSION['error'] = 'Moneda inválida';
            redirect($redirectUrl);
            return;
        }

        if ($monto <= 0) {
            $_SESSION['error'] = 'El monto debe ser mayor a 0';
            redirect($redirectUrl);
            return;
        }

        if (empty($mensualidadesSeleccionadas)) {
            $_SESSION['error'] = 'Debe seleccionar al menos una mensualidad';
            redirect($redirectUrl);
            return;
        }

        // Validar y recalcular montos basados en tarifa actual
        require_once __DIR__ . '/../models/ConfiguracionTarifa.php';
        $tarifaActual = ConfiguracionTarifa::getTarifaActual();

        if (!$tarifaActual) {
            $_SESSION['error'] = 'No hay tarifa configurada. Contacte al administrador.';
            redirect($redirectUrl);
            return;
        }

        // Obtener cantidad de controles del cliente
        $sqlControles = "SELECT cantidad_controles FROM apartamento_usuario WHERE usuario_id = ? AND activo = TRUE";
        $controlesData = Database::fetchOne($sqlControles, [$clienteId]);
        $cantidadControles = $controlesData ? $controlesData['cantidad_controles'] : 0;

        // Calcular monto esperado basado en tarifa actual
        $montoEsperadoUSD = $tarifaActual->monto_mensual_usd * count($mensualidadesSeleccionadas) * $cantidadControles;
        $tasaBCV = $this->getTasaBCVActual();

        // Convertir monto pagado a USD si la moneda de pago fue en Bolívares
        $montoEnUSD = $monto;
        if ($moneda === 'Bs') {
            if ($tasaBCV > 0) {
                $montoEnUSD = $monto / $tasaBCV;
            } else {
                $_SESSION['error'] = 'Error: Tasa de cambio BCV no válida';
                redirect($redirectUrl);
                return;
            }
        }

        // Validar que el monto pagado sea razonable (permitir pequeña variación por redondeo de tasa)
        $variacionPermitida = 0.15; // 15 centavos de variación permitida
        if (abs($montoEsperadoUSD - $montoEnUSD) > $variacionPermitida) {
            $_SESSION['error'] = sprintf(
                'El monto pagado equivalente (%.2f USD) no coincide con el monto esperado (%.2f USD) basado en la tarifa actual.',
                $montoEnUSD,
                $montoEsperadoUSD
            );
            redirect($redirectUrl);
            return;
        }

        // Obtener apartamento_usuario_id del cliente
        $sqlApartamento = "SELECT id FROM apartamento_usuario WHERE usuario_id = ? AND activo = TRUE LIMIT 1";
        $apartamentoData = Database::fetchOne($sqlApartamento, [$clienteId]);
        
        if (!$apartamentoData) {
            $_SESSION['error'] = 'El cliente no tiene un apartamento asignado';
            redirect($redirectUrl);
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
            redirect('operador/pago-exitoso?id=' . $pagoId);

        } catch (Exception $e) {
            writeLog("Error al registrar pago presencial: " . $e->getMessage(), 'error');
            $_SESSION['error'] = 'Error al registrar el pago';
            redirect($redirectUrl);
        }
    }

    /**
     * Vista de confirmación de pago exitoso
     */
    public function pagoExitoso(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $pagoId = intval($_GET['id'] ?? 0);

        if (!$pagoId) {
            redirect('operador/dashboard');
            return;
        }

        $pago = Pago::findById($pagoId);

        if (!$pago) {
            $_SESSION['error'] = 'Pago no encontrado';
            redirect('operador/dashboard');
            return;
        }

        // Obtener datos del cliente
        $sql = "SELECT u.nombre_completo, u.cedula, u.email
                FROM public.usuarios u
                JOIN public.apartamento_usuario au ON au.usuario_id = u.id
                WHERE au.id = ? LIMIT 1";
        $cliente = Database::fetchOne($sql, [$pago->apartamento_usuario_id]);

        // Obtener datos del apartamento
        $sqlApto = "SELECT CONCAT(a.bloque, '-', a.numero_apartamento) as apto
                    FROM public.apartamentos a
                    JOIN public.apartamento_usuario au ON au.apartamento_id = a.id
                    WHERE au.id = ? LIMIT 1";
        $aptoData = Database::fetchOne($sqlApto, [$pago->apartamento_usuario_id]);
        $aptoInfo = $aptoData ? $aptoData['apto'] : 'N/A';

        require_once __DIR__ . '/../views/operador/pago_exitoso.php';
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
     * Aprobar solicitud (AJAX/JSON)
     */
    public function aprobarSolicitud(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
            exit;
        }

        $solicitudId = (int)($_POST['solicitud_id'] ?? 0);
        $controlId = (int)($_POST['control_id'] ?? 0);
        $observaciones = trim($_POST['observaciones'] ?? '');

        if ($solicitudId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de solicitud inválido']);
            exit;
        }

        require_once __DIR__ . '/../models/SolicitudCambio.php';
        $solicitud = SolicitudCambio::findById($solicitudId);

        if (!$solicitud) {
            echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
            exit;
        }

        if ($controlId > 0) {
            $solicitud->control_id = $controlId;
            $sqlSet = "UPDATE solicitudes_cambios SET control_id = ? WHERE id = ?";
            Database::execute($sqlSet, [$controlId, $solicitudId]);
        }

        if ($solicitud->aprobar($usuario->id, $observaciones)) {
            echo json_encode(['success' => true, 'message' => 'Solicitud aprobada y procesada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al aprobar la solicitud. Verifique que los datos del control sean correctos.']);
        }
        exit;
    }

    /**
     * Rechazar solicitud (AJAX/JSON)
     */
    public function rechazarSolicitud(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        header('Content-Type: application/json');

        $solicitudId = (int)($_POST['solicitud_id'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? $_POST['observaciones'] ?? '');

        if ($solicitudId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de solicitud inválido']);
            exit;
        }

        require_once __DIR__ . '/../models/SolicitudCambio.php';
        $solicitud = SolicitudCambio::findById($solicitudId);

        if (!$solicitud) {
            echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
            exit;
        }

        if ($solicitud->rechazar($usuario->id, $motivo)) {
            echo json_encode(['success' => true, 'message' => 'Solicitud rechazada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al rechazar la solicitud']);
        }
        exit;
    }

    /**
     * Obtener lista de controles para asignar o desincorporar en modal (AJAX/JSON)
     */
    public function obtenerControlesSolicitud(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) {
            echo json_encode(['success' => false, 'controles' => []]);
            exit;
        }

        header('Content-Type: application/json');

        $solicitudId = (int)($_GET['solicitud_id'] ?? 0);
        if ($solicitudId <= 0) {
            echo json_encode(['success' => false, 'controles' => []]);
            exit;
        }

        require_once __DIR__ . '/../models/SolicitudCambio.php';
        $solicitud = SolicitudCambio::findById($solicitudId);

        if (!$solicitud) {
            echo json_encode(['success' => false, 'controles' => []]);
            exit;
        }

        $tipo = $solicitud->tipo_solicitud;
        $controles = [];

        if (in_array($tipo, ['desincorporar_control', 'suspension_control', 'desactivacion_control', 'reportar_perdido'])) {
            $sql = "SELECT id, numero_control_completo, receptor, posicion_numero, estado
                    FROM controles_estacionamiento
                    WHERE apartamento_usuario_id = ? AND estado != 'vacio'
                    ORDER BY posicion_numero, receptor";
            $controles = Database::fetchAll($sql, [$solicitud->apartamento_usuario_id]);
        } elseif (in_array($tipo, ['agregar_control', 'comprar_control', 'reactivar_control'])) {
            $sql = "SELECT id, numero_control_completo, receptor, posicion_numero, estado
                    FROM controles_estacionamiento
                    WHERE (estado = 'vacio' OR apartamento_usuario_id IS NULL)
                    ORDER BY posicion_numero, receptor";
            $controles = Database::fetchAll($sql);
        }

        echo json_encode([
            'success' => true,
            'tipo' => $tipo,
            'control_actual_id' => $solicitud->control_id,
            'controles' => $controles
        ]);
        exit;
    }

    /**
     * Aprobar/rechazar solicitud (Fallback POST normal)
     */
    public function processSolicitud(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $solicitudId = intval($_POST['solicitud_id'] ?? 0);
        $accion = $_POST['accion'] ?? '';
        $controlId = intval($_POST['control_id'] ?? 0);
        $observaciones = sanitize($_POST['observaciones'] ?? '');

        if ($accion === 'aprobar') {
            $_POST['solicitud_id'] = $solicitudId;
            $_POST['control_id'] = $controlId;
            $_POST['observaciones'] = $observaciones;
            $this->aprobarSolicitud();
            return;
        } elseif ($accion === 'rechazar') {
            $_POST['solicitud_id'] = $solicitudId;
            $_POST['motivo'] = $observaciones;
            $this->rechazarSolicitud();
            return;
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

        if (strlen($criterio) < 1) {
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
                WHERE DATE(fecha_pago) = CURRENT_DATE";

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
     * Obtener información completa de la tasa BCV actual
     */
    private function getTasaBCVInfo(): array
    {
        $sql = "SELECT tasa_usd_bs, fecha_registro, fuente FROM tasa_cambio_bcv ORDER BY fecha_registro DESC LIMIT 1";
        $result = Database::fetchOne($sql);

        if (!$result) {
            return [
                'tasa_usd_bs' => 36.50,
                'fecha_registro' => date('Y-m-d H:i:s'),
                'fuente' => 'Sistema'
            ];
        }

        return [
            'tasa_usd_bs' => floatval($result['tasa_usd_bs']),
            'fecha_registro' => $result['fecha_registro'],
            'fuente' => $result['fuente']
        ];
    }

    /**
     * Obtener tasa BCV actual
     */
    private function getTasaBCVActual(): float
    {
        $info = $this->getTasaBCVInfo();
        return $info['tasa_usd_bs'];
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
            // DolarApi para tasa oficial BCV de Venezuela
            $url = 'https://ve.dolarapi.com/v1/dolares/oficial';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['promedio']) && is_numeric($data['promedio']) && $data['promedio'] > 0) {
                    return (float)$data['promedio'];
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

        require_once __DIR__ . '/../helpers/BCVHelper.php';
        $res = BCVHelper::actualizarTasaBCV('Operador', $usuario->id);

        echo json_encode($res);
        exit;
    }

    /**
     * Mapa de controles (igual que administrador, solo visualización)
     */
    public function controles(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $busqueda = isset($_GET['buscar']) && trim($_GET['buscar']) !== '' ? trim($_GET['buscar']) : null;
        $estado = isset($_GET['estado']) && trim($_GET['estado']) !== '' ? trim($_GET['estado']) : null;

        $mapa = Control::getMapaControles($busqueda, $estado);
        $estadisticas = Control::getEstadisticas();

        $sqlResidentes = "SELECT au.id, u.nombre_completo, CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento
                          FROM apartamento_usuario au
                          JOIN usuarios u ON u.id = au.usuario_id
                          JOIN apartamentos a ON a.id = au.apartamento_id
                          WHERE au.activo = TRUE
                          ORDER BY u.nombre_completo ASC";
        $listaResidentes = Database::fetchAll($sqlResidentes);

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
                       a.id as apartamento_id, a.bloque, a.escalera, a.piso, a.numero_apartamento
                FROM apartamento_usuario au
                JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE au.usuario_id = ? AND au.activo = TRUE
                LIMIT 1";
        $apartamento = Database::fetchOne($sql, [$usuarioId]);

        // Obtener todos los apartamentos activos para el listado
        $todosApartamentos = Apartamento::getAll(['activo' => true]);

        // Obtener controles actuales y disponibles
        $controlesActuales = [];
        $controlesDisponibles = [];
        if ($apartamento) {
            $controlesActuales = Control::getByApartamentoUsuario($apartamento['apartamento_usuario_id']);
            $controlesDisponibles = Control::getVacios();
        }

        // Renderizar contenido del modal
        ob_start();
        ?>
        <!-- Contenedor del Modal con Estilos Sleek/Premium -->
        <div class="container-fluid py-2">
            <div class="row g-4">
                <!-- Columna 1: Información del Usuario y Apartamento -->
                <div class="col-lg-6">
                    <!-- Tarjeta: Datos del Usuario -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-gradient bg-primary text-white py-3">
                            <h6 class="mb-0 fs-6 fw-bold">
                                <i class="bi bi-person-fill"></i> Datos del Usuario
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="<?= url('operador/guardar-usuario-ajax') ?>" id="formEditarUsuario">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="usuario_id" value="<?= $usuarioGestionado->id ?>">

                                <div class="mb-3">
                                    <label for="nombre_completo" class="form-label fw-semibold text-muted small">Nombre Completo</label>
                                    <input type="text" class="form-control" name="nombre_completo" id="nombre_completo" 
                                           value="<?= htmlspecialchars($usuarioGestionado->nombre_completo) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold text-muted small">Correo Electrónico</label>
                                    <input type="email" class="form-control" name="email" id="email" 
                                           value="<?= htmlspecialchars($usuarioGestionado->email) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="telefono" class="form-label fw-semibold text-muted small">Teléfono</label>
                                    <input type="text" class="form-control" name="telefono" id="telefono" 
                                           value="<?= htmlspecialchars($usuarioGestionado->telefono ?? '') ?>" placeholder="Ej: 04141234567">
                                </div>

                                <div class="row mb-3 align-items-center">
                                    <div class="col-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" 
                                                   <?= $usuarioGestionado->activo ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-semibold text-muted small" for="activo">Usuario Activo</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="exonerado" id="exonerado" value="1" 
                                                   <?= $usuarioGestionado->exonerado ? 'checked' : '' ?> onchange="toggleExoneracionInput(this.checked)">
                                            <label class="form-check-label fw-semibold text-muted small" for="exonerado">Exonerado de Pago</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3" id="motivo_exoneracion_wrapper" style="display: <?= $usuarioGestionado->exonerado ? 'block' : 'none' ?>;">
                                    <label for="motivo_exoneracion" class="form-label fw-semibold text-muted small">Motivo de Exoneración</label>
                                    <textarea class="form-control" name="motivo_exoneracion" id="motivo_exoneracion" rows="2" 
                                              placeholder="Escriba el motivo detallado de la exoneración..."><?= htmlspecialchars($usuarioGestionado->motivo_exoneracion ?? '') ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 mt-2 shadow-sm">
                                    <i class="bi bi-save"></i> Guardar Cambios Personales
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Tarjeta: Apartamento Asignado -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-gradient bg-secondary text-white py-3">
                            <h6 class="mb-0 fs-6 fw-bold">
                                <i class="bi bi-house-door-fill"></i> Apartamento Asignado
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="<?= url('operador/guardar-apartamento-usuario-ajax') ?>" id="formAsignarApartamento">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="usuario_id" value="<?= $usuarioGestionado->id ?>">

                                <div class="mb-3">
                                    <label for="apartamento_id" class="form-label fw-semibold text-muted small">Seleccionar Apartamento</label>
                                    <select class="form-select" name="apartamento_id" id="apartamento_id">
                                        <option value="0">-- Sin Apartamento Asignado / Desasignar --</option>
                                        <?php foreach ($todosApartamentos as $apt): ?>
                                            <option value="<?= $apt->id ?>" <?= ($apartamento && $apartamento['apartamento_id'] == $apt->id) ? 'selected' : '' ?>>
                                                Bloque <?= $apt->bloque ?> - Apt <?= $apt->numero_apartamento ?> (Escalera <?= $apt->escalera ?>, Piso <?= $apt->piso ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-secondary w-100 shadow-sm">
                                    <i class="bi bi-arrow-left-right"></i> Actualizar Apartamento
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Columna 2: Gestión de Controles Físicos -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-gradient bg-dark text-white py-3">
                            <h6 class="mb-0 fs-6 fw-bold">
                                <i class="bi bi-key-fill"></i> Controles de Estacionamiento
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            <?php if (!$apartamento): ?>
                                <div class="alert alert-info border-0 shadow-sm py-4 text-center">
                                    <i class="bi bi-info-circle-fill text-info" style="font-size: 2.5rem;"></i>
                                    <h6 class="mt-3 fw-bold">Sin Apartamento Asignado</h6>
                                    <p class="mb-0 text-muted small mt-2">Debe asignar un apartamento a este usuario (en la sección de la izquierda) antes de poder asignarle controles físicos de estacionamiento.</p>
                                </div>
                            <?php else: ?>
                                <!-- Controles Actuales -->
                                <div class="mb-4">
                                    <h6 class="fw-bold text-muted small mb-3">CONTROLES ASIGNADOS (<?= count($controlesActuales) ?> / <?= $apartamento['cantidad_controles'] ?>)</h6>
                                    
                                    <?php if (empty($controlesActuales)): ?>
                                        <div class="text-center text-muted py-4 bg-light rounded-3 border">
                                            <i class="bi bi-key" style="font-size: 2rem;"></i>
                                            <p class="mb-0 mt-2 small">No hay controles asignados para este apartamento.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="list-group shadow-sm">
                                            <?php foreach ($controlesActuales as $control): ?>
                                                <div class="list-group-item py-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div>
                                                            <span class="fs-5 fw-bold text-dark"><?= htmlspecialchars($control->numero_control_completo) ?></span>
                                                            <span class="badge
                                                                <?php if ($control->estado === 'activo'): ?>bg-success
                                                                <?php elseif ($control->estado === 'bloqueado'): ?>bg-danger
                                                                <?php elseif ($control->estado === 'suspendido'): ?>bg-warning text-dark
                                                                <?php else: ?>bg-secondary<?php endif; ?> ms-2">
                                                                <?= ucfirst($control->estado) ?>
                                                            </span>
                                                            <?php if ($control->fecha_asignacion): ?>
                                                                <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                                                    <i class="bi bi-calendar"></i> Asignado: <?= date('d/m/Y', strtotime($control->fecha_asignacion)) ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                                onclick="removerControlAjax(<?= $control->id ?>, '<?= htmlspecialchars($control->numero_control_completo) ?>', <?= $usuarioGestionado->id ?>)">
                                                            <i class="bi bi-trash"></i> Remover
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Cambiar Estado Form -->
                                                    <form method="POST" action="<?= url('operador/cambiar-estado-control') ?>" class="form-cambiar-estado mt-2">
                                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                        <input type="hidden" name="control_id" value="<?= $control->id ?>">
                                                        <input type="hidden" name="usuario_id" value="<?= $usuarioGestionado->id ?>">
                                                        
                                                        <div class="row g-2">
                                                            <div class="col-7">
                                                                <select class="form-select form-select-sm" name="estado" id="estado_<?= $control->id ?>" onchange="toggleMotivo(<?= $control->id ?>, this.value)">
                                                                    <option value="activo" <?= $control->estado === 'activo' ? 'selected' : '' ?>>Activo</option>
                                                                    <option value="bloqueado" <?= $control->estado === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                                                                    <option value="suspendido" <?= $control->estado === 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
                                                                    <option value="desactivado" <?= $control->estado === 'desactivado' ? 'selected' : '' ?>>Desactivado</option>
                                                                    <option value="perdido" <?= $control->estado === 'perdido' ? 'selected' : '' ?>>Perdido</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-5">
                                                                <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                                                    <i class="bi bi-check-lg"></i> Guardar Estado
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="mt-2" id="motivo_wrapper_<?= $control->id ?>" style="display: <?= $control->estado !== 'activo' ? 'block' : 'none' ?>;">
                                                            <input type="text" name="motivo" class="form-control form-control-sm" placeholder="Especificar motivo del estado..." value="<?= htmlspecialchars($control->motivo_estado ?? '') ?>">
                                                        </div>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Asignar Nuevos Controles -->
                                <div class="mt-3 pt-3 border-top">
                                    <h6 class="fw-bold text-muted small mb-3">ASIGNAR NUEVO CONTROL</h6>
                                    <?php if (empty($controlesDisponibles)): ?>
                                        <div class="alert alert-warning py-3 text-center border-0 shadow-sm">
                                            <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem;"></i>
                                            <p class="mb-0 small mt-1">No hay controles físicos disponibles en el sistema.</p>
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" action="<?= url('operador/asignar-control-usuario') ?>" id="formAsignarControl">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="usuario_id" value="<?= $usuarioGestionado->id ?>">

                                            <div class="mb-3">
                                                <label for="control_id" class="form-label fw-semibold text-muted small">Seleccionar Control Disponible</label>
                                                <select class="form-select" name="control_id" id="control_id" required>
                                                    <option value="">-- Seleccionar control libre --</option>
                                                    <?php foreach ($controlesDisponibles as $ctrl): ?>
                                                        <option value="<?= $ctrl['id'] ?>">
                                                            <?= htmlspecialchars($ctrl['numero_control_completo']) ?> (Posición <?= $ctrl['posicion_numero'] ?>, Receptor <?= $ctrl['receptor'] ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <button type="submit" class="btn btn-success w-100 shadow-sm">
                                                <i class="bi bi-plus-circle"></i> Asignar Control
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function toggleExoneracionInput(checked) {
                const wrapper = document.getElementById('motivo_exoneracion_wrapper');
                if (checked) {
                    wrapper.style.display = 'block';
                    document.getElementById('motivo_exoneracion').setAttribute('required', 'required');
                } else {
                    wrapper.style.display = 'none';
                    document.getElementById('motivo_exoneracion').removeAttribute('required');
                }
            }

            function toggleMotivo(controlId, val) {
                const wrapper = document.getElementById('motivo_wrapper_' + controlId);
                if (val !== 'activo') {
                    wrapper.style.display = 'block';
                    wrapper.querySelector('input').setAttribute('required', 'required');
                } else {
                    wrapper.style.display = 'none';
                    wrapper.querySelector('input').removeAttribute('required');
                }
            }
        </script>
        <?php
        echo ob_get_clean();
        exit;
    }

    /**
     * Guardar datos básicos del usuario desde AJAX
     */
    public function guardarUsuarioAjax(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Token de seguridad inválido']);
            return;
        }

        $usuarioId = intval($_POST['usuario_id'] ?? 0);
        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $activo = isset($_POST['activo']) && $_POST['activo'] === '1';
        $exonerado = isset($_POST['exonerado']) && $_POST['exonerado'] === '1';
        $motivoExoneracion = trim($_POST['motivo_exoneracion'] ?? '');

        if (!$usuarioId) {
            $this->jsonResponse(['success' => false, 'message' => 'Usuario no especificado']);
            return;
        }

        $usuarioGestionado = Usuario::findById($usuarioId);
        if (!$usuarioGestionado) {
            $this->jsonResponse(['success' => false, 'message' => 'Usuario no encontrado']);
            return;
        }

        // Validaciones básicas
        if (empty($nombreCompleto) || empty($email)) {
            $this->jsonResponse(['success' => false, 'message' => 'Nombre completo y Email son requeridos']);
            return;
        }

        // Validar duplicado de email
        $existente = Usuario::findByEmail($email);
        if ($existente && $existente->id !== $usuarioId) {
            $this->jsonResponse(['success' => false, 'message' => 'El correo electrónico ya está registrado por otro usuario']);
            return;
        }

        // Actualizar datos
        $dataUpdate = [
            'nombre_completo' => $nombreCompleto,
            'email' => $email,
            'telefono' => $telefono ?: null,
            'activo' => $activo,
            'exonerado' => $exonerado,
            'motivo_exoneracion' => $exonerado ? $motivoExoneracion : null
        ];

        if ($usuarioGestionado->update($dataUpdate)) {
            $this->jsonResponse(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'No se realizaron cambios o hubo un error al actualizar']);
        }
    }

    /**
     * Guardar/Modificar/Eliminar asignación de apartamento a usuario desde AJAX
     */
    public function guardarApartamentoUsuarioAjax(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Token de seguridad inválido']);
            return;
        }

        $usuarioId = intval($_POST['usuario_id'] ?? 0);
        $apartamentoId = intval($_POST['apartamento_id'] ?? 0); // 0 significa desasignar

        if (!$usuarioId) {
            $this->jsonResponse(['success' => false, 'message' => 'Usuario no especificado']);
            return;
        }

        $usuarioGestionado = Usuario::findById($usuarioId);
        if (!$usuarioGestionado) {
            $this->jsonResponse(['success' => false, 'message' => 'Usuario no encontrado']);
            return;
        }

        try {
            // Obtener asignación actual
            $sql = "SELECT id, apartamento_id FROM apartamento_usuario WHERE usuario_id = ? AND activo = TRUE LIMIT 1";
            $asignacionActual = Database::fetchOne($sql, [$usuarioId]);

            if ($apartamentoId > 0) {
                // Verificar que el apartamento existe y está activo
                $apartamento = Apartamento::findById($apartamentoId);
                if (!$apartamento || !$apartamento->activo) {
                    $this->jsonResponse(['success' => false, 'message' => 'Apartamento no válido o inactivo']);
                    return;
                }

                if ($asignacionActual) {
                    if ($asignacionActual['apartamento_id'] != $apartamentoId) {
                        // Liberar controles del apartamento anterior
                        $sqlLiberar = "UPDATE controles_estacionamiento 
                                       SET apartamento_usuario_id = NULL, estado = 'vacio', motivo_estado = NULL, aprobado_por = NULL, fecha_asignacion = NULL, fecha_estado = NULL
                                       WHERE apartamento_usuario_id = ?";
                        Database::execute($sqlLiberar, [$asignacionActual['id']]);

                        // Desactivar asignación vieja
                        $sqlDesactivar = "UPDATE apartamento_usuario SET activo = FALSE WHERE id = ?";
                        Database::execute($sqlDesactivar, [$asignacionActual['id']]);

                        // Crear asignación nueva
                        $sqlInsertar = "INSERT INTO apartamento_usuario (usuario_id, apartamento_id, activo, fecha_asignacion, cantidad_controles)
                                        VALUES (?, ?, TRUE, NOW(), 0)";
                        Database::execute($sqlInsertar, [$usuarioId, $apartamentoId]);

                        writeLog("Operador {$usuario->email} cambió apartamento del usuario {$usuarioGestionado->email} al apartamento ID {$apartamentoId}", 'info');
                    }
                } else {
                    // Crear nueva asignación
                    $sqlInsertar = "INSERT INTO apartamento_usuario (usuario_id, apartamento_id, activo, fecha_asignacion, cantidad_controles)
                                    VALUES (?, ?, TRUE, NOW(), 0)";
                    Database::execute($sqlInsertar, [$usuarioId, $apartamentoId]);

                    writeLog("Operador {$usuario->email} asignó apartamento ID {$apartamentoId} al usuario {$usuarioGestionado->email}", 'info');
                }
            } else {
                // Desasignar apartamento (si tiene)
                if ($asignacionActual) {
                    // Liberar controles del apartamento anterior
                    $sqlLiberar = "UPDATE controles_estacionamiento 
                                   SET apartamento_usuario_id = NULL, estado = 'vacio', motivo_estado = NULL, aprobado_por = NULL, fecha_asignacion = NULL, fecha_estado = NULL
                                   WHERE apartamento_usuario_id = ?";
                    Database::execute($sqlLiberar, [$asignacionActual['id']]);

                    // Desactivar asignación
                    $sqlDesactivar = "UPDATE apartamento_usuario SET activo = FALSE WHERE id = ?";
                    Database::execute($sqlDesactivar, [$asignacionActual['id']]);

                    writeLog("Operador {$usuario->email} desasignó apartamento del usuario {$usuarioGestionado->email}", 'info');
                }
            }

            $this->jsonResponse(['success' => true, 'message' => 'Apartamento actualizado correctamente']);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
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
                redirect('operador/controles');
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
                redirect('operador/controles');
            }
        } else {
            $errorMsg = 'Error al remover el control';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMsg]);
            } else {
                $_SESSION['error'] = $errorMsg;
                redirect('operador/controles');
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
                redirect('operador/controles');
            }
            return;
        }

        // Obtener apartamento_usuario_id del usuario
        $sql = "SELECT id FROM apartamento_usuario WHERE usuario_id = ? AND activo = TRUE LIMIT 1";
        $apartamentoUsuario = Database::fetchOne($sql, [$usuarioId]);

        if (!$apartamentoUsuario) {
            $errorMsg = 'El usuario no tiene un apartamento asignado';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMsg]);
            } else {
                $_SESSION['error'] = $errorMsg;
                redirect('operador/controles');
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
                redirect('operador/controles');
            }
        } else {
            $errorMsg = 'Error al asignar el control';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMsg]);
            } else {
                $_SESSION['error'] = $errorMsg;
                redirect('operador/controles');
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

        $apartamentoUsuarioId = intval($_POST['apartamento_usuario_id'] ?? $_POST['asignar_usuario_id'] ?? 0);

        // Si se especificó un residente para asignar
        if ($apartamentoUsuarioId > 0 && $nuevoEstado !== 'vacio') {
            if ($control->asignar($apartamentoUsuarioId, $operador->id, $nuevoEstado)) {
                $success = true;
                $successMessage = "Control {$control->numero_control_completo} actualizado y asignado al residente correctamente";
            } else {
                $errorMessage = 'Error al asignar el control al residente';
            }
        } elseif ($nuevoEstado === 'vacio') {
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
                WHERE au.usuario_id = ? AND au.activo = TRUE
                LIMIT 1";
        $apartamento = Database::fetchOne($sql, [$usuario->id]);

        // Obtener controles asignados (si tiene)
        $sql = "SELECT ce.numero_control_completo, ce.estado, ce.fecha_asignacion
                FROM apartamento_usuario au
                LEFT JOIN controles_estacionamiento ce ON ce.apartamento_usuario_id = au.id
                WHERE au.usuario_id = ? AND au.activo = TRUE
                ORDER BY ce.numero_control_completo";
        $controles = Database::fetchAll($sql, [$usuario->id]);

        // Filtrar controles válidos (que no sean NULL)
        $controles = array_filter($controles, function($c) {
            return !empty($c['numero_control_completo']);
        });

        // Obtener todos los apartamentos disponibles para el selector
        $sql = "SELECT id, bloque, escalera, piso, numero_apartamento
                FROM apartamentos
                WHERE activo = TRUE
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
                    WHERE usuario_id = ? AND activo = TRUE LIMIT 1";
            $asignacionActual = Database::fetchOne($sql, [$usuario->id]);

            if ($apartamentoId > 0) {
                // Usuario quiere tener un apartamento asignado
                if ($asignacionActual) {
                    // Ya tiene un apartamento, verificar si cambió
                    if ($asignacionActual['apartamento_id'] != $apartamentoId) {
                        // Desactivar asignación anterior
                        $sql = "UPDATE apartamento_usuario SET activo = FALSE WHERE id = ?";
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
                    $sql = "UPDATE apartamento_usuario SET activo = FALSE WHERE id = ?";
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

    private function processImageAndSave(string $tmpPath, string $originalName, string $destPath): ?string
    {
        $fileData = @file_get_contents($tmpPath);
        if (!$fileData) {
            return null;
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($ext === 'pdf') {
            @move_uploaded_file($tmpPath, $destPath);
            return 'data:application/pdf;base64,' . base64_encode($fileData);
        }

        if (!function_exists('imagecreatefromstring')) {
            @move_uploaded_file($tmpPath, $destPath);
            $mime = ($ext === 'png') ? 'image/png' : (($ext === 'webp') ? 'image/webp' : 'image/jpeg');
            return "data:{$mime};base64," . base64_encode($fileData);
        }

        $image = @imagecreatefromstring($fileData);
        if (!$image) {
            @move_uploaded_file($tmpPath, $destPath);
            $mime = ($ext === 'png') ? 'image/png' : (($ext === 'webp') ? 'image/webp' : 'image/jpeg');
            return "data:{$mime};base64," . base64_encode($fileData);
        }

        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($tmpPath);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;
                }
            }
        }

        $origW = imagesx($image);
        $origH = imagesy($image);
        $maxDim = 1920;

        if ($origW > $maxDim || $origH > $maxDim) {
            if ($origW > $origH) {
                $newW = $maxDim;
                $newH = intval($origH * ($maxDim / $origW));
            } else {
                $newH = $maxDim;
                $newW = intval($origW * ($maxDim / $origH));
            }
            $newImg = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($newImg, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($image);
            $image = $newImg;
        }

        ob_start();
        imagejpeg($image, null, 82);
        $compressedBytes = ob_get_clean();
        imagedestroy($image);

        if ($compressedBytes) {
            @file_put_contents($destPath, $compressedBytes);
            return 'data:image/jpeg;base64,' . base64_encode($compressedBytes);
        }

        @move_uploaded_file($tmpPath, $destPath);
        return 'data:image/jpeg;base64,' . base64_encode($fileData);
    }

    private function uploadGastoArchivo(array $file, string $prefix): ?string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            writeLog("Error uploadGastoArchivo ({$prefix}): UPLOAD_ERR code " . ($file['error'] ?? 'missing'), 'error');
            return null;
        }

        $uploadDir = GASTOS_PATH . '/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'heic', 'heif'];
        if (!in_array($ext, $allowed)) {
            writeLog("Error uploadGastoArchivo ({$prefix}): Extensión '$ext' no permitida", 'error');
            return null;
        }

        $nombreArchivo = $prefix . '_' . uniqid() . '.' . ($ext === 'pdf' ? 'pdf' : 'jpg');
        $rutaDestino = $uploadDir . $nombreArchivo;

        $dataUri = $this->processImageAndSave($file['tmp_name'], $file['name'], $rutaDestino);
        if ($dataUri) {
            return $dataUri;
        }

        writeLog("Error uploadGastoArchivo ({$prefix}): Falló procesamiento de archivo hacia $rutaDestino", 'error');
        return null;
    }

    /**
     * Registrar un nuevo gasto
     */
    public function registrarGasto(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $usuarioRol = 'operador';
        $postUrl = url('operador/process-registrar-gasto');

        require_once __DIR__ . '/../views/shared/registrar_gasto.php';
    }

    /**
     * Procesar registro de gasto
     */
    public function processRegistrarGasto(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('operador/registrar-gasto');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('operador/registrar-gasto');
            return;
        }

        $nombre = sanitize($_POST['nombre'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $monto = floatval($_POST['monto'] ?? 0);
        $moneda = $_POST['moneda'] ?? '';
        $metodoPago = $_POST['metodo_pago'] ?? '';
        $fechaGasto = $_POST['fecha_gasto'] ?? date('Y-m-d');

        // Validaciones
        if (empty($nombre)) {
            $_SESSION['error'] = 'El nombre del gasto es requerido';
            redirect('operador/registrar-gasto');
            return;
        }

        if ($monto <= 0) {
            $_SESSION['error'] = 'El monto debe ser mayor a 0';
            redirect('operador/registrar-gasto');
            return;
        }

        if (!in_array($moneda, ['USD', 'Bs'])) {
            $_SESSION['error'] = 'Moneda inválida';
            redirect('operador/registrar-gasto');
            return;
        }

        if (!in_array($metodoPago, ['efectivo', 'transferencia'])) {
            $_SESSION['error'] = 'Método de pago inválido';
            redirect('operador/registrar-gasto');
            return;
        }

        // Subida de archivos
        $comprobanteRuta = null;
        $reciboRuta = null;

        if (isset($_FILES['comprobante']['error']) && ($_FILES['comprobante']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['comprobante']['error'] === UPLOAD_ERR_FORM_SIZE)) {
            $_SESSION['error'] = 'El comprobante seleccionado supera el tamaño máximo de archivo permitido por el servidor.';
            redirect('operador/registrar-gasto');
            return;
        }

        if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] !== UPLOAD_ERR_NO_FILE) {
            $comprobanteRuta = $this->uploadGastoArchivo($_FILES['comprobante'], 'comprobante');
            if (!$comprobanteRuta) {
                $_SESSION['error'] = 'Error al subir el comprobante. Verifique que sea un archivo de imagen o PDF válido (JPG, PNG, WEBP, HEIC, PDF).';
                redirect('operador/registrar-gasto');
                return;
            }
        } else {
            $_SESSION['error'] = 'La foto del comprobante es obligatoria';
            redirect('operador/registrar-gasto');
            return;
        }

        if (isset($_FILES['recibo']) && $_FILES['recibo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $reciboRuta = $this->uploadGastoArchivo($_FILES['recibo'], 'recibo');
            if (!$reciboRuta) {
                $_SESSION['error'] = 'Error al subir el recibo. Debe ser una imagen o PDF válido.';
                redirect('operador/registrar-gasto');
                return;
            }
        }

        require_once __DIR__ . '/../models/Gasto.php';

        try {
            Gasto::registrar([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'monto' => $monto,
                'moneda' => $moneda,
                'metodo_pago' => $metodoPago,
                'fecha_gasto' => $fechaGasto,
                'comprobante_ruta' => $comprobanteRuta,
                'recibo_ruta' => $reciboRuta,
                'registrado_por' => $usuario->id
            ]);

            writeLog("Gasto registrado por operador {$usuario->email}: $nombre ($monto $moneda)", 'info');
            $_SESSION['success'] = 'Gasto registrado correctamente';
            redirect('operador/historial-gastos');

        } catch (Exception $e) {
            writeLog("Error al registrar gasto: " . $e->getMessage(), 'error');
            $_SESSION['error'] = 'Error interno al registrar el gasto. Intente de nuevo.';
            redirect('operador/registrar-gasto');
        }
    }

    /**
     * Ver historial de gastos
     */
    public function historialGastos(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $usuarioRol = 'operador';

        // Filtros
        $filtros = [
            'buscar' => $_GET['buscar'] ?? null,
            'moneda' => $_GET['moneda'] ?? null,
            'metodo_pago' => $_GET['metodo_pago'] ?? null,
            'mes' => $_GET['mes'] ?? null,
            'anio' => $_GET['anio'] ?? null
        ];

        require_once __DIR__ . '/../models/Gasto.php';
        $gastos = Gasto::getAllConFiltros($filtros);

        require_once __DIR__ . '/../views/shared/historial_gastos.php';
    }

    /**
     * Cargar deuda histórica para un usuario (Operador)
     */
    public function cargarDeudaHistorica(): void
    {
        $operador = $this->checkAuth();
        if (!$operador) return;

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit;
            }
            redirect('operador/clientes-controles');
            return;
        }

        $usuarioId = intval($_POST['usuario_id'] ?? 0);
        $mesInicio = intval($_POST['mes_inicio'] ?? 0);
        $anioInicio = intval($_POST['anio_inicio'] ?? 0);

        if ($usuarioId <= 0 || $mesInicio < 1 || $mesInicio > 12 || $anioInicio < 2020 || $anioInicio > intval(date('Y'))) {
            $msg = 'Parámetros inválidos para cargar la deuda histórica';
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            $_SESSION['error'] = $msg;
            redirect('operador/clientes-controles');
            return;
        }

        $result = Mensualidad::cargarDeudaHistorica($usuarioId, $mesInicio, $anioInicio);

        if ($isAjax) {
            echo json_encode($result);
            exit;
        }

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        redirect('operador/clientes-controles');
    }

    /**
     * Revertir / Eliminar deuda histórica sin pagos aprobados para un usuario
     */
    public function revertirDeudaHistorica(): void
    {
        $operador = $this->checkAuth();
        if (!$operador) return;

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit;
            }
            redirect('operador/clientes-controles');
            return;
        }

        $usuarioId = intval($_POST['usuario_id'] ?? 0);
        $mesInicio = intval($_POST['mes_inicio'] ?? 0);
        $anioInicio = intval($_POST['anio_inicio'] ?? 0);
        $mesFin = !empty($_POST['mes_fin']) ? intval($_POST['mes_fin']) : null;
        $anioFin = !empty($_POST['anio_fin']) ? intval($_POST['anio_fin']) : null;

        if ($usuarioId <= 0 || $mesInicio < 1 || $mesInicio > 12 || $anioInicio < 2020) {
            $msg = 'Parámetros inválidos para revertir la deuda histórica';
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            $_SESSION['error'] = $msg;
            redirect('operador/clientes-controles');
            return;
        }

        $result = Mensualidad::revertirDeudaHistorica($usuarioId, $mesInicio, $anioInicio, $mesFin, $anioFin);

        if ($isAjax) {
            echo json_encode($result);
            exit;
        }

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        redirect('operador/clientes-controles');
    }
}

