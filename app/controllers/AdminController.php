<?php
/**
 * AdminController - Funcionalidades de administración
 *
 * Gestión completa: usuarios, apartamentos, controles, configuración, tarifas
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Apartamento.php';
require_once __DIR__ . '/../models/Control.php';
require_once __DIR__ . '/../models/Mensualidad.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/SolicitudCambio.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/MailHelper.php';

class AdminController
{
    /**
     * Verificar que el usuario esté autenticado como administrador
     */
    private function checkAuth(): ?Usuario
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
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
     * Dashboard del administrador
     */
    public function dashboard(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Estadísticas generales
        $estadisticas = [
            'usuarios' => $this->getEstadisticasUsuarios(),
            'pagos' => $this->getEstadisticasPagos(),
            'controles' => Control::getEstadisticas(),
            'apartamentos' => Apartamento::getEstadisticas(),
            'morosidad' => $this->getEstadisticasMorosidad()
        ];

        // Actividad reciente
        $actividadReciente = $this->getActividadReciente(20);

        require_once __DIR__ . '/../views/admin/dashboard.php';
    }

    // ==================== GESTIÓN DE USUARIOS ====================

    /**
     * Lista de usuarios
     */
    public function usuarios(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $filtros = [
            'rol' => $_GET['rol'] ?? null,
            'activo' => $_GET['activo'] ?? null
        ];

        $usuarios = Usuario::getAll($filtros);

        // Obtener controles y apartamento para cada usuario (todos los roles pueden tener)
        foreach ($usuarios as $user) {
            // Obtener controles del usuario
            $sql = "SELECT ce.id, ce.numero_control_completo as numero_control,
                           ce.estado, ce.fecha_asignacion,
                           CASE WHEN ce.estado = 'activo' THEN 1 ELSE 0 END as activo
                    FROM apartamento_usuario au
                    LEFT JOIN controles_estacionamiento ce ON ce.apartamento_usuario_id = au.id
                    WHERE au.usuario_id = ? AND au.activo = 1
                    ORDER BY ce.numero_control_completo";
            $user->controles = Database::fetchAll($sql, [$user->id]);

            // Obtener información del apartamento
            $sql = "SELECT a.bloque, a.escalera, a.piso, a.numero_apartamento
                    FROM apartamento_usuario au
                    JOIN apartamentos a ON a.id = au.apartamento_id
                    WHERE au.usuario_id = ? AND au.activo = 1
                    LIMIT 1";
            $apto = Database::fetchOne($sql, [$user->id]);
            if ($apto) {
                $user->apartamento = "{$apto['bloque']}-{$apto['escalera']}-{$apto['piso']}-{$apto['numero_apartamento']}";
            }
        }

        // Estadísticas
        $estadisticas = $this->getEstadisticasUsuarios();

        // Agregar estadísticas adicionales
        $estadisticas['inactivos'] = $estadisticas['total'] - $estadisticas['activos'];

        require_once __DIR__ . '/../views/admin/usuarios/index.php';
    }

    /**
     * Crear nuevo usuario
     */
    public function crearUsuario(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        require_once __DIR__ . '/../views/admin/usuarios/crear.php';
    }

    /**
     * Procesar creación de usuario
     */
    public function processCrearUsuario(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/crear-usuario');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/crear-usuario');
            return;
        }

        // Procesar cédula desde campos separados
        $cedulaTipo = trim($_POST['cedula_tipo'] ?? '');
        $cedulaNumero = trim(sanitize($_POST['cedula_numero'] ?? ''));
        $cedula = null;

        if (!empty($cedulaTipo) && !empty($cedulaNumero)) {
            // Validar que el número solo contenga dígitos
            if (!preg_match('/^\d{6,8}$/', $cedulaNumero)) {
                $_SESSION['error'] = 'El número de cédula debe contener entre 6 y 8 dígitos';
                redirect('admin/usuarios/crear');
                return;
            }
            $cedula = $cedulaTipo . '-' . $cedulaNumero;
        } elseif (!empty($cedulaTipo) || !empty($cedulaNumero)) {
            $_SESSION['error'] = 'Debe completar tanto el tipo como el número de cédula';
            redirect('admin/usuarios/crear');
            return;
        }

        $data = [
            'nombre_completo' => sanitize($_POST['nombre_completo'] ?? ''),
            'cedula' => $cedula,
            'email' => sanitize($_POST['email'] ?? ''),
            'telefono' => sanitize($_POST['telefono'] ?? ''),
            'rol' => $_POST['rol'] ?? 'cliente',
            'password' => $_POST['password'] ?? '',
            'activo' => intval($_POST['activo'] ?? 1)
        ];

        // Validaciones
        if (empty($data['nombre_completo']) || empty($data['email']) || empty($data['password'])) {
            $_SESSION['error'] = 'Nombre, email y contraseña son obligatorios';
            redirect('admin/crearUsuario');
            return;
        }

        if (!ValidationHelper::validateEmail($data['email'])) {
            $_SESSION['error'] = 'Email inválido';
            redirect('admin/crearUsuario');
            return;
        }

        // Verificar si el email ya existe
        if (Usuario::findByEmail($data['email'])) {
            $_SESSION['error'] = 'El email ya está registrado';
            redirect('admin/crearUsuario');
            return;
        }

        // Hash de la contraseña
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['primer_acceso'] = isset($_POST['cambiar_password_siguiente']) ? 1 : 0;
        $data['password_temporal'] = isset($_POST['cambiar_password_siguiente']) ? 1 : 0;
        $data['perfil_completo'] = 1;

        try {
            $usuarioId = Usuario::create($data);

            writeLog("Usuario creado por admin {$usuario->email}: {$data['email']}", 'info');

            $_SESSION['success'] = 'Usuario creado correctamente';
            redirect('admin/usuarios');

        } catch (Exception $e) {
            writeLog("Error al crear usuario: " . $e->getMessage(), 'error');
            $_SESSION['error'] = 'Error al crear el usuario: ' . $e->getMessage();
            redirect('admin/crearUsuario');
        }
    }

    /**
     * Editar usuario
     */
    public function editarUsuario(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        $usuarioId = intval($_GET['id'] ?? 0);

        if (!$usuarioId) {
            redirect('admin/usuarios');
            return;
        }

        $usuario = Usuario::findById($usuarioId);

        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            redirect('admin/usuarios');
            return;
        }

        // Obtener apartamento y controles (todos los roles pueden tener)
        $apartamento = null;
        $controles = [];
        
        $sql = "SELECT a.*, au.cantidad_controles
                FROM apartamento_usuario au
                JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE au.usuario_id = ? AND au.activo = 1
                LIMIT 1";
        $apartamento = Database::fetchOne($sql, [$usuarioId]);

        // Obtener controles asignados
        $sql = "SELECT ce.id, ce.numero_control_completo, ce.estado, ce.fecha_asignacion
                FROM apartamento_usuario au
                LEFT JOIN controles_estacionamiento ce ON ce.apartamento_usuario_id = au.id
                WHERE au.usuario_id = ? AND au.activo = 1
                ORDER BY ce.numero_control_completo";
        $controles = Database::fetchAll($sql, [$usuarioId]);

        // Filtrar controles válidos (que no sean NULL)
        $controles = array_filter($controles, function($c) {
            return !empty($c['numero_control_completo']);
        });

        // Obtener controles disponibles para asignar (solo para clientes)
        $controlesDisponibles = [];
        if ($usuario->rol === 'cliente' && $apartamento) {
            $controlesDisponibles = Control::getVacios();
        }

        require_once __DIR__ . '/../views/admin/usuarios/editar.php';
    }

    /**
     * Procesar edición de usuario
     */
    public function processEditarUsuario(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/usuarios');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/usuarios');
            return;
        }

        $usuarioId = intval($_POST['usuario_id'] ?? 0);
        $usuario = Usuario::findById($usuarioId);

        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            redirect('admin/usuarios');
            return;
        }

        // Procesar cédula desde campos separados
        $cedulaTipo = trim($_POST['cedula_tipo'] ?? '');
        $cedulaNumero = trim(sanitize($_POST['cedula_numero'] ?? ''));
        $cedula = null;

        if (!empty($cedulaTipo) && !empty($cedulaNumero)) {
            // Validar que el número solo contenga dígitos
            if (!preg_match('/^\d{6,8}$/', $cedulaNumero)) {
                $_SESSION['error'] = 'El número de cédula debe contener entre 6 y 8 dígitos';
                redirect('admin/usuarios/editar?id=' . $usuarioId);
                return;
            }
            $cedula = $cedulaTipo . '-' . $cedulaNumero;
        } elseif (!empty($cedulaTipo) || !empty($cedulaNumero)) {
            $_SESSION['error'] = 'Debe completar tanto el tipo como el número de cédula';
            redirect('admin/usuarios/editar?id=' . $usuarioId);
            return;
        }

        $data = [
            'nombre_completo' => sanitize($_POST['nombre_completo'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'cedula' => $cedula,
            'telefono' => sanitize($_POST['telefono'] ?? ''),
            'direccion' => sanitize($_POST['direccion'] ?? ''),
            'rol' => $_POST['rol'] ?? 'cliente',
            'exonerado' => isset($_POST['exonerado']) ? 1 : 0
        ];

        // Validar email si cambió
        if ($data['email'] !== $usuario->email) {
            if (!ValidationHelper::validateEmail($data['email'])) {
                $_SESSION['error'] = 'Email inválido';
                redirect('admin/editar-usuario?id=' . $usuarioId);
                return;
            }

            if (Usuario::findByEmail($data['email'])) {
                $_SESSION['error'] = 'El email ya está registrado';
                redirect('admin/editar-usuario?id=' . $usuarioId);
                return;
            }
        }

        if ($usuario->update($data)) {
            $_SESSION['success'] = 'Usuario actualizado correctamente';
            writeLog("Usuario ID $usuarioId actualizado por admin {$admin->email}", 'info');
        } else {
            $_SESSION['error'] = 'Error al actualizar el usuario';
        }

        redirect('admin/usuarios');
    }

    /**
     * Activar/desactivar usuario
     */
    public function toggleUsuario(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/usuarios');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/usuarios');
            return;
        }

        $usuarioId = intval($_POST['usuario_id'] ?? 0);
        $usuario = Usuario::findById($usuarioId);

        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            redirect('admin/usuarios');
            return;
        }

        // No puede desactivarse a sí mismo
        if ($usuario->id === $admin->id) {
            $_SESSION['error'] = 'No puedes desactivar tu propia cuenta';
            redirect('admin/usuarios');
            return;
        }

        $nuevoEstado = !$usuario->activo;

        if ($usuario->update(['activo' => $nuevoEstado])) {
            $accion = $nuevoEstado ? 'activado' : 'desactivado';
            $_SESSION['success'] = "Usuario $accion correctamente";
            writeLog("Usuario ID $usuarioId $accion por admin {$admin->email}", 'info');
        } else {
            $_SESSION['error'] = 'Error al cambiar el estado';
        }

        redirect('admin/usuarios');
    }

    /**
     * Cambiar rol de usuario
     */
    public function cambiarRol(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // Debug: Log all POST data
        writeLog("cambiarRol - POST data: " . json_encode($_POST), 'debug');
        writeLog("cambiarRol - Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? ''), 'debug');

        // Leer datos JSON desde el body de la petición
        $postData = $_POST;
        if (empty($postData) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $input = file_get_contents('php://input');
            writeLog("cambiarRol - Raw JSON input: " . $input, 'debug');
            $postData = json_decode($input, true);
            writeLog("cambiarRol - Decoded JSON: " . json_encode($postData), 'debug');
        }

        // Validar CSRF
        $csrfToken = $postData['csrf_token'] ?? '';
        if (!ValidationHelper::validateCSRFToken($csrfToken)) {
            writeLog("cambiarRol - CSRF validation failed. Token: $csrfToken", 'error');
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
            exit;
        }

        $usuarioId = intval($postData['usuario_id'] ?? 0);
        $nuevoRol = $postData['nuevo_rol'] ?? '';

        writeLog("cambiarRol - usuarioId: $usuarioId, nuevoRol: $nuevoRol", 'debug');

        $usuario = Usuario::findById($usuarioId);

        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            exit;
        }

        // Validar rol
        $rolesPermitidos = ['cliente', 'operador', 'consultor', 'administrador'];
        if (!in_array($nuevoRol, $rolesPermitidos)) {
            writeLog("cambiarRol - Rol inválido: $nuevoRol", 'error');
            echo json_encode(['success' => false, 'message' => 'Rol inválido']);
            exit;
        }

        // No puede cambiar su propio rol
        if ($usuario->id === $admin->id) {
            echo json_encode(['success' => false, 'message' => 'No puedes cambiar tu propio rol']);
            exit;
        }

        writeLog("cambiarRol - Intentando actualizar usuario ID $usuarioId con rol $nuevoRol", 'debug');

        if ($usuario->update(['rol' => $nuevoRol])) {
            writeLog("Rol de usuario ID $usuarioId cambiado de {$usuario->rol} a $nuevoRol por admin {$admin->email}", 'info');
            echo json_encode(['success' => true, 'message' => 'Rol actualizado correctamente']);
        } else {
            writeLog("Error al actualizar rol del usuario ID $usuarioId", 'error');
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el rol']);
        }
        exit;
    }

    /**
     * Resetear contraseña de usuario
     */
    public function resetearPassword(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/usuarios');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/usuarios');
            return;
        }

        $usuarioId = intval($_POST['usuario_id'] ?? 0);
        $usuario = Usuario::findById($usuarioId);

        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            redirect('admin/usuarios');
            return;
        }

        // Generar nueva contraseña temporal
        $passwordTemporal = $this->generarPasswordTemporal();

        $usuario->cambiarPassword($passwordTemporal);
        $usuario->update([
            'primer_acceso' => true,
            'password_temporal' => true
        ]);

        // Enviar email
        MailHelper::sendWelcomeCredentials(
            $usuario->email,
            $usuario->nombre_completo,
            $passwordTemporal
        );

        $_SESSION['success'] = 'Contraseña reseteada. Se envió un email al usuario';
        writeLog("Password reseteado para usuario ID $usuarioId por admin {$admin->email}", 'info');

        redirect('admin/usuarios');
    }

    /**
     * Eliminar usuario
     */
    public function eliminarUsuario(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // Leer datos JSON
        $postData = $_POST;
        if (empty($postData) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $postData = json_decode($input, true);
        }

        // Validar CSRF
        $csrfToken = $postData['csrf_token'] ?? '';
        if (!ValidationHelper::validateCSRFToken($csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
            exit;
        }

        $usuarioId = intval($postData['usuario_id'] ?? 0);
        $usuario = Usuario::findById($usuarioId);

        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            exit;
        }

        // No puede eliminarse a sí mismo
        if ($usuario->id === $admin->id) {
            echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta']);
            exit;
        }

        // Verificar si tiene apartamento asignado (cualquier rol puede tener apartamento)
        $sql = "SELECT COUNT(*) as total FROM apartamento_usuario WHERE usuario_id = ? AND activo = 1";
        $result = Database::fetchOne($sql, [$usuarioId]);
        if ($result['total'] > 0) {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar. El usuario tiene un apartamento asignado. Primero debe desasignarlo.']);
            exit;
        }

        try {
            // Eliminar usuario (soft delete)
            $sql = "DELETE FROM usuarios WHERE id = ?";
            Database::execute($sql, [$usuarioId]);

            writeLog("Usuario ID $usuarioId ({$usuario->email}) eliminado por admin {$admin->email}", 'info');
            echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        } catch (Exception $e) {
            writeLog("Error al eliminar usuario ID $usuarioId: " . $e->getMessage(), 'error');
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el usuario']);
        }
        exit;
    }

    // ==================== GESTIÓN DE APARTAMENTOS ====================

    /**
     * Lista de apartamentos
     */
    public function apartamentos(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Obtener parámetros de filtros y paginación
        $busqueda = sanitize($_GET['busqueda'] ?? '');
        $bloque = sanitize($_GET['bloque'] ?? '');
        $escalera = sanitize($_GET['escalera'] ?? '');
        $porPagina = intval($_GET['por_pagina'] ?? 20);
        $paginaActual = intval($_GET['pagina'] ?? 1);

        // Validar por_pagina
        $opcionesPorPagina = [10, 20, 30, 40, 50];
        if (!in_array($porPagina, $opcionesPorPagina)) {
            $porPagina = 20;
        }

        // Validar página actual
        if ($paginaActual < 1) {
            $paginaActual = 1;
        }

        // Preparar filtros
        $filtros = [];
        if (!empty($busqueda)) {
            $filtros['busqueda'] = $busqueda;
        }
        if (!empty($bloque)) {
            $filtros['bloque'] = $bloque;
        }
        if (!empty($escalera)) {
            $filtros['escalera'] = $escalera;
        }

        // Calcular offset
        $offset = ($paginaActual - 1) * $porPagina;

        // Obtener apartamentos paginados
        $apartamentos = Apartamento::getAllWithResidentesPaginado($filtros, $porPagina, $offset);

        // Obtener total de apartamentos con filtros
        $totalApartamentos = Apartamento::countWithFilters($filtros);

        // Calcular total de páginas
        $totalPaginas = ceil($totalApartamentos / $porPagina);

        // Obtener listas para filtros
        $bloques = Apartamento::getBloques();
        $escaleras = Apartamento::getEscaleras();

        // Pasar variables a la vista
        require_once __DIR__ . '/../views/admin/apartamentos/index.php';
    }

    /**
     * Crear apartamento
     */
    public function crearApartamento(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        require_once __DIR__ . '/../views/admin/apartamentos/crear.php';
    }

    /**
     * Procesar creación de apartamento
     */
    public function processCrearApartamento(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/crear-apartamento');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/crear-apartamento');
            return;
        }

        $data = [
            'bloque' => sanitize($_POST['bloque'] ?? ''),
            'escalera' => sanitize($_POST['escalera'] ?? ''),
            'piso' => intval($_POST['piso'] ?? 0),
            'numero_apartamento' => sanitize($_POST['numero_apartamento'] ?? ''),
            'activo' => true
        ];

        // Validaciones
        if (empty($data['bloque']) || empty($data['escalera']) || empty($data['numero_apartamento'])) {
            $_SESSION['error'] = 'Todos los campos son obligatorios';
            redirect('admin/crear-apartamento');
            return;
        }

        // Verificar si ya existe
        $existente = Apartamento::findByDatos(
            $data['bloque'],
            $data['escalera'],
            $data['piso'],
            $data['numero_apartamento']
        );

        if ($existente) {
            $_SESSION['error'] = 'El apartamento ya existe';
            redirect('admin/crear-apartamento');
            return;
        }

        try {
            $apartamentoId = Apartamento::create($data);

            $_SESSION['success'] = 'Apartamento creado correctamente';
            writeLog("Apartamento creado por admin {$admin->email}: {$data['bloque']}-{$data['numero_apartamento']}", 'info');

            redirect('admin/apartamentos');

        } catch (Exception $e) {
            writeLog("Error al crear apartamento: " . $e->getMessage(), 'error');
            $_SESSION['error'] = 'Error al crear el apartamento';
            redirect('admin/crear-apartamento');
        }
    }

    /**
     * Editar apartamento
     */
    public function editarApartamento(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        $apartamentoId = intval($_GET['id'] ?? 0);

        if (!$apartamentoId) {
            redirect('admin/apartamentos');
            return;
        }

        $apartamento = Apartamento::findById($apartamentoId);

        if (!$apartamento) {
            $_SESSION['error'] = 'Apartamento no encontrado';
            redirect('admin/apartamentos');
            return;
        }

        require_once __DIR__ . '/../views/admin/apartamentos/editar.php';
    }

    /**
     * Procesar edición de apartamento
     */
    public function processEditarApartamento(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/apartamentos');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/apartamentos');
            return;
        }

        $apartamentoId = intval($_POST['apartamento_id'] ?? 0);
        $apartamento = Apartamento::findById($apartamentoId);

        if (!$apartamento) {
            $_SESSION['error'] = 'Apartamento no encontrado';
            redirect('admin/apartamentos');
            return;
        }

        $data = [
            'bloque' => sanitize($_POST['bloque'] ?? ''),
            'escalera' => sanitize($_POST['escalera'] ?? ''),
            'piso' => intval($_POST['piso'] ?? 0),
            'numero_apartamento' => sanitize($_POST['numero_apartamento'] ?? ''),
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        // Validaciones
        if (empty($data['bloque']) || empty($data['escalera']) || empty($data['numero_apartamento'])) {
            $_SESSION['error'] = 'Todos los campos son obligatorios';
            redirect('admin/editarApartamento?id=' . $apartamentoId);
            return;
        }

        if ($apartamento->update($data)) {
            $_SESSION['success'] = 'Apartamento actualizado correctamente';
            writeLog("Apartamento ID $apartamentoId actualizado por admin {$admin->email}", 'info');
        } else {
            $_SESSION['error'] = 'Error al actualizar el apartamento';
        }

        redirect('admin/apartamentos');
    }

    /**
     * Asignar residente a apartamento
     */
    public function asignarResidente(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $apartamentoId = intval($_GET['id'] ?? 0);

        if (!$apartamentoId) {
            redirect('admin/apartamentos');
            return;
        }

        $apartamento = Apartamento::findById($apartamentoId);

        if (!$apartamento) {
            $_SESSION['error'] = 'Apartamento no encontrado';
            redirect('admin/apartamentos');
            return;
        }

        // Obtener clientes disponibles
        $clientes = Usuario::getAll(['rol' => 'cliente', 'activo' => true]);

        require_once __DIR__ . '/../views/admin/apartamentos/asignar_residente.php';
    }

    /**
     * Procesar asignación de residente
     */
    public function processAsignarResidente(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/apartamentos');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/apartamentos');
            return;
        }

        $apartamentoId = intval($_POST['apartamento_id'] ?? 0);
        $usuarioId = intval($_POST['usuario_id'] ?? 0);
        $cantidadControles = intval($_POST['cantidad_controles'] ?? 0);

        $apartamento = Apartamento::findById($apartamentoId);
        $usuario = Usuario::findById($usuarioId);

        if (!$apartamento || !$usuario) {
            $_SESSION['error'] = 'Datos inválidos';
            redirect('admin/apartamentos');
            return;
        }

        // Asignar
        $apartamento->asignarUsuario($usuarioId, $cantidadControles);

        $_SESSION['success'] = 'Residente asignado correctamente';
        writeLog("Residente asignado a apartamento {$apartamento->getIdentificador()} por admin {$admin->email}", 'info');

        redirect('admin/apartamentos');
    }

    /**
     * Editar residente de apartamento
     */
    public function editarResidente(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        $apartamentoId = intval($_GET['id'] ?? 0);

        if (!$apartamentoId) {
            redirect('admin/apartamentos');
            return;
        }

        $apartamento = Apartamento::findById($apartamentoId);

        if (!$apartamento) {
            $_SESSION['error'] = 'Apartamento no encontrado';
            redirect('admin/apartamentos');
            return;
        }

        // Obtener la asignación actual
        $sql = "SELECT au.*, u.nombre_completo, u.email
                FROM apartamento_usuario au
                JOIN usuarios u ON u.id = au.usuario_id
                WHERE au.apartamento_id = ? AND au.activo = TRUE
                LIMIT 1";

        $asignacion = Database::fetchOne($sql, [$apartamentoId]);

        if (!$asignacion) {
            $_SESSION['error'] = 'No hay residente asignado a este apartamento';
            redirect('admin/apartamentos');
            return;
        }

        // Obtener todos los clientes para cambio
        $clientes = Usuario::getAll(['rol' => 'cliente', 'activo' => true]);

        require_once __DIR__ . '/../views/admin/apartamentos/editar_residente.php';
    }

    /**
     * Procesar edición de residente
     */
    public function processEditarResidente(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/apartamentos');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/apartamentos');
            return;
        }

        $asignacionId = intval($_POST['asignacion_id'] ?? 0);
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'remover') {
            // Desactivar la asignación actual
            $sql = "UPDATE apartamento_usuario SET activo = FALSE WHERE id = ?";
            Database::execute($sql, [$asignacionId]);

            $_SESSION['success'] = 'Residente removido correctamente';
            writeLog("Residente removido de apartamento por admin {$admin->email}", 'info');
        } else {
            // Cambiar residente o actualizar controles
            $nuevoUsuarioId = intval($_POST['usuario_id'] ?? 0);
            $cantidadControles = intval($_POST['cantidad_controles'] ?? 0);

            $sql = "UPDATE apartamento_usuario
                    SET usuario_id = ?, cantidad_controles = ?
                    WHERE id = ?";

            Database::execute($sql, [$nuevoUsuarioId, $cantidadControles, $asignacionId]);

            $_SESSION['success'] = 'Residente actualizado correctamente';
            writeLog("Residente actualizado en apartamento por admin {$admin->email}", 'info');
        }

        redirect('admin/apartamentos');
    }

    // ==================== GESTIÓN DE CONTROLES ====================

    /**
     * Mapa de controles
     */
    public function controles(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $mapa = Control::getMapaControles();
        $estadisticas = Control::getEstadisticas();

        require_once __DIR__ . '/../views/admin/controles/mapa.php';
    }


    /**
     * Remover control de un usuario
     */
    public function removerControlUsuario(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/usuarios');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/usuarios');
            return;
        }

        $controlId = intval($_POST['control_id'] ?? 0);
        $usuarioId = intval($_POST['usuario_id'] ?? 0);
        $motivo = sanitize($_POST['motivo'] ?? 'Removido por administrador');

        $control = Control::findById($controlId);
        $usuario = Usuario::findById($usuarioId);

        if (!$control || !$usuario) {
            $_SESSION['error'] = 'Datos inválidos';
            redirect('admin/gestionar-controles-usuario?id=' . $usuarioId);
            return;
        }

        if ($control->desasignar($motivo, $admin->id)) {
            $_SESSION['success'] = 'Control removido correctamente';
            writeLog("Control {$control->numero_control_completo} removido del usuario {$usuario->email} por admin {$admin->email}", 'info');
        } else {
            $_SESSION['error'] = 'Error al remover el control';
        }

        redirect('admin/gestionar-controles-usuario?id=' . $usuarioId);
    }

    /**
     * Asignar control a un usuario
     */
    public function asignarControlUsuario(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/usuarios');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/usuarios');
            return;
        }

        $controlId = intval($_POST['control_id'] ?? 0);
        $usuarioId = intval($_POST['usuario_id'] ?? 0);

        $control = Control::findById($controlId);
        $usuario = Usuario::findById($usuarioId);

        if (!$control || !$usuario) {
            $_SESSION['error'] = 'Datos inválidos';
            redirect('admin/gestionar-controles-usuario?id=' . $usuarioId);
            return;
        }

        // Obtener apartamento_usuario_id del usuario
        $sql = "SELECT id FROM apartamento_usuario WHERE usuario_id = ? AND activo = 1 LIMIT 1";
        $apartamentoUsuario = Database::fetchOne($sql, [$usuarioId]);

        if (!$apartamentoUsuario) {
            $_SESSION['error'] = 'El usuario no tiene un apartamento asignado';
            redirect('admin/gestionar-controles-usuario?id=' . $usuarioId);
            return;
        }

        if ($control->asignar($apartamentoUsuario['id'], $admin->id)) {
            $_SESSION['success'] = 'Control asignado correctamente';
            writeLog("Control {$control->numero_control_completo} asignado al usuario {$usuario->email} por admin {$admin->email}", 'info');
        } else {
            $_SESSION['error'] = 'Error al asignar el control';
        }

        redirect('admin/gestionar-controles-usuario?id=' . $usuarioId);
    }

    /**
     * Asignar control
     */
    public function asignarControl(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $controlId = intval($_GET['id'] ?? 0);

        if (!$controlId) {
            redirect('admin/controles');
            return;
        }

        $control = Control::findById($controlId);

        if (!$control || $control->estado !== 'vacio') {
            $_SESSION['error'] = 'Control no disponible';
            redirect('admin/controles');
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

        require_once __DIR__ . '/../views/admin/controles/asignar.php';
    }

    /**
     * Procesar asignación de control
     */
    public function processAsignarControl(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/controles');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/controles');
            return;
        }

        $controlId = intval($_POST['control_id'] ?? 0);
        $apartamentoUsuarioId = intval($_POST['apartamento_usuario_id'] ?? 0);

        $control = Control::findById($controlId);

        if (!$control) {
            $_SESSION['error'] = 'Control no encontrado';
            redirect('admin/controles');
            return;
        }

        if ($control->asignar($apartamentoUsuarioId, $admin->id)) {
            $_SESSION['success'] = 'Control asignado correctamente';
        } else {
            $_SESSION['error'] = 'Error al asignar el control';
        }

        redirect('admin/controles');
    }

    /**
     * Cambiar estado de un control
     */
    public function cambiarEstadoControl(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            } else {
                redirect('admin/controles');
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
                redirect('admin/controles');
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
                redirect('admin/controles');
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
                redirect('admin/controles');
            }
            return;
        }

        $success = false;
        $successMessage = '';
        $errorMessage = '';

        // Cambiar estado según el tipo
        if ($nuevoEstado === 'vacio') {
            // Desasignar control
            if ($control->desasignar($motivo, $admin->id)) {
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
            if ($control->desbloquear($admin->id)) {
                $success = true;
                $successMessage = "Control {$control->numero_control_completo} desbloqueado correctamente";
            } else {
                $errorMessage = 'Error al desbloquear el control';
            }
        } else {
            // Cambiar a otro estado
            if ($control->cambiarEstado($nuevoEstado, $motivo, $admin->id)) {
                $success = true;
                $successMessage = "Estado del control {$control->numero_control_completo} actualizado correctamente";
            } else {
                $errorMessage = 'Error al cambiar el estado del control';
            }
        }

        writeLog("Admin {$admin->email} cambió estado del control {$control->numero_control_completo} a: $nuevoEstado", 'info');

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
            redirect('admin/controles');
        }
    }

    /**
     * Inicializar controles (crear 500 controles)
     */
    public function inicializarControles(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        try {
            $creados = Control::crearControlesIniciales();

            $_SESSION['success'] = "$creados controles creados correctamente";
            writeLog("Controles inicializados por admin {$admin->email}: $creados creados", 'info');

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al crear controles: ' . $e->getMessage();
            writeLog("Error al inicializar controles: " . $e->getMessage(), 'error');
        }

        redirect('admin/controles');
    }

    // ==================== CONFIGURACIÓN ====================

    /**
     * Configuración del sistema
     */
    public function configuracion(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Obtener configuración actual de tarifas
        $sql = "SELECT * FROM configuracion_tarifas WHERE activo = 1 ORDER BY fecha_vigencia_inicio DESC LIMIT 1";
        $configuracion = Database::fetchOne($sql);

        // Obtener última tasa BCV
        $sql = "SELECT * FROM tasa_cambio_bcv ORDER BY fecha_registro DESC LIMIT 1";
        $tasaBCV = Database::fetchOne($sql);

        // Obtener tareas CRON
        $sql = "SELECT * FROM configuracion_cron ORDER BY nombre_tarea";
        $tareasCron = Database::fetchAll($sql);

        // Obtener historial de tarifas
        require_once __DIR__ . '/../models/ConfiguracionTarifa.php';
        $historialTarifas = ConfiguracionTarifa::getHistorialCambios(5);

        // Preparar array de configuración para la vista
        $config = [
            'sistema_nombre' => 'Sistema de Control de Pagos - Estacionamiento',
            'monto_mensualidad' => $configuracion['monto_mensual_usd'] ?? 1.00,
            'meses_bloqueo' => $configuracion['meses_bloqueo'] ?? 2,
            'tasa_bcv' => $tasaBCV['tasa_usd_bs'] ?? 36.50,
            'tasa_bcv_fecha' => $tasaBCV['fecha_registro'] ?? null,
            'tasa_bcv_fuente' => $tasaBCV['fuente'] ?? 'Manual',
            'email_notificaciones' => 'admin@estacionamiento.com',
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_user' => '',
            'historial_tarifas' => $historialTarifas
        ];

        require_once __DIR__ . '/../views/admin/configuracion.php';
    }

    /**
     * Procesar actualización de configuración general
     */
    public function processConfiguracion(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/configuracion');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/configuracion');
            return;
        }

        // Obtener datos del formulario
        $montoMensualidad = floatval($_POST['monto_mensualidad'] ?? 0);
        $mesesBloqueo = intval($_POST['meses_bloqueo'] ?? 2);

        // Validar monto mensualidad
        if ($montoMensualidad <= 0) {
            $_SESSION['error'] = 'El monto de mensualidad debe ser mayor a 0';
            redirect('admin/configuracion');
            return;
        }

        // Validar meses de bloqueo
        if ($mesesBloqueo < 1 || $mesesBloqueo > 12) {
            $_SESSION['error'] = 'Los meses de bloqueo deben estar entre 1 y 12';
            redirect('admin/configuracion');
            return;
        }

        try {
            // Desactivar configuración actual
            $sql = "UPDATE configuracion_tarifas SET activo = 0 WHERE activo = 1";
            Database::execute($sql);

            // Insertar nueva configuración
            $sql = "INSERT INTO configuracion_tarifas
                    (monto_mensual_usd, meses_bloqueo, fecha_vigencia_inicio, activo, creado_por, fecha_creacion)
                    VALUES (?, ?, NOW(), 1, ?, NOW())";

            Database::execute($sql, [$montoMensualidad, $mesesBloqueo, $admin->id]);

            $_SESSION['success'] = 'Configuración actualizada correctamente';
            writeLog("Configuración actualizada: Mensualidad $montoMensualidad USD, Bloqueo $mesesBloqueo meses - por admin {$admin->email}", 'info');

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al actualizar la configuración: ' . $e->getMessage();
            writeLog("Error al actualizar configuración: " . $e->getMessage(), 'error');
        }

        redirect('admin/configuracion');
    }

    /**
     * Procesar configuración SMTP
     */
    public function processSmtp(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/configuracion');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/configuracion');
            return;
        }

        // TODO: Implementar guardado de configuración SMTP en base de datos
        // Por ahora solo mostrar mensaje de éxito
        $_SESSION['success'] = 'Configuración SMTP guardada correctamente (funcionalidad pendiente de implementar)';
        writeLog("Configuración SMTP actualizada por admin {$admin->email}", 'info');

        redirect('admin/configuracion');
    }

    /**
     * Actualizar configuración
     */
    public function updateConfiguracion(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/configuracion');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/configuracion');
            return;
        }

        $accion = $_POST['accion'] ?? '';

        if ($accion === 'tarifa') {
            $this->updateTarifa($admin);
        } elseif ($accion === 'tasa_bcv') {
            $this->updateTasaBCV($admin);
        }

        redirect('admin/configuracion');
    }

    /**
     * Gestionar tarifas
     */
    public function tarifas(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        require_once __DIR__ . '/../models/ConfiguracionTarifa.php';

        $tarifas = ConfiguracionTarifa::getAll();
        $tarifaActual = ConfiguracionTarifa::getTarifaActual();

        require_once __DIR__ . '/../views/admin/tarifas.php';
    }

    /**
     * Crear nueva tarifa
     */
    public function crearTarifa(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/tarifas');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/tarifas');
            return;
        }

        $montoMensualUSD = floatval($_POST['monto_mensual_usd'] ?? 0);
        $fechaVigenciaInicio = $_POST['fecha_vigencia_inicio'] ?? date('Y-m-d');
        $fechaVigenciaFin = !empty($_POST['fecha_vigencia_fin']) ? $_POST['fecha_vigencia_fin'] : null;

        // Validaciones
        if ($montoMensualUSD <= 0) {
            $_SESSION['error'] = 'El monto mensual debe ser mayor a 0';
            redirect('admin/tarifas');
            return;
        }

        if (empty($fechaVigenciaInicio)) {
            $_SESSION['error'] = 'La fecha de vigencia es obligatoria';
            redirect('admin/tarifas');
            return;
        }

        try {
            require_once __DIR__ . '/../models/ConfiguracionTarifa.php';
            $nuevaTarifaId = ConfiguracionTarifa::crear($montoMensualUSD, $fechaVigenciaInicio, $admin->id, $fechaVigenciaFin);

            $_SESSION['success'] = 'Tarifa creada correctamente. Los pagos ahora usarán este nuevo monto.';
            writeLog("Nueva tarifa creada: $montoMensualUSD USD desde $fechaVigenciaInicio por admin {$admin->email}", 'info');

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al crear la tarifa: ' . $e->getMessage();
            writeLog("Error al crear tarifa: " . $e->getMessage(), 'error');
        }

        redirect('admin/tarifas');
    }

    /**
     * Desactivar tarifa
     */
    public function desactivarTarifa(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/tarifas');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/tarifas');
            return;
        }

        $tarifaId = intval($_POST['tarifa_id'] ?? 0);

        if (!$tarifaId) {
            $_SESSION['error'] = 'ID de tarifa inválido';
            redirect('admin/tarifas');
            return;
        }

        try {
            require_once __DIR__ . '/../models/ConfiguracionTarifa.php';
            $result = ConfiguracionTarifa::desactivar($tarifaId);

            if ($result) {
                $_SESSION['success'] = 'Tarifa desactivada correctamente';
                writeLog("Tarifa ID $tarifaId desactivada por admin {$admin->email}", 'info');
            } else {
                $_SESSION['error'] = 'Error al desactivar la tarifa';
            }

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al desactivar la tarifa: ' . $e->getMessage();
            writeLog("Error al desactivar tarifa: " . $e->getMessage(), 'error');
        }

        redirect('admin/tarifas');
    }

    /**
     * Logs de actividad
     */
    public function logs(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $filtros = [
            'modulo' => $_GET['modulo'] ?? null,
            'fecha' => $_GET['fecha'] ?? null,
            'buscar' => $_GET['buscar'] ?? null
        ];

        $sql = "SELECT la.*,
                       u.nombre_completo as usuario_nombre,
                       u.email as usuario_email
                FROM logs_actividad la
                LEFT JOIN usuarios u ON u.id = la.usuario_id
                WHERE 1=1";
        $params = [];

        if ($filtros['modulo']) {
            $sql .= " AND la.modulo = ?";
            $params[] = $filtros['modulo'];
        }

        if ($filtros['fecha']) {
            $sql .= " AND DATE(la.fecha_hora) = ?";
            $params[] = $filtros['fecha'];
        }

        if ($filtros['buscar']) {
            $sql .= " AND (la.accion LIKE ? OR u.nombre_completo LIKE ? OR u.email LIKE ?)";
            $buscar = "%{$filtros['buscar']}%";
            $params[] = $buscar;
            $params[] = $buscar;
            $params[] = $buscar;
        }

        $sql .= " ORDER BY la.fecha_hora DESC LIMIT 500";

        $logs = Database::fetchAll($sql, $params);

        // Obtener módulos únicos para el filtro
        $modulos = Database::fetchAll("SELECT DISTINCT modulo FROM logs_actividad WHERE modulo IS NOT NULL ORDER BY modulo");

        require_once __DIR__ . '/../views/admin/logs.php';
    }

    /**
     * Exportar logs a CSV
     */
    public function exportLogs(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $filtros = [
            'modulo' => $_GET['modulo'] ?? null,
            'fecha' => $_GET['fecha'] ?? null,
            'buscar' => $_GET['buscar'] ?? null
        ];

        $sql = "SELECT la.*,
                       u.nombre_completo as usuario_nombre,
                       u.email as usuario_email
                FROM logs_actividad la
                LEFT JOIN usuarios u ON u.id = la.usuario_id
                WHERE 1=1";
        $params = [];

        if ($filtros['modulo']) {
            $sql .= " AND la.modulo = ?";
            $params[] = $filtros['modulo'];
        }

        if ($filtros['fecha']) {
            $sql .= " AND DATE(la.fecha_hora) = ?";
            $params[] = $filtros['fecha'];
        }

        if ($filtros['buscar']) {
            $sql .= " AND (la.accion LIKE ? OR u.nombre_completo LIKE ? OR u.email LIKE ?)";
            $buscar = "%{$filtros['buscar']}%";
            $params[] = $buscar;
            $params[] = $buscar;
            $params[] = $buscar;
        }

        $sql .= " ORDER BY la.fecha_hora DESC LIMIT 5000";

        $logs = Database::fetchAll($sql, $params);

        // Nombre del archivo
        $filename = 'logs_' . date('Y-m-d_His') . '.csv';

        // Encabezados HTTP para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Abrir output stream
        $output = fopen('php://output', 'w');

        // BOM para UTF-8 (para Excel)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Encabezados CSV
        fputcsv($output, [
            'ID',
            'Fecha/Hora',
            'Módulo',
            'Acción',
            'Tabla Afectada',
            'Registro ID',
            'Usuario',
            'Email',
            'IP'
        ]);

        // Datos
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['fecha_hora'],
                $log['modulo'] ?? '',
                $log['accion'] ?? '',
                $log['tabla_afectada'] ?? '',
                $log['registro_id'] ?? '',
                $log['usuario_nombre'] ?? 'Sistema',
                $log['usuario_email'] ?? '',
                $log['ip_address'] ?? ''
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Limpiar logs antiguos
     */
    public function limpiarLogs(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($input['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
            exit;
        }

        $dias = intval($input['dias'] ?? 30);

        if ($dias < 1 || $dias > 365) {
            echo json_encode(['success' => false, 'message' => 'Los días deben estar entre 1 y 365']);
            exit;
        }

        try {
            $fecha = date('Y-m-d', strtotime("-$dias days"));
            $sql = "DELETE FROM logs_actividad WHERE DATE(fecha_hora) < ?";
            $affected = Database::execute($sql, [$fecha]);

            writeLog("Logs anteriores a $fecha eliminados ($affected registros) por admin {$usuario->email}", 'info');

            echo json_encode([
                'success' => true,
                'message' => "Se eliminaron $affected registros de logs"
            ]);
        } catch (Exception $e) {
            writeLog("Error al limpiar logs: " . $e->getMessage(), 'error');
            echo json_encode([
                'success' => false,
                'message' => 'Error al limpiar logs: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Actualizar configuración de tareas CRON
     */
    public function actualizarTareaCron(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($input['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
            exit;
        }

        $tareaId = intval($input['tarea_id'] ?? 0);
        $activo = filter_var($input['activo'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $horaEjecucion = $input['hora_ejecucion'] ?? null;
        $diaMes = !empty($input['dia_mes']) ? intval($input['dia_mes']) : null;

        if ($tareaId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de tarea inválido']);
            exit;
        }

        // Validar hora
        if ($horaEjecucion && !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $horaEjecucion)) {
            echo json_encode(['success' => false, 'message' => 'Formato de hora inválido (use HH:MM)']);
            exit;
        }

        // Validar día del mes
        if ($diaMes !== null && ($diaMes < 1 || $diaMes > 31)) {
            echo json_encode(['success' => false, 'message' => 'Día del mes debe estar entre 1 y 31']);
            exit;
        }

        try {
            $sql = "UPDATE configuracion_cron SET activo = ?, hora_ejecucion = ?, dia_mes = ? WHERE id = ?";
            Database::execute($sql, [$activo, $horaEjecucion . ':00', $diaMes, $tareaId]);

            // Obtener nombre de la tarea
            $tarea = Database::fetchOne("SELECT nombre_tarea FROM configuracion_cron WHERE id = ?", [$tareaId]);
            $estadoTexto = $activo ? 'activada' : 'desactivada';

            writeLog("Tarea CRON '{$tarea['nombre_tarea']}' $estadoTexto (hora: $horaEjecucion) por admin {$admin->email}", 'info');

            echo json_encode([
                'success' => true,
                'message' => 'Tarea CRON actualizada correctamente'
            ]);
        } catch (Exception $e) {
            writeLog("Error al actualizar tarea CRON: " . $e->getMessage(), 'error');
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar tarea: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Ejecutar manualmente una tarea CRON
     */
    public function ejecutarTareaCron(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($input['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
            exit;
        }

        $nombreTarea = $input['nombre_tarea'] ?? '';

        try {
            $resultado = '';
            $exito = false;

            switch ($nombreTarea) {
                case 'generar_mensualidades':
                    // Ejecutar generación de mensualidades
                    $generadas = Mensualidad::generarMensualidadesMes();
                    $resultado = $generadas > 0 
                        ? "✓ Se generaron $generadas mensualidades para el mes actual" 
                        : "✓ No había mensualidades pendientes por generar";
                    $exito = true;
                    break;

                case 'verificar_bloqueos':
                    // Ejecutar verificación de bloqueos
                    $bloqueados = Mensualidad::verificarBloqueos();
                    $resultado = $bloqueados > 0
                        ? "✓ Se bloquearon $bloqueados controles por morosidad"
                        : "✓ No hay controles para bloquear";
                    $exito = true;
                    break;

                case 'enviar_notificaciones':
                    // Ejecutar envío de notificaciones
                    require_once __DIR__ . '/../models/Notificacion.php';
                    if (class_exists('Notificacion') && method_exists('Notificacion', 'enviarPendientes')) {
                        $enviadas = Notificacion::enviarPendientes();
                        $resultado = "✓ Se enviaron $enviadas notificaciones";
                        $exito = true;
                    } else {
                        $resultado = 'Funcionalidad de notificaciones no disponible';
                        $exito = false;
                    }
                    break;

                case 'actualizar_tasa_bcv':
                    $tasa = $this->consultarTasaBCV();
                    if ($tasa) {
                        $sql = "INSERT INTO tasa_cambio_bcv (tasa_usd_bs, fecha_registro, registrado_por, fuente)
                                VALUES (?, NOW(), ?, 'BCV CRON Manual')";
                        Database::execute($sql, [$tasa, $admin->id]);
                        $resultado = "Tasa BCV actualizada a $tasa Bs/USD";
                        $exito = true;
                    } else {
                        $resultado = 'No se pudo obtener la tasa del BCV';
                        $exito = false;
                    }
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => 'Tarea no reconocida']);
                    exit;
            }

            // Actualizar última ejecución
            $sql = "UPDATE configuracion_cron SET ultima_ejecucion = NOW() WHERE nombre_tarea = ?";
            Database::execute($sql, [$nombreTarea]);

            writeLog("Tarea CRON '$nombreTarea' ejecutada manualmente por admin {$admin->email}: $resultado", 'info');

            echo json_encode([
                'success' => $exito,
                'message' => $resultado
            ]);
        } catch (Exception $e) {
            writeLog("Error al ejecutar tarea CRON '$nombreTarea': " . $e->getMessage(), 'error');
            echo json_encode([
                'success' => false,
                'message' => 'Error al ejecutar tarea: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // ==================== HELPERS ====================

    /**
     * Generar contraseña temporal
     */
    private function generarPasswordTemporal(): string
    {
        return 'Est' . random_int(10000, 99999);
    }

    /**
     * Actualizar tarifa
     */
    private function updateTarifa(Usuario $admin): void
    {
        $montoMensualUSD = floatval($_POST['monto_mensual_usd'] ?? 0);
        $fechaInicio = $_POST['fecha_vigencia_inicio'] ?? date('Y-m-d');

        if ($montoMensualUSD <= 0) {
            $_SESSION['error'] = 'El monto mensual debe ser mayor a 0';
            return;
        }

        // Desactivar la configuración actual
        $sql = "UPDATE configuracion_tarifas SET activo = 0 WHERE activo = 1";
        Database::execute($sql);

        // Insertar nueva configuración
        $sql = "INSERT INTO configuracion_tarifas
                (monto_mensual_usd, fecha_vigencia_inicio, activo, creado_por, fecha_creacion)
                VALUES (?, ?, 1, ?, NOW())";

        Database::execute($sql, [$montoMensualUSD, $fechaInicio, $admin->id]);

        $_SESSION['success'] = 'Tarifa actualizada correctamente';
        writeLog("Tarifa mensual actualizada a $montoMensualUSD USD por admin {$admin->email}", 'info');
    }

    /**
     * Actualizar tasa BCV automáticamente desde la página del BCV
     */
    public function actualizarTasaBCV(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit;
            }
            redirect('admin/configuracion');
            return;
        }

        // Detectar si es petición AJAX
        $isAjax = $this->isAjaxRequest();

        // Validar CSRF - aceptar desde POST o JSON
        $csrfToken = '';
        if ($isAjax) {
            $input = json_decode(file_get_contents('php://input'), true);
            $csrfToken = $input['csrf_token'] ?? '';
        } else {
            $csrfToken = $_POST['csrf_token'] ?? '';
        }

        if (!ValidationHelper::validateCSRFToken($csrfToken)) {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
                exit;
            }
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/configuracion');
            return;
        }

        // Consultar tasa automáticamente desde BCV
        $tasa = $this->consultarTasaBCV();

        if ($tasa === null || $tasa <= 0) {
            $errorMsg = 'No se pudo obtener la tasa del BCV. Verifique su conexión a internet o intente más tarde.';
            writeLog("Error al consultar tasa BCV automáticamente por admin {$admin->email}", 'error');

            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $_SESSION['error'] = $errorMsg;
            redirect('admin/configuracion');
            return;
        }

        try {
            $sql = "INSERT INTO tasa_cambio_bcv (tasa_usd_bs, fecha_registro, registrado_por, fuente)
                    VALUES (?, NOW(), ?, 'BCV Automático')";

            Database::execute($sql, [$tasa, $admin->id]);

            $successMsg = "Tasa BCV actualizada correctamente a " . number_format($tasa, 2, '.', '') . " Bs/USD";
            writeLog("Tasa BCV actualizada automáticamente a $tasa por admin {$admin->email}", 'info');

            // Obtener fecha de registro
            $sqlFecha = "SELECT DATE_FORMAT(fecha_registro, '%d/%m/%Y %H:%i') as fecha_formateada
                         FROM tasa_cambio_bcv ORDER BY fecha_registro DESC LIMIT 1";
            $resultado = Database::fetchOne($sqlFecha);
            $fechaActualizacion = $resultado['fecha_formateada'] ?? date('d/m/Y H:i');

            if ($isAjax) {
                echo json_encode([
                    'success' => true,
                    'message' => $successMsg,
                    'tasa' => number_format($tasa, 2, '.', ''),
                    'fecha' => $fechaActualizacion,
                    'fuente' => 'BCV Automático'
                ]);
                exit;
            }

            $_SESSION['success'] = $successMsg;
            redirect('admin/configuracion');

        } catch (Exception $e) {
            $errorMsg = 'Error al guardar la tasa en la base de datos: ' . $e->getMessage();
            writeLog("Error al guardar tasa BCV: " . $e->getMessage(), 'error');

            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $_SESSION['error'] = $errorMsg;
            redirect('admin/configuracion');
        }
    }

    /**
     * Verificar si la petición es AJAX
     */
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Enviar respuesta JSON
     *
     * @param array $data Datos a enviar
     * @param int $statusCode Código HTTP
     * @return void
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Consultar tasa de cambio desde la página oficial del BCV
     *
     * @return float|null Tasa USD/BS o null si falla
     */
    private function consultarTasaBCV(): ?float
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
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

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
     * Actualizar tasa BCV (método privado para updateConfiguracion)
     */
    private function updateTasaBCV(Usuario $admin): void
    {
        $tasa = floatval($_POST['tasa_bcv'] ?? 0);

        if ($tasa <= 0) {
            $_SESSION['error'] = 'La tasa debe ser mayor a 0';
            return;
        }

        $sql = "INSERT INTO tasa_cambio_bcv (tasa_usd_bs, fecha_registro, registrado_por, fuente)
                VALUES (?, NOW(), ?, 'Manual')";

        Database::execute($sql, [$tasa, $admin->id]);

        $_SESSION['success'] = 'Tasa BCV actualizada correctamente';
        writeLog("Tasa BCV actualizada a $tasa por admin {$admin->email}", 'info');
    }

    private function getEstadisticasUsuarios(): array
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN rol = 'cliente' THEN 1 ELSE 0 END) as clientes,
                    SUM(CASE WHEN rol = 'operador' THEN 1 ELSE 0 END) as operadores,
                    SUM(CASE WHEN rol = 'consultor' THEN 1 ELSE 0 END) as consultores,
                    SUM(CASE WHEN rol = 'administrador' THEN 1 ELSE 0 END) as administradores,
                    SUM(CASE WHEN activo = TRUE THEN 1 ELSE 0 END) as activos
                FROM usuarios";

        return Database::fetchOne($sql) ?: [];
    }

    private function getEstadisticasPagos(): array
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN estado_comprobante = 'aprobado' THEN 1 ELSE 0 END) as aprobados,
                    SUM(CASE WHEN estado_comprobante = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado_comprobante = 'rechazado' THEN 1 ELSE 0 END) as rechazados,
                    SUM(CASE WHEN estado_comprobante = 'aprobado' THEN monto_usd ELSE 0 END) as total_usd,
                    SUM(CASE WHEN estado_comprobante = 'aprobado' THEN monto_bs ELSE 0 END) as total_bs
                FROM pagos";

        return Database::fetchOne($sql) ?: [];
    }

    private function getEstadisticasMorosidad(): array
    {
        $sql = "SELECT COUNT(DISTINCT usuario_id) as total_morosos
                FROM vista_morosidad
                WHERE meses_pendientes >= 1";

        return Database::fetchOne($sql) ?: [];
    }

    private function getActividadReciente(int $limit): array
    {
        $sql = "SELECT * FROM logs_actividad
                ORDER BY fecha_hora DESC
                LIMIT ?";

        return Database::fetchAll($sql, [$limit]);
    }

    /**
     * Limpiar caché del sistema
     */
    public function limpiarCache(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!ValidationHelper::validateCSRFToken($input['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token inválido']);
            exit;
        }

        try {
            // Limpiar sesiones antiguas
            session_gc();

            // Limpiar logs antiguos (más de 30 días)
            $sql = "DELETE FROM logs_actividad WHERE fecha_hora < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $deleted = Database::execute($sql);

            writeLog("Caché limpiado por admin {$admin->email} - $deleted registros de log eliminados", 'info');

            echo json_encode([
                'success' => true,
                'message' => "Caché limpiado exitosamente. Se eliminaron $deleted registros antiguos."
            ]);
        } catch (Exception $e) {
            writeLog("Error al limpiar caché: " . $e->getMessage(), 'error');
            echo json_encode(['success' => false, 'message' => 'Error al limpiar caché']);
        }
        exit;
    }

    /**
     * Generar mensualidades del mes actual manualmente
     */
    public function regenerarMensualidades(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!ValidationHelper::validateCSRFToken($input['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token inválido']);
            exit;
        }

        try {
            // Llamar directamente al método del modelo
            $generadas = Mensualidad::generarMensualidadesMes();

            writeLog("Mensualidades generadas manualmente por admin {$admin->email}: $generadas registros", 'info');

            $mensaje = $generadas > 0 
                ? "✓ Se generaron $generadas mensualidades para el mes actual" 
                : "✓ No había mensualidades pendientes por generar. Todas las mensualidades del mes actual ya están creadas.";

            echo json_encode([
                'success' => true,
                'message' => $mensaje,
                'cantidad' => $generadas
            ]);
        } catch (Exception $e) {
            writeLog("Error al generar mensualidades: " . $e->getMessage(), 'error');
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar mensualidades: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Verificar integridad de datos
     */
    public function verificarIntegridad(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!ValidationHelper::validateCSRFToken($input['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token inválido']);
            exit;
        }

        $errores = [];
        $advertencias = [];

        try {
            // Verificar usuarios sin apartamento
            $sql = "SELECT COUNT(*) as total FROM usuarios u
                    LEFT JOIN apartamento_usuario au ON u.id = au.usuario_id
                    WHERE u.rol = 'cliente' AND au.id IS NULL AND u.activo = 1";
            $result = Database::fetchOne($sql);
            if ($result['total'] > 0) {
                $advertencias[] = "{$result['total']} clientes activos sin apartamento asignado";
            }

            // Verificar apartamentos sin controles
            $sql = "SELECT COUNT(*) as total FROM apartamento_usuario au
                    LEFT JOIN controles c ON au.id = c.apartamento_usuario_id
                    WHERE c.id IS NULL AND au.activo = 1";
            $result = Database::fetchOne($sql);
            if ($result['total'] > 0) {
                $advertencias[] = "{$result['total']} apartamentos sin controles asignados";
            }

            // Verificar mensualidades sin tasa de cambio
            $sql = "SELECT COUNT(*) as total FROM mensualidades WHERE tasa_cambio_id IS NULL";
            $result = Database::fetchOne($sql);
            if ($result['total'] > 0) {
                $errores[] = "{$result['total']} mensualidades sin tasa de cambio";
            }

            // Verificar pagos huérfanos
            $sql = "SELECT COUNT(*) as total FROM pagos p
                    LEFT JOIN apartamento_usuario au ON p.apartamento_usuario_id = au.id
                    WHERE au.id IS NULL";
            $result = Database::fetchOne($sql);
            if ($result['total'] > 0) {
                $errores[] = "{$result['total']} pagos huérfanos (sin apartamento)";
            }

            $mensaje = "Verificación completada:\n\n";

            if (empty($errores) && empty($advertencias)) {
                $mensaje .= "✓ No se encontraron problemas de integridad";
            } else {
                if (!empty($errores)) {
                    $mensaje .= "❌ ERRORES:\n" . implode("\n", $errores) . "\n\n";
                }
                if (!empty($advertencias)) {
                    $mensaje .= "⚠️ ADVERTENCIAS:\n" . implode("\n", $advertencias);
                }
            }

            writeLog("Verificación de integridad ejecutada por admin {$admin->email}", 'info');

            echo json_encode([
                'success' => empty($errores),
                'message' => $mensaje
            ]);
        } catch (Exception $e) {
            writeLog("Error al verificar integridad: " . $e->getMessage(), 'error');
            echo json_encode(['success' => false, 'message' => 'Error al verificar integridad']);
        }
        exit;
    }


    /**
     * Aprobar/rechazar solicitud (igual que operadores)
     */
    public function processSolicitud(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/solicitudes');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/solicitudes');
            return;
        }

        $solicitudId = intval($_POST['solicitud_id'] ?? 0);
        $accion = $_POST['accion'] ?? '';
        $observaciones = sanitize($_POST['observaciones'] ?? '');

        if (!in_array($accion, ['aprobar', 'rechazar'])) {
            $_SESSION['error'] = 'Acción inválida';
            redirect('admin/solicitudes');
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
            writeLog("Solicitud ID $solicitudId {$estado} por admin {$usuario->email}", 'info');
        } else {
            $_SESSION['error'] = 'Error al procesar la solicitud';
        }

        redirect('admin/solicitudes');
    }

    /**
     * Obtener solicitudes pendientes (igual que operadores)
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
     * Exportar base de datos (backup manual)
     */
    public function exportarBaseDatos(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) {
            $_SESSION['error'] = 'No autorizado';
            redirect('admin/dashboard');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_GET['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token inválido';
            redirect('admin/configuracion');
            return;
        }

        try {
            // Ejecutar el script de backup
            $backupScript = ROOT_PATH . '/cron/backup_database.php';

            if (!file_exists($backupScript)) {
                throw new Exception('Script de backup no encontrado');
            }

            // Ejecutar el script y capturar la salida
            ob_start();
            require $backupScript;
            ob_end_clean();

            // Buscar el backup más reciente
            $backupDir = ROOT_PATH . '/backups';
            $files = glob($backupDir . '/backup_db_*.sql.gz');

            if (empty($files)) {
                throw new Exception('No se generó el archivo de backup');
            }

            // Ordenar por fecha de modificación (más reciente primero)
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            $latestBackup = $files[0];

            // Enviar el archivo para descarga
            header('Content-Type: application/gzip');
            header('Content-Disposition: attachment; filename="' . basename($latestBackup) . '"');
            header('Content-Length: ' . filesize($latestBackup));
            readfile($latestBackup);

            writeLog("Backup de BD descargado por admin {$admin->email}", 'info');
            exit;

        } catch (Exception $e) {
            writeLog("Error al exportar BD: " . $e->getMessage(), 'error');
            $_SESSION['error'] = 'Error al generar el backup: ' . $e->getMessage();
            redirect('admin/configuracion');
        }
    }

    // ==================== GESTIÓN DE PERFIL ====================

    /**
     * Ver perfil del administrador
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

        require_once __DIR__ . '/../views/admin/perfil.php';
    }

    /**
     * Actualizar perfil del administrador
     */
    public function updatePerfil(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/perfil');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('admin/perfil');
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
            redirect('admin/perfil');
            return;
        }

        // Validar email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Formato de email inválido';
            redirect('admin/perfil');
            return;
        }

        // Verificar si el email ya está en uso por otro usuario
        if ($email !== $usuario->email) {
            $existingUser = Usuario::findByEmail($email);
            if ($existingUser && $existingUser->id !== $usuario->id) {
                $_SESSION['error'] = 'El email ya está en uso por otro usuario';
                redirect('admin/perfil');
                return;
            }
        }

        // Validar teléfono
        if (!empty($telefono) && !ValidationHelper::validatePhone($telefono)) {
            $_SESSION['error'] = 'Formato de teléfono inválido';
            redirect('admin/perfil');
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
            $sql = "SELECT id, apartamento_id FROM apartamento_usuario
                    WHERE usuario_id = ? AND activo = 1 LIMIT 1";
            $asignacionActual = Database::fetchOne($sql, [$usuario->id]);

            if ($apartamentoId > 0) {
                if ($asignacionActual) {
                    if ($asignacionActual['apartamento_id'] != $apartamentoId) {
                        $sql = "UPDATE apartamento_usuario SET activo = 0 WHERE id = ?";
                        Database::execute($sql, [$asignacionActual['id']]);

                        $sql = "INSERT INTO apartamento_usuario (usuario_id, apartamento_id, activo, fecha_asignacion)
                                VALUES (?, ?, 1, NOW())";
                        Database::execute($sql, [$usuario->id, $apartamentoId]);

                        writeLog("Apartamento cambiado para usuario {$usuario->email} (ID: {$usuario->id})", 'info');
                    }
                } else {
                    $sql = "INSERT INTO apartamento_usuario (usuario_id, apartamento_id, activo, fecha_asignacion)
                            VALUES (?, ?, 1, NOW())";
                    Database::execute($sql, [$usuario->id, $apartamentoId]);

                    writeLog("Apartamento asignado a usuario {$usuario->email} (ID: {$usuario->id})", 'info');
                }
            } else {
                if ($asignacionActual) {
                    $sql = "UPDATE apartamento_usuario SET activo = 0 WHERE id = ?";
                    Database::execute($sql, [$asignacionActual['id']]);

                    writeLog("Apartamento desasignado de usuario {$usuario->email} (ID: {$usuario->id})", 'info');
                }
            }

            $_SESSION['success'] = 'Perfil actualizado correctamente';

        } catch (Exception $e) {
            writeLog("Error al actualizar perfil de administrador: " . $e->getMessage(), 'error');
            $_SESSION['error'] = 'Error al actualizar el perfil. Intente nuevamente.';
        }

        redirect('admin/perfil');
    }

    // ==================== GESTIÓN DE SOLICITUDES ====================

    /**
     * Gestión de solicitudes
     */
    public function solicitudes(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Obtener TODAS las solicitudes pendientes, no solo las de registro
        $solicitudesPendientes = SolicitudCambio::getPendientes();

        require_once __DIR__ . '/../views/admin/solicitudes/index.php';
    }

    /**
     * Aprobar solicitud
     */
    public function aprobarSolicitud(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
            exit;
        }

        $solicitudId = (int)($_POST['solicitud_id'] ?? 0);

        if ($solicitudId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de solicitud inválido']);
            exit;
        }

        $solicitud = SolicitudCambio::findById($solicitudId);

        if (!$solicitud) {
            echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
            exit;
        }

        if ($solicitud->aprobar($admin->id, null)) {
            echo json_encode(['success' => true, 'message' => 'Solicitud aprobada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al aprobar la solicitud']);
        }
        exit;
    }

    /**
     * Aprobar solicitud de registro con asignación manual de controles
     */
    public function aprobarSolicitudRegistro(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) {
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
            $usuarioId = $solicitud->crearUsuarioConAsignacionManual($admin->id, $datosAsignacion);

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
            writeLog("Error en aprobarSolicitudRegistro: " . $e->getMessage(), 'error');
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        exit;
    }

    /**
     * Rechazar solicitud
     */
    public function rechazarSolicitud(): void
    {
        $admin = $this->checkAuth();
        if (!$admin) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
            exit;
        }

        $solicitudId = (int)($_POST['solicitud_id'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');

        if ($solicitudId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de solicitud inválido']);
            exit;
        }

        if (empty($motivo)) {
            echo json_encode(['success' => false, 'message' => 'Debe proporcionar un motivo de rechazo']);
            exit;
        }

        $solicitud = SolicitudCambio::findById($solicitudId);

        if (!$solicitud) {
            echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
            exit;
        }

        if ($solicitud->rechazar($admin->id, $motivo)) {
            echo json_encode(['success' => true, 'message' => 'Solicitud rechazada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al rechazar la solicitud']);
        }
        exit;
    }

    /**
     * Reporte de morosidad
     */
    public function reporteMorosidad(): void
    {
        // Permitir acceso a operadores y consultores también
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['operador', 'consultor', 'administrador'])) {
            redirect('auth/login');
            return;
        }

        $usuario = Usuario::findById($_SESSION['user_id']);
        if (!$usuario || !$usuario->activo) {
            session_destroy();
            redirect('auth/login');
            return;
        }

        // 🔒 SEGURIDAD CRÍTICA: Verificar si el usuario debe cambiar contraseña obligatoriamente
        require_once __DIR__ . '/AuthController.php';
        AuthController::forzarCambioPasswordSiNecesario($usuario);

        // Filtros
        $mesesMinimos = intval($_GET['meses_min'] ?? 1);
        $bloque = sanitize($_GET['torre'] ?? '');
        $orden = sanitize($_GET['orden'] ?? 'meses_desc');

        $sql = "SELECT
                    u.id,
                    u.nombre_completo,
                    u.email,
                    u.telefono,
                    u.cedula,
                    a.bloque as torre,
                    a.numero_apartamento as apartamento,
                    COUNT(m.id) as meses_vencidos,
                    SUM(m.monto_usd) as deuda_total,
                    MIN(CONCAT(m.anio, '-', LPAD(m.mes, 2, '0'), '-01')) as primera_mensualidad_vencida,
                    MAX(CONCAT(m.anio, '-', LPAD(m.mes, 2, '0'), '-01')) as ultima_mensualidad,
                    (SELECT COUNT(*) 
                     FROM controles_estacionamiento ce 
                     JOIN apartamento_usuario au2 ON au2.id = ce.apartamento_usuario_id 
                     WHERE au2.usuario_id = u.id AND au2.activo = 1) as total_controles
                FROM usuarios u
                JOIN apartamento_usuario au ON au.usuario_id = u.id AND au.activo = TRUE
                JOIN apartamentos a ON a.id = au.apartamento_id
                JOIN mensualidades m ON m.apartamento_usuario_id = au.id AND m.estado IN ('pendiente', 'vencido')
                WHERE 1=1";

        $params = [];

        if ($bloque) {
            $sql .= " AND a.bloque = ?";
            $params[] = $bloque;
        }

        $sql .= " GROUP BY u.id, a.id
                  HAVING COUNT(m.id) >= ?";

        $params[] = $mesesMinimos;

        // Ordenamiento
        switch ($orden) {
            case 'meses_asc':
                $sql .= " ORDER BY meses_vencidos ASC";
                break;
            case 'deuda_desc':
                $sql .= " ORDER BY deuda_total DESC";
                break;
            case 'deuda_asc':
                $sql .= " ORDER BY deuda_total ASC";
                break;
            case 'meses_desc':
            default:
                $sql .= " ORDER BY meses_vencidos DESC";
                break;
        }

        $morosos = Database::fetchAll($sql, $params);

        // Resumen para la vista
        $totalDeuda = 0;
        $totalMensualidades = 0;
        $bloqueados = 0;

        foreach ($morosos as $m) {
            $totalDeuda += $m['deuda_total'];
            $totalMensualidades += $m['meses_vencidos'];
            if ($m['meses_vencidos'] >= MESES_BLOQUEO) {
                $bloqueados++;
            }
        }

        // Obtener bloques para filtro
        $bloques = Apartamento::getBloques();

        require_once __DIR__ . '/../views/admin/reporte_morosidad.php';
    }
}
