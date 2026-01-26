<?php
/**
 * ClienteController - Funcionalidades para residentes
 *
 * Dashboard, estado de cuenta, registro de pagos, historial
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Mensualidad.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/Control.php';
require_once __DIR__ . '/../models/Apartamento.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';

class ClienteController
{
    /**
     * Verificar que el usuario esté autenticado como cliente
     */
    private function checkAuth(): ?Usuario
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'cliente') {
            redirect('auth/login');
            return null;
        }

        $usuario = Usuario::findById($_SESSION['user_id']);

        if (!$usuario || !$usuario->activo) {
            session_destroy();
            redirect('auth/login');
            return null;
        }

        // 🔒 SEGURIDAD CRÍTICA: Verificar si el usuario debe cambiar contraseña obligatoriamente
        require_once __DIR__ . '/AuthController.php';
        AuthController::forzarCambioPasswordSiNecesario($usuario);

        return $usuario;
    }

    /**
     * Dashboard del cliente
     */
    public function dashboard(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Obtener mensualidades vencidas + mes actual (que requieren atención inmediata)
        $todasMensualidadesPendientes = Mensualidad::getPendientesByUsuario($usuario->id, false);
        $mesActual = date('Y-m');
        $mensualidadesPendientes = array_filter($todasMensualidadesPendientes, function($m) use ($mesActual) {
            $fechaVencimiento = strtotime($m->fecha_vencimiento);
            $mesVencimiento = date('Y-m', $fechaVencimiento);
            // Incluir: mes actual (siempre) + meses vencidos que no sean del mes actual
            return $mesVencimiento === $mesActual || ($fechaVencimiento < time() && $mesVencimiento !== $mesActual);
        });

        // Calcular deuda total
        $deudaInfo = Mensualidad::calcularDeudaTotal($usuario->id);

        // Obtener últimos 5 pagos
        $ultimosPagos = Pago::getByUsuario($usuario->id, 5);

        // Obtener controles asignados
        $controles = $this->getControlesUsuario($usuario->id);

        // Verificar si tiene pagos pendientes de aprobación
        $pagosPendientesAprobacion = Pago::getPendientesByUsuario($usuario->id);

        // Obtener notificaciones no leídas
        $notificaciones = $this->getNotificacionesNoLeidas($usuario->id);

        require_once __DIR__ . '/../views/cliente/dashboard.php';
    }

    /**
     * Estado de cuenta detallado
     */
    public function estadoCuenta(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Obtener todas las mensualidades
        $mensualidades = Mensualidad::getAllByUsuario($usuario->id);

        // Obtener todos los pagos
        $pagos = Pago::getByUsuario($usuario->id);

        // Calcular estadísticas
        $deudaInfo = Mensualidad::calcularDeudaTotal($usuario->id);

        require_once __DIR__ . '/../views/cliente/estado_cuenta.php';
    }

    /**
     * Formulario para registrar nuevo pago
     */
    public function registrarPago(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Manejar solicitud para generar mensualidades futuras
        if (isset($_GET['generar_futuras'])) {
            $mesesAGenerar = intval($_GET['generar_futuras']);
            if ($mesesAGenerar > 0 && $mesesAGenerar <= 24) { // Máximo 24 meses
                try {
                    $generadas = Mensualidad::generarMensualidadesFuturas($usuario->id, $mesesAGenerar);
                    $_SESSION['success'] = "Se han generado {$mesesAGenerar} mensualidades futuras para que puedas pagar por adelantado";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error al generar mensualidades futuras: " . $e->getMessage();
                }
            }
            // Redireccionar para limpiar el parámetro
            header('Location: ' . url('cliente/registrar-pago'));
            exit;
        }

        // Obtener mensualidades disponibles para pago (vencidas + futuras para pago adelantado)
        $mensualidadesPendientes = Mensualidad::getMensualidadesParaPagoAdelantado($usuario->id, 12); // Aumentar a 12 meses

        // Obtener tasa BCV actual
        $tasaBCV = $this->getTasaBCVActual();

        require_once __DIR__ . '/../views/cliente/registrar_pago.php';
    }

    /**
     * Procesar registro de pago
     */
    public function processRegistrarPago(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('cliente/registrar-pago');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('cliente/registrar-pago');
            return;
        }

        $moneda = $_POST['moneda'] ?? '';
        $monto = floatval($_POST['monto'] ?? 0);
        $metodoPago = $_POST['metodo_pago'] ?? '';
        $referencia = sanitize($_POST['referencia'] ?? '');
        $fechaPago = $_POST['fecha_pago'] ?? date('Y-m-d');
        $mensualidadesSeleccionadas = $_POST['mensualidades'] ?? [];

        // Validaciones
        if (empty($moneda) || !in_array($moneda, ['USD', 'Bs'])) {
            $_SESSION['error'] = 'Moneda inválida';
            redirect('cliente/registrar-pago');
            return;
        }

        if ($monto <= 0) {
            $_SESSION['error'] = 'El monto debe ser mayor a 0';
            redirect('cliente/registrar-pago');
            return;
        }

        if (empty($metodoPago)) {
            $_SESSION['error'] = 'Debe seleccionar un método de pago';
            redirect('cliente/registrar-pago');
            return;
        }

        if (empty($mensualidadesSeleccionadas)) {
            $_SESSION['error'] = 'Debe seleccionar al menos una mensualidad';
            redirect('cliente/registrar-pago');
            return;
        }

        // VALIDACIÓN CRÍTICA: Verificar que las mensualidades seleccionadas sean consecutivas desde la más antigua pendiente
        // Esto previene que los clientes salten meses manipulando el formulario
        $mensualidadesPermitidas = Mensualidad::getMensualidadesParaPagoAdelantado($usuario->id, 12);
        $idsPermitidos = array_map(fn($m) => $m->id, $mensualidadesPermitidas);

        // Verificar que TODAS las mensualidades seleccionadas estén en la lista permitida
        foreach ($mensualidadesSeleccionadas as $mensualidadId) {
            if (!in_array($mensualidadId, $idsPermitidos)) {
                writeLog("Intento de pago no autorizado: Usuario {$usuario->id} intentó pagar mensualidad ID {$mensualidadId} que no está en la lista permitida", 'warning');
                $_SESSION['error'] = 'No puede saltarse mensualidades. Debe pagar desde la mensualidad más antigua pendiente.';
                redirect('cliente/registrar-pago');
                return;
            }
        }

        // Verificar que las mensualidades seleccionadas sean consecutivas (sin saltos)
        $indicesSeleccionados = [];
        foreach ($mensualidadesSeleccionadas as $mensualidadId) {
            $indice = array_search($mensualidadId, $idsPermitidos);
            if ($indice !== false) {
                $indicesSeleccionados[] = $indice;
            }
        }

        // Verificar que los índices sean consecutivos desde el inicio
        sort($indicesSeleccionados);
        for ($i = 0; $i < count($indicesSeleccionados); $i++) {
            if ($indicesSeleccionados[$i] !== $i) {
                writeLog("Intento de pago con saltos: Usuario {$usuario->id} seleccionó índices no consecutivos: " . implode(',', $indicesSeleccionados), 'warning');
                $_SESSION['error'] = 'Debe seleccionar mensualidades consecutivas desde la más antigua pendiente.';
                redirect('cliente/registrar-pago');
                return;
            }
        }

        // Validar que se suba comprobante para métodos que lo requieren
        $metodosConComprobante = ['transferencia', 'pago_movil'];
        if (in_array($metodoPago, $metodosConComprobante) && 
            (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] === UPLOAD_ERR_NO_FILE)) {
            $_SESSION['error'] = 'Debe subir el comprobante de pago para el método seleccionado';
            redirect('cliente/registrar-pago');
            return;
        }

        // Validar archivo de comprobante si fue subido
        $rutaComprobante = null;
        if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] !== UPLOAD_ERR_NO_FILE) {
            $validacion = ValidationHelper::validateFile(
                $_FILES['comprobante'],
                ['jpg', 'jpeg', 'png', 'pdf'],
                5 * 1024 * 1024 // 5MB
            );

            if (!$validacion['valid']) {
                $_SESSION['error'] = implode('<br>', $validacion['errors']);
                redirect('cliente/registrar-pago');
                return;
            }

            // Subir archivo
            $rutaComprobante = $this->uploadComprobante($_FILES['comprobante'], $usuario->id);

            if (!$rutaComprobante) {
                $_SESSION['error'] = 'Error al subir el comprobante';
                redirect('cliente/registrar-pago');
                return;
            }
        }

        // Obtener apartamento_usuario_id del cliente
        $sqlApartamento = "SELECT id FROM apartamento_usuario WHERE usuario_id = ? AND activo = TRUE LIMIT 1";
        $apartamentoData = Database::fetchOne($sqlApartamento, [$usuario->id]);
        $apartamentoUsuarioId = $apartamentoData['id'] ?? null;

        if (!$apartamentoUsuarioId) {
            $_SESSION['error'] = 'No se encontró información del apartamento';
            redirect('cliente/registrar-pago');
            return;
        }

        // Registrar pago
        try {
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
            
            $pagoId = Pago::registrar([
                'apartamento_usuario_id' => $apartamentoUsuarioId,
                'moneda_pago' => $monedaPago,
                'fecha_pago' => $fechaPago,
                'comprobante_ruta' => $rutaComprobante,
                'mensualidades_ids' => $mensualidadesSeleccionadas,
                'registrado_por' => $usuario->id,
                'estado_comprobante' => 'pendiente' // Siempre pendiente para revisión del operador
            ]);

            writeLog("Pago registrado por cliente {$usuario->email}: ID $pagoId, Moneda: $monedaPago", 'info');

            $_SESSION['success'] = 'Pago registrado correctamente. Será revisado por un operador';
            redirect('cliente/historial-pagos');

        } catch (Exception $e) {
            writeLog("Error al registrar pago: " . $e->getMessage(), 'error');
            $_SESSION['error'] = 'Error al registrar el pago. Intente nuevamente';
            redirect('cliente/registrar-pago');
        }
    }

    /**
     * Historial de pagos del cliente
     */
    public function historialPagos(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Filtros
        $filtros = [
            'estado' => $_GET['estado'] ?? null,
            'mes' => $_GET['mes'] ?? null,
            'anio' => $_GET['anio'] ?? null
        ];

        $pagos = Pago::getByUsuarioConFiltros($usuario->id, $filtros);

        require_once __DIR__ . '/../views/cliente/historial_pagos.php';
    }

    /**
     * Ver detalle de un pago
     */
    public function verPago(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $pagoId = intval($_GET['id'] ?? 0);

        if (!$pagoId) {
            redirect('cliente/historial-pagos');
            return;
        }

        $pago = Pago::findById($pagoId);

        // Verificar que el pago pertenezca al usuario a través de apartamento_usuario
        $perteneceAlUsuario = false;
        if ($pago) {
            $sql = "SELECT 1 FROM apartamento_usuario WHERE id = ? AND usuario_id = ?";
            $perteneceAlUsuario = Database::fetchOne($sql, [$pago->apartamento_usuario_id, $usuario->id]);
        }

        if (!$pago || !$perteneceAlUsuario) {
            $_SESSION['error'] = 'Pago no encontrado';
            redirect('cliente/historial-pagos');
            return;
        }

        // Obtener mensualidades asociadas
        $mensualidades = Pago::getMensualidadesPago($pagoId);

        require_once __DIR__ . '/../views/cliente/ver_pago.php';
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
            redirect('cliente/historial-pagos');
            return;
        }

        $pago = Pago::findById($pagoId);

        // Verificar que el pago pertenezca al usuario a través de apartamento_usuario
        $perteneceAlUsuario = false;
        if ($pago) {
            $sql = "SELECT 1 FROM apartamento_usuario WHERE id = ? AND usuario_id = ?";
            $perteneceAlUsuario = Database::fetchOne($sql, [$pago->apartamento_usuario_id, $usuario->id]);
        }

        if (!$pago || !$perteneceAlUsuario) {
            $_SESSION['error'] = 'Pago no encontrado';
            redirect('cliente/historial-pagos');
            return;
        }

        if ($pago->estado !== 'aprobado') {
            $_SESSION['error'] = 'Solo se pueden descargar recibos de pagos aprobados';
            redirect('cliente/ver-pago?id=' . $pagoId);
            return;
        }

        // Generar PDF
        $rutaPdf = $pago->generarRecibo();

        if (!$rutaPdf || !file_exists($rutaPdf)) {
            $_SESSION['error'] = 'Error al generar el recibo';
            redirect('cliente/ver-pago?id=' . $pagoId);
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
     * Ver controles asignados
     */
    public function controles(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $controles = $this->getControlesUsuario($usuario->id);

        // Obtener información de los apartamentos
        $apartamentos = [];
        foreach ($controles as $control) {
            if (!isset($apartamentos[$control['apartamento_usuario_id']])) {
                $sql = "SELECT a.*, au.cantidad_controles
                        FROM apartamentos a
                        JOIN apartamento_usuario au ON au.apartamento_id = a.id
                        WHERE au.id = ?";
                $apartamento = Database::fetchOne($sql, [$control['apartamento_usuario_id']]);
                $apartamentos[$control['apartamento_usuario_id']] = $apartamento;
            }
        }

        require_once __DIR__ . '/../views/cliente/controles.php';
    }

    /**
     * Perfil del usuario
     */
    public function perfil(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Obtener información del apartamento
        $sql = "SELECT a.bloque, a.escalera, a.piso, a.numero_apartamento
                FROM apartamento_usuario au
                JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE au.usuario_id = ? AND au.activo = 1
                LIMIT 1";
        $apartamento = Database::fetchOne($sql, [$usuario->id]);

        // Obtener controles asignados
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

        require_once __DIR__ . '/../views/cliente/perfil.php';
    }

    /**
     * Actualizar perfil
     */
    public function updatePerfil(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('cliente/perfil');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('cliente/perfil');
            return;
        }

        $nombreCompleto = sanitize($_POST['nombre_completo'] ?? '');
        $telefono = sanitize($_POST['telefono'] ?? '');
        $direccion = sanitize($_POST['direccion'] ?? '');

        // Validar nombre completo
        if (empty($nombreCompleto) || strlen($nombreCompleto) < 3) {
            $_SESSION['error'] = 'El nombre completo debe tener al menos 3 caracteres';
            redirect('cliente/perfil');
            return;
        }

        // Validar teléfono
        if (!empty($telefono) && !ValidationHelper::validatePhone($telefono)) {
            $_SESSION['error'] = 'Formato de teléfono inválido';
            redirect('cliente/perfil');
            return;
        }

        // Actualizar
        $usuario->update([
            'nombre_completo' => $nombreCompleto,
            'telefono' => $telefono,
            'direccion' => $direccion
        ]);

        // Actualizar sesión si cambió el nombre
        if ($nombreCompleto !== $_SESSION['user_nombre']) {
            $_SESSION['user_nombre'] = $nombreCompleto;
        }

        $_SESSION['success'] = 'Perfil actualizado correctamente';
        redirect('cliente/perfil');
    }

    /**
     * Solicitudes del cliente
     */
    public function solicitudes(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Obtener controles del usuario para las opciones de solicitud
        $controles = $this->getControlesUsuario($usuario->id);
        
        // Obtener historial de solicitudes
        $historialSolicitudes = $this->getSolicitudesUsuario($usuario->id);

        require_once __DIR__ . '/../views/cliente/solicitudes.php';
    }

    /**
     * Obtener historial de solicitudes del usuario
     */
    private function getSolicitudesUsuario($usuarioId): array
    {
        $sql = "SELECT s.*, 
                       c.numero_control_completo as control_numero
                FROM solicitudes_cambios s
                JOIN apartamento_usuario au ON au.id = s.apartamento_usuario_id
                LEFT JOIN controles_estacionamiento c ON c.id = s.control_id
                WHERE au.usuario_id = ?
                ORDER BY s.fecha_solicitud DESC";
        
        return Database::fetchAll($sql, [$usuarioId]);
    }

    /**
     * Procesar solicitud del cliente
     */
    public function processSolicitud(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('cliente/solicitudes');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('cliente/solicitudes');
            return;
        }

        $tipoSolicitud = $_POST['tipo_solicitud'] ?? '';
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $controlId = intval($_POST['control_id'] ?? 0);

        // Debug log
        writeLog("ClienteController::processSolicitud - tipo_solicitud recibido: '$tipoSolicitud'", 'debug');

        // Validaciones
        $tiposPermitidos = ['desincorporar_control', 'reportar_perdido', 'agregar_control', 'comprar_control', 'solicitud_personalizada'];
        if (empty($tipoSolicitud) || !in_array($tipoSolicitud, $tiposPermitidos)) {
            $_SESSION['error'] = 'Debe seleccionar un tipo de solicitud válido';
            redirect('cliente/solicitudes');
            return;
        }

        if (empty($descripcion)) {
            $_SESSION['error'] = 'Debe proporcionar una descripción';
            redirect('cliente/solicitudes');
            return;
        }

        // Validar que el control pertenezca al usuario si se especifica
        if ($controlId > 0) {
            $sqlCheck = "SELECT 1 FROM controles_estacionamiento c
                        JOIN apartamento_usuario au ON au.id = c.apartamento_usuario_id
                        WHERE c.id = ? AND au.usuario_id = ?";
            $pertenece = Database::fetchOne($sqlCheck, [$controlId, $usuario->id]);
            if (!$pertenece) {
                $_SESSION['error'] = 'El control seleccionado no le pertenece';
                redirect('cliente/solicitudes');
                return;
            }
        }

        // Obtener apartamento_usuario_id del cliente
        $sqlApartamento = "SELECT id FROM apartamento_usuario WHERE usuario_id = ? AND activo = TRUE LIMIT 1";
        $apartamentoData = Database::fetchOne($sqlApartamento, [$usuario->id]);
        $apartamentoUsuarioId = $apartamentoData['id'] ?? null;

        if (!$apartamentoUsuarioId) {
            $_SESSION['error'] = 'No se encontró información del apartamento';
            redirect('cliente/solicitudes');
            return;
        }

        // Crear la solicitud
        try {
            $sql = "INSERT INTO solicitudes_cambios (
                        apartamento_usuario_id, tipo_solicitud, cantidad_controles_nueva,
                        control_id, motivo, estado, fecha_solicitud
                    ) VALUES (?, ?, NULL, ?, ?, 'pendiente', NOW())";

            $params = [
                $apartamentoUsuarioId,
                $tipoSolicitud,
                $controlId > 0 ? $controlId : null,
                $descripcion
            ];

            Database::execute($sql, $params);

            $_SESSION['success'] = 'Solicitud enviada correctamente. Será revisada por un operador.';
            redirect('cliente/solicitudes');

        } catch (Exception $e) {
            writeLog("Error al crear solicitud: " . $e->getMessage(), 'error');
            $_SESSION['error'] = 'Error al enviar la solicitud. Intente nuevamente';
            redirect('cliente/solicitudes');
        }
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        require_once __DIR__ . '/../views/cliente/cambiar_password.php';
    }

    /**
     * Procesar cambio de contraseña
     */
    public function processCambiarPassword(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('cliente/cambiar-password');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('cliente/cambiar-password');
            return;
        }

        $passwordActual = $_POST['password_actual'] ?? '';
        $passwordNueva = $_POST['password_nueva'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Validar contraseña actual
        $sql = "SELECT password FROM usuarios WHERE id = ?";
        $result = Database::fetchOne($sql, [$usuario->id]);

        if (!password_verify($passwordActual, $result['password'])) {
            $_SESSION['error'] = 'Contraseña actual incorrecta';
            redirect('cliente/cambiar-password');
            return;
        }

        // Validar que coincidan
        if ($passwordNueva !== $passwordConfirm) {
            $_SESSION['error'] = 'Las contraseñas no coinciden';
            redirect('cliente/cambiar-password');
            return;
        }

        // Validar requisitos
        $validacion = ValidationHelper::validatePassword($passwordNueva);
        if (!$validacion['valid']) {
            $_SESSION['error'] = implode('<br>', $validacion['errors']);
            redirect('cliente/cambiar-password');
            return;
        }

        // Cambiar
        if (!$usuario->cambiarPassword($passwordNueva)) {
            $_SESSION['error'] = 'Error al cambiar la contraseña';
            redirect('cliente/cambiar-password');
            return;
        }

        $_SESSION['success'] = 'Contraseña actualizada correctamente';
        redirect('cliente/perfil');
    }

    /**
     * Notificaciones
     */
    public function notificaciones(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Filtros
        $tipo = $_GET['tipo'] ?? null;

        $notificaciones = $this->getAllNotificaciones($usuario->id, $tipo);

        // Obtener todas las notificaciones para calcular conteos de filtros
        $todasNotificaciones = $this->getAllNotificaciones($usuario->id);

        require_once __DIR__ . '/../views/cliente/notificaciones.php';
    }

    /**
     * Marcar notificación como leída
     */
    public function marcarLeida(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('cliente/notificaciones');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('cliente/notificaciones');
            return;
        }

        $notificacionId = intval($_POST['notificacion_id'] ?? 0);

        if ($notificacionId) {
            try {
                $sql = "UPDATE notificaciones SET leido = TRUE WHERE id = ? AND usuario_id = ?";
                Database::execute($sql, [$notificacionId, $usuario->id]);
                $_SESSION['success'] = 'Notificación marcada como leída';
            } catch (Exception $e) {
                error_log("Error al marcar notificación como leída: " . $e->getMessage());
                $_SESSION['error'] = 'Error al marcar la notificación como leída';
            }
        }

        redirect('cliente/notificaciones');
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasLeidas(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('cliente/notificaciones');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('cliente/notificaciones');
            return;
        }

        try {
            $sql = "UPDATE notificaciones SET leido = TRUE WHERE usuario_id = ? AND leido = FALSE";
            $result = Database::execute($sql, [$usuario->id]);

            if ($result > 0) {
                $_SESSION['success'] = "Se marcaron {$result} notificaciones como leídas";
            } else {
                $_SESSION['info'] = 'No hay notificaciones nuevas para marcar';
            }
        } catch (Exception $e) {
            error_log("Error al marcar todas las notificaciones como leídas: " . $e->getMessage());
            $_SESSION['error'] = 'Error al marcar las notificaciones como leídas';
        }

        redirect('cliente/notificaciones');
    }

    /**
     * Solicitar cambio de estado de control
     */
    public function solicitarCambioEstado(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('cliente/controles');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('cliente/controles');
            return;
        }

        $controlNumero = sanitize($_POST['control_numero'] ?? '');
        $motivoSolicitud = $_POST['motivo_solicitud'] ?? '';
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $telefono = sanitize($_POST['telefono'] ?? '');

        // Validaciones
        if (empty($controlNumero)) {
            $_SESSION['error'] = 'Número de control no válido';
            redirect('cliente/controles');
            return;
        }

        if (empty($motivoSolicitud)) {
            $_SESSION['error'] = 'Debe seleccionar un motivo de solicitud';
            redirect('cliente/controles');
            return;
        }

        if (empty($descripcion)) {
            $_SESSION['error'] = 'Debe proporcionar una descripción detallada';
            redirect('cliente/controles');
            return;
        }

        // Verificar que el control pertenezca al usuario
        $sql = "SELECT c.id, au.id as apartamento_usuario_id
                FROM controles_estacionamiento c
                JOIN apartamento_usuario au ON au.id = c.apartamento_usuario_id
                WHERE c.numero_control_completo = ? AND au.usuario_id = ? AND au.activo = TRUE";

        $controlData = Database::fetchOne($sql, [$controlNumero, $usuario->id]);

        if (!$controlData) {
            $_SESSION['error'] = 'El control especificado no le pertenece o no existe';
            redirect('cliente/controles');
            return;
        }

        // Crear la solicitud
        try {
            $sql = "INSERT INTO solicitudes_cambios (
                        apartamento_usuario_id, tipo_solicitud, control_id,
                        motivo, estado, fecha_solicitud
                    ) VALUES (?, ?, ?, ?, 'pendiente', NOW())";

            $params = [
                $controlData['apartamento_usuario_id'],
                'cambio_estado_control', // Tipo específico para solicitudes de cambio de estado
                $controlData['id'],
                $descripcion . "\n\nMotivo: " . $motivoSolicitud . ($telefono ? "\nTeléfono de contacto: " . $telefono : '')
            ];

            Database::execute($sql, $params);

            writeLog("Cliente {$usuario->email} solicitó cambio de estado para control {$controlNumero}", 'info');

            $_SESSION['success'] = 'Solicitud de cambio de estado enviada correctamente. Será revisada por el administrador.';
            redirect('cliente/controles');

        } catch (Exception $e) {
            writeLog("Error al crear solicitud de cambio de estado: " . $e->getMessage(), 'error');
            $_SESSION['error'] = 'Error al enviar la solicitud. Intente nuevamente';
            redirect('cliente/controles');
        }
    }

    // ==================== HELPERS ====================

    /**
     * Obtener controles del usuario
     */
    private function getControlesUsuario(int $usuarioId): array
    {
        $sql = "SELECT c.*, a.bloque, a.numero_apartamento
                FROM controles_estacionamiento c
                JOIN apartamento_usuario au ON au.id = c.apartamento_usuario_id
                JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE au.usuario_id = ? AND au.activo = TRUE
                ORDER BY c.posicion_numero, c.receptor";

        return Database::fetchAll($sql, [$usuarioId]);
    }

    /**
     * Subir comprobante de pago
     */
    private function uploadComprobante(array $file, int $usuarioId): ?string
    {
        $uploadDir = __DIR__ . '/../../uploads/comprobantes/';

        // Crear directorio si no existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nombreArchivo = 'comp_' . $usuarioId . '_' . time() . '.' . $extension;
        $rutaDestino = $uploadDir . $nombreArchivo;

        if (move_uploaded_file($file['tmp_name'], $rutaDestino)) {
            return 'uploads/comprobantes/' . $nombreArchivo;
        }

        return null;
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

    /**
     * Obtener notificaciones no leídas
     */
    private function getNotificacionesNoLeidas(int $usuarioId): array
    {
        try {
            $sql = "SELECT * FROM notificaciones
                    WHERE usuario_id = ? AND leido = FALSE
                    ORDER BY fecha_creacion DESC
                    LIMIT 5";

            return Database::fetchAll($sql, [$usuarioId]);
        } catch (Exception $e) {
            // Si hay error con la tabla, devolver array vacío
            error_log("Error en getNotificacionesNoLeidas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todas las notificaciones
     */
    private function getAllNotificaciones(int $usuarioId, ?string $tipo = null): array
    {
        try {
            $sql = "SELECT * FROM notificaciones WHERE usuario_id = ?";
            $params = [$usuarioId];

            if ($tipo) {
                // Mapear tipos de filtro a tipos de base de datos
                $tipoMapping = [
                    'pago' => ['pago_aprobado', 'comprobante_rechazado'],
                    'mensualidad' => ['mensualidad_generada', 'mensualidad_vencida', 'alerta_3_meses'],
                    'control' => ['control_asignado', 'control_bloqueado'],
                    'sistema' => ['sistema', 'bloqueo', 'morosidad', 'bienvenida']
                ];

                if (isset($tipoMapping[$tipo])) {
                    $tipos = $tipoMapping[$tipo];
                    $placeholders = str_repeat('?,', count($tipos) - 1) . '?';
                    $sql .= " AND tipo IN ($placeholders)";
                    $params = array_merge($params, $tipos);
                } else {
                    // Si no hay mapping específico, buscar por tipo exacto
                    $sql .= " AND tipo = ?";
                    $params[] = $tipo;
                }
            }

            $sql .= " ORDER BY fecha_creacion DESC";

            return Database::fetchAll($sql, $params);
        } catch (Exception $e) {
            // Si hay error con la tabla, devolver array vacío
            error_log("Error en getAllNotificaciones: " . $e->getMessage());
            return [];
        }
    }
}
