<?php
/**
 * AuthController - Manejo de autenticación
 *
 * Login, Logout, Recuperación de contraseña (User Story #6)
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/MailHelper.php';

class AuthController
{
    /**
     * Verificar si un usuario debe ser redirigido al cambio obligatorio de contraseña
     * Método de seguridad crítica para prevenir bypass del cambio de contraseña
     *
     * @param Usuario $usuario Usuario a verificar
     * @return bool True si debe ser redirigido, false si puede continuar
     */
    public static function debeCambiarPasswordObligatorio(Usuario $usuario): bool
    {
        return $usuario->password_temporal || $usuario->primer_acceso;
    }

    /**
     * Forzar redirección al cambio obligatorio de contraseña si es necesario
     * Método de seguridad crítica
     *
     * @param Usuario $usuario Usuario a verificar
     */
    public static function forzarCambioPasswordSiNecesario(Usuario $usuario): void
    {
        if (self::debeCambiarPasswordObligatorio($usuario)) {
            writeLog("SEGURIDAD: Usuario {$usuario->email} (ID: {$usuario->id}) con contraseña temporal intentó acceder a página protegida. Redirigiendo a cambio obligatorio.", 'warning');
            redirect('auth/cambiar-password-obligatorio');
        }
    }

    /**
     * Mostrar formulario de login
     */
    public function login(): void
    {
        // Si ya está autenticado, redirigir al dashboard correspondiente
        if (isset($_SESSION['user_id'])) {
            $rol = $_SESSION['user_rol'] ?? 'cliente';
            $dashboardRol = $rol === 'administrador' ? 'admin' : $rol;
            redirect("$dashboardRol/dashboard");
        }

        // Renderizar vista de login
        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Procesar login
     */
    public function processLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('auth/login');
        }

        // Validar CSRF token
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('auth/login');
        }

        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validar campos requeridos
        if (empty($email) || empty($password)) {
            $_SESSION['error'] = 'Todos los campos son obligatorios';
            redirect('auth/login');
        }

        // Validar formato de email
        if (!ValidationHelper::validateEmail($email)) {
            $_SESSION['error'] = 'Formato de email inválido';
            redirect('auth/login');
        }

        // Verificar credenciales
        $resultado = Usuario::verifyLogin($email, $password);

        if (!$resultado['success']) {
            $_SESSION['error'] = $resultado['message'];
            redirect('auth/login');
        }

        $usuario = $resultado['user'];

        // 🔒 SEGURIDAD CRÍTICA: Verificar que el usuario esté ACTIVO antes de crear sesión
        if (!$usuario->activo) {
            writeLog("Intento de login con usuario inactivo: {$usuario->email} (ID: {$usuario->id})", 'warning');
            $_SESSION['error'] = 'Tu cuenta ha sido desactivada. Contacta al administrador.';
            redirect('auth/login');
            return;
        }

        // Crear sesión
        $_SESSION['user_id'] = $usuario->id;
        $_SESSION['user_nombre'] = $usuario->nombre_completo;
        $_SESSION['user_email'] = $usuario->email;
        $_SESSION['user_rol'] = $usuario->rol;
        $_SESSION['user_primer_acceso'] = $usuario->primer_acceso;
        $_SESSION['user_password_temporal'] = $usuario->password_temporal;

        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);

        writeLog("Login exitoso: {$usuario->email} (ID: {$usuario->id})", 'info');

        // Verificar si es primer acceso (User Story #2)
        if ($usuario->primer_acceso || $usuario->password_temporal) {
            redirect('auth/cambiar-password-obligatorio');
        }

        // Redirigir al dashboard según rol (admin en lugar de administrador)
        $dashboardRol = $usuario->rol === 'administrador' ? 'admin' : $usuario->rol;
        redirect("{$dashboardRol}/dashboard");
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $userEmail = $_SESSION['user_email'] ?? 'Desconocido';

        // 1. Guardar el mensaje de éxito antes de destruir la sesión
        $successMessage = 'Sesión cerrada correctamente';

        // Destruir sesión
        session_unset();
        session_destroy();

        writeLog("Logout: $userEmail (ID: $userId)", 'info');

        // 2. Iniciar una nueva sesión limpia para el mensaje flash
        session_start();
        $_SESSION['success'] = $successMessage;

        redirect('auth/login');
    }

    /**
     * Mostrar formulario de recuperación de contraseña (User Story #6)
     */
    public function forgotPassword(): void
    {
        require_once __DIR__ . '/../views/auth/forgot_password.php';
    }

    /**
     * Procesar solicitud de recuperación de contraseña
     */
    public function processForgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('auth/forgot-password');
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('auth/forgot-password');
        }

        $email = sanitize($_POST['email'] ?? '');

        // Validar email
        if (!ValidationHelper::validateEmail($email)) {
            $_SESSION['error'] = 'Formato de email inválido';
            redirect('auth/forgot-password');
        }

        // Verificar rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!$this->checkRateLimiting($ip)) {
            $_SESSION['error'] = 'Por favor, espere ' . PASSWORD_RESET_RATE_LIMIT . ' segundos antes de solicitar otro código';
            redirect('auth/forgot-password');
        }

        $usuario = Usuario::findByEmail($email);

        // No revelar si el email existe (anti-enumeración)
        $_SESSION['success'] = 'Si el email existe en nuestro sistema, recibirás un código de verificación';

        // Si el usuario existe y está activo, enviar código
        if ($usuario && $usuario->activo) {
            // Generar código de 6 dígitos
            $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+' . PASSWORD_RESET_CODE_EXPIRATION . ' minutes'));

            // Guardar en BD
            $sql = "INSERT INTO password_reset_tokens
                    (usuario_id, email, codigo, fecha_expiracion, ip_address, user_agent)
                    VALUES (?, ?, ?, ?, ?, ?)";

            Database::execute($sql, [
                $usuario->id,
                $email,
                $codigo,
                $fechaExpiracion,
                $ip,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            // Enviar email con código
            MailHelper::sendPasswordResetCode($email, $usuario->nombre_completo, $codigo);

            writeLog("Código de recuperación enviado a: $email", 'info');

            // Guardar email en sesión para el siguiente paso
            $_SESSION['reset_email'] = $email;
        } else {
            // Log de intento con email no registrado
            writeLog("Intento de recuperación con email no registrado: $email", 'warning');
        }

        redirect('auth/verificar-codigo');
    }

    /**
     * Mostrar formulario de verificación de código
     */
    public function verificarCodigo(): void
    {
        if (!isset($_SESSION['reset_email'])) {
            redirect('auth/forgot-password');
        }

        require_once __DIR__ . '/../views/auth/verify_code.php';
    }

    /**
     * Procesar verificación de código
     */
    public function processVerificarCodigo(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('auth/verificar-codigo');
        }

        if (!isset($_SESSION['reset_email'])) {
            redirect('auth/forgot-password');
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('auth/verificar-codigo');
        }

        $codigo = sanitize($_POST['codigo'] ?? '');
        $email = $_SESSION['reset_email'];

        // Validar formato de código
        if (!ValidationHelper::validateVerificationCode($codigo)) {
            $_SESSION['error'] = 'Código inválido. Debe ser de 6 dígitos';
            redirect('auth/verificar-codigo');
        }

        // Buscar código en BD
        $sql = "SELECT * FROM password_reset_tokens
                WHERE email = ?
                  AND codigo = ?
                  AND usado = FALSE
                  AND fecha_expiracion > NOW()
                ORDER BY fecha_creacion DESC
                LIMIT 1";

        $token = Database::fetchOne($sql, [$email, $codigo]);

        if (!$token) {
            // Incrementar intentos fallidos
            $this->incrementarIntentosValidacion($email, $codigo);

            $_SESSION['error'] = 'Código incorrecto o expirado';
            redirect('auth/verificar-codigo');
        }

        // Código válido - marcar como verificado en sesión
        $_SESSION['reset_token_id'] = $token['id'];
        $_SESSION['reset_usuario_id'] = $token['usuario_id'];

        writeLog("Código verificado correctamente para: $email", 'info');

        redirect('auth/nueva-password');
    }

    /**
     * Mostrar formulario de nueva contraseña
     */
    public function nuevaPassword(): void
    {
        if (!isset($_SESSION['reset_token_id'])) {
            redirect('auth/forgot-password');
        }

        require_once __DIR__ . '/../views/auth/new_password.php';
    }

    /**
     * Procesar nueva contraseña
     */
    public function processNuevaPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('auth/nueva-password');
        }

        if (!isset($_SESSION['reset_token_id']) || !isset($_SESSION['reset_usuario_id'])) {
            redirect('auth/forgot-password');
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('auth/nueva-password');
        }

        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Validar que coincidan
        if ($password !== $passwordConfirm) {
            $_SESSION['error'] = 'Las contraseñas no coinciden';
            redirect('auth/nueva-password');
        }

        // Validar requisitos de contraseña
        $validacion = ValidationHelper::validatePassword($password);
        if (!$validacion['valid']) {
            $_SESSION['error'] = implode('<br>', $validacion['errors']);
            redirect('auth/nueva-password');
        }

        // Cargar usuario
        $usuario = Usuario::findById($_SESSION['reset_usuario_id']);

        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            redirect('auth/forgot-password');
        }

        // Verificar que no sea la misma contraseña anterior
        $sql = "SELECT password FROM usuarios WHERE id = ?";
        $result = Database::fetchOne($sql, [$usuario->id]);

        if (password_verify($password, $result['password'])) {
            $_SESSION['error'] = 'No puedes usar la misma contraseña anterior';
            redirect('auth/nueva-password');
        }

        // Cambiar contraseña
        if (!$usuario->cambiarPassword($password)) {
            $_SESSION['error'] = 'Error al cambiar la contraseña';
            redirect('auth/nueva-password');
        }

        // Marcar token como usado
        $sql = "UPDATE password_reset_tokens SET usado = TRUE WHERE id = ?";
        Database::execute($sql, [$_SESSION['reset_token_id']]);

        // Enviar email de confirmación
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        MailHelper::sendPasswordChanged($usuario->email, $usuario->nombre_completo, $ip);

        writeLog("Contraseña cambiada exitosamente para: {$usuario->email}", 'info');

        // Limpiar sesión
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_token_id']);
        unset($_SESSION['reset_usuario_id']);

        $_SESSION['success'] = 'Contraseña actualizada correctamente. Puedes iniciar sesión';
        redirect('auth/login');
    }

    /**
     * Cambio de contraseña obligatorio (User Story #2)
     */
    public function cambiarPasswordObligatorio(): void
    {
        if (!isset($_SESSION['user_id'])) {
            redirect('auth/login');
        }

        // Verificar que el usuario aún existe en la base de datos
        $usuario = Usuario::findById($_SESSION['user_id']);
        if (!$usuario) {
            // Usuario no existe - redirigir al login con mensaje
            session_destroy();
            session_start();
            $_SESSION['error'] = 'Usuario no encontrado. Por favor, registra tu cuenta nuevamente.';
            redirect('auth/login');
            return;
        }

        // Verificar que el usuario esté activo
        if (!$usuario->activo) {
            session_destroy();
            session_start();
            $_SESSION['error'] = 'Tu cuenta ha sido desactivada. Contacta al administrador.';
            redirect('auth/login');
            return;
        }

        // 🔒 SEGURIDAD: Verificar que aún necesite cambiar contraseña
        if (!$usuario->primer_acceso && !$usuario->password_temporal) {
            // Usuario ya cambió contraseña - redirigir al dashboard
            $rol = $usuario->rol === 'administrador' ? 'admin' : $usuario->rol;
            redirect("$rol/dashboard");
            return;
        }

        // Si no es primer acceso ni tiene contraseña temporal, redirigir al dashboard
        if (!$usuario->primer_acceso && !$usuario->password_temporal) {
            $rol = $usuario->rol === 'administrador' ? 'admin' : $usuario->rol;
            redirect("$rol/dashboard");
        }

        require_once __DIR__ . '/../views/auth/cambiar_password_obligatorio.php';
    }

    /**
     * Procesar cambio de contraseña obligatorio
     */
    public function processCambiarPasswordObligatorio(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('auth/cambiar-password-obligatorio');
        }

        if (!isset($_SESSION['user_id'])) {
            redirect('auth/login');
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('auth/cambiar-password-obligatorio');
        }

        $passwordActual = $_POST['password_actual'] ?? '';
        $passwordNueva = $_POST['password_nueva'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $usuario = Usuario::findById($_SESSION['user_id']);

        if (!$usuario) {
            $this->logout();
            return;
        }

        // Validar contraseña actual
        $sql = "SELECT password FROM usuarios WHERE id = ?";
        $result = Database::fetchOne($sql, [$usuario->id]);

        if (!password_verify($passwordActual, $result['password'])) {
            $_SESSION['error'] = 'Contraseña actual incorrecta';
            redirect('auth/cambiar-password-obligatorio');
        }

        // Validar que coincidan
        if ($passwordNueva !== $passwordConfirm) {
            $_SESSION['error'] = 'Las contraseñas no coinciden';
            redirect('auth/cambiar-password-obligatorio');
        }

        // Validar requisitos
        $validacion = ValidationHelper::validatePassword($passwordNueva);
        if (!$validacion['valid']) {
            $_SESSION['error'] = implode('<br>', $validacion['errors']);
            redirect('auth/cambiar-password-obligatorio');
        }

        // Cambiar contraseña
        if (!$usuario->cambiarPassword($passwordNueva)) {
            $_SESSION['error'] = 'Error al cambiar la contraseña';
            redirect('auth/cambiar-password-obligatorio');
        }

        // Marcar primer acceso como completado
        $usuario->marcarPrimerAccesoCompletado();

        // Actualizar sesión
        $_SESSION['user_primer_acceso'] = false;
        $_SESSION['user_password_temporal'] = false;

        writeLog("Primer acceso completado y contraseña cambiada: {$usuario->email}", 'info');

        $_SESSION['success'] = 'Contraseña actualizada correctamente';

        // Redirigir al dashboard
        redirect("{$usuario->rol}/dashboard");
    }

    /**
     * Verificar rate limiting para recuperación de contraseña
     *
     * @param string $ip Dirección IP
     * @return bool True si puede continuar, false si debe esperar
     */
    private function checkRateLimiting(string $ip): bool
    {
        $sql = "SELECT MAX(fecha_creacion) as ultima_solicitud
                FROM password_reset_tokens
                WHERE ip_address = ?";

        $result = Database::fetchOne($sql, [$ip]);

        if ($result && $result['ultima_solicitud']) {
            $tiempoTranscurrido = time() - strtotime($result['ultima_solicitud']);

            if ($tiempoTranscurrido < PASSWORD_RESET_RATE_LIMIT) {
                return false;
            }
        }

        return true;
    }

    /**
     * Incrementar intentos fallidos de validación de código
     *
     * @param string $email Email
     * @param string $codigo Código intentado
     */
    private function incrementarIntentosValidacion(string $email, string $codigo): void
    {
        $sql = "UPDATE password_reset_tokens
                SET intentos_validacion = intentos_validacion + 1
                WHERE email = ? AND codigo = ?";

        Database::execute($sql, [$email, $codigo]);

        // Si alcanza el máximo, invalidar token
        $sql = "UPDATE password_reset_tokens
                SET usado = TRUE
                WHERE email = ?
                  AND intentos_validacion >= ?";

        Database::execute($sql, [$email, PASSWORD_RESET_MAX_ATTEMPTS]);
    }

    // ============================================================================
    // MÉTODOS DE REGISTRO DE NUEVOS USUARIOS
    // ============================================================================

    /**
     * Alias para registroTradicional (para compatibilidad con rutas)
     */
    public function registro(): void
    {
        $this->registroTradicional();
    }

    /**
     * Mostrar formulario de registro tradicional
     */
    public function registroTradicional(): void
    {
        // Si ya está autenticado, redirigir
        if (isset($_SESSION['user_id'])) {
            $rol = $_SESSION['user_rol'] ?? 'cliente';
            $dashboardRol = $rol === 'administrador' ? 'admin' : $rol;
            redirect("$dashboardRol/dashboard");
        }

        // Solo cargar bloques - escaleras y pisos se cargan dinámicamente
        $bloques = $this->getBloquesDisponibles();
        $escaleras = []; // Vacío - se carga dinámicamente
        $pisos = []; // Vacío - se carga dinámicamente

        require_once __DIR__ . '/../views/auth/registro_tradicional.php';
    }

    /**
     * Alias para processRegistroTradicional (para compatibilidad con rutas)
     */
    public function processRegistro(): void
    {
        $this->processRegistroTradicional();
    }

    /**
     * Procesar solicitud de registro tradicional
     */
    public function processRegistroTradicional(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('auth/registro');
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('auth/registro');
        }

        // Validar datos del formulario
        $errores = $this->validarDatosRegistro($_POST);

        if (!empty($errores)) {
            $_SESSION['error'] = implode('<br>', $errores);
            $_SESSION['form_data'] = $_POST;
            redirect('auth/registro');
        }

        // Verificar que el email no esté registrado
        if (Usuario::findByEmail($_POST['email'])) {
            $_SESSION['error'] = 'El email ya está registrado en el sistema';
            $_SESSION['form_data'] = $_POST;
            redirect('auth/registro');
        }

        // Crear solicitud de registro
        $this->crearSolicitudRegistro($_POST);

        // Limpiar datos del formulario
        unset($_SESSION['form_data']);

        $_SESSION['success'] = '¡Solicitud de registro enviada exitosamente! Una vez aprobada, podrás iniciar sesión con la contraseña temporal <strong>123456</strong> y cambiarla inmediatamente. Te notificaremos por email cuando sea aprobada.';
        redirect('auth/login');
    }

    /**
     * Validar datos del formulario de registro
     *
     * @param array $data Datos del formulario
     * @return array Lista de errores (vacío si todo es válido)
     */
    private function validarDatosRegistro(array $data): array
    {
        $errores = [];

        // Nombre
        if (empty(trim($data['nombre'] ?? ''))) {
            $errores[] = 'El nombre es obligatorio';
        }

        // Apellido
        if (empty(trim($data['apellido'] ?? ''))) {
            $errores[] = 'El apellido es obligatorio';
        }

        // Email
        if (empty(trim($data['email'] ?? ''))) {
            $errores[] = 'El email es obligatorio';
        } elseif (!ValidationHelper::validateEmail($data['email'])) {
            $errores[] = 'Formato de email inválido';
        }

        // Teléfono
        if (empty(trim($data['telefono'] ?? ''))) {
            $errores[] = 'El teléfono es obligatorio';
        } elseif (!ValidationHelper::validatePhone($data['telefono'])) {
            $errores[] = 'Formato de teléfono inválido (Ej: 04141234567)';
        }

        // Nota: Ya no se solicita contraseña en el registro
        // Se asignará una contraseña temporal automáticamente

        // Apartamento
        if (empty($data['bloque'] ?? '')) {
            $errores[] = 'Debe seleccionar un bloque';
        }

        if (empty($data['escalera'] ?? '')) {
            $errores[] = 'Debe seleccionar una escalera';
        }

        if (!isset($data['piso']) || $data['piso'] === '') {
            $errores[] = 'Debe seleccionar un piso';
        }

        if (empty($data['apartamento'] ?? '')) {
            $errores[] = 'Debe seleccionar un apartamento';
        }

        // Verificar que la combinación bloque-escalera-piso-apartamento existe
        if (!empty($data['bloque']) && !empty($data['escalera']) && isset($data['piso']) && $data['piso'] !== '' && !empty($data['apartamento'])) {
            if (!$this->verificarApartamentoExiste($data['bloque'], $data['escalera'], $data['piso'], $data['apartamento'])) {
                $errores[] = 'La combinación de apartamento seleccionada no existe';
            }
        }

        // Cantidad de controles (sin límite según requerimiento)
        $controles = (int)($data['cantidad_controles'] ?? 0);
        if ($controles < 1) {
            $errores[] = 'Debe indicar al menos 1 control';
        }

        return $errores;
    }

    /**
     * Crear solicitud de registro
     *
     * @param array $formData Datos del formulario
     */
    private function crearSolicitudRegistro(array $formData): void
    {
        require_once __DIR__ . '/../models/SolicitudCambio.php';

        // Asignar contraseña temporal (123456) en lugar de solicitar contraseña al usuario
        $passwordTemporal = '123456';

        $datosUsuario = [
            'nombre_completo' => trim($formData['nombre'] . ' ' . $formData['apellido']),
            'email' => trim($formData['email']),
            'password' => password_hash($passwordTemporal, PASSWORD_BCRYPT),
            'password_temporal' => true, // Marcar como contraseña temporal
            'telefono' => trim($formData['telefono']),
            'bloque' => $formData['bloque'],
            'escalera' => $formData['escalera'],
            'piso' => $formData['piso'],
            'apartamento' => $formData['apartamento'],
            'cantidad_controles' => (int)$formData['cantidad_controles'],
            'comentarios' => trim($formData['comentarios'] ?? '')
        ];

        SolicitudCambio::create([
            'tipo_solicitud' => 'registro_nuevo_usuario',
            'datos_nuevo_usuario' => $datosUsuario,
            'motivo' => 'Solicitud de registro de nuevo usuario: ' . $datosUsuario['nombre_completo'],
            'estado' => 'pendiente'
        ]);

        writeLog("Nueva solicitud de registro creada: {$datosUsuario['email']}", 'info');
    }

    /**
     * Verificar que un apartamento existe
     *
     * @param string $bloque Bloque
     * @param string $escalera Escalera
     * @param string $piso Piso
     * @param string $apartamento Número de apartamento
     * @return bool
     */
    private function verificarApartamentoExiste(string $bloque, string $escalera, string $piso, string $apartamento): bool
    {
        $sql = "SELECT COUNT(*) as total FROM apartamentos
                WHERE bloque = ? AND escalera = ? AND piso = ? AND numero_apartamento = ?";

        $result = Database::fetchOne($sql, [$bloque, $escalera, $piso, $apartamento]);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Obtener bloques disponibles
     *
     * @return array
     */
    private function getBloquesDisponibles(): array
    {
        $sql = "SELECT DISTINCT bloque FROM apartamentos ORDER BY bloque";
        $results = Database::fetchAll($sql);
        return array_column($results, 'bloque');
    }

    /**
     * Obtener escaleras disponibles
     *
     * @param string|null $bloque Filtrar por bloque (opcional)
     * @return array
     */
    private function getEscalerasDisponibles(?string $bloque = null): array
    {
        if ($bloque) {
            $sql = "SELECT DISTINCT escalera FROM apartamentos WHERE bloque = ? ORDER BY escalera";
            $results = Database::fetchAll($sql, [$bloque]);
        } else {
            $sql = "SELECT DISTINCT escalera FROM apartamentos ORDER BY escalera";
            $results = Database::fetchAll($sql);
        }
        return array_column($results, 'escalera');
    }

    /**
     * Obtener pisos disponibles
     *
     * @return array
     */
    private function getPisosDisponibles(): array
    {
        $sql = "SELECT DISTINCT piso FROM apartamentos ORDER BY piso";
        $results = Database::fetchAll($sql);
        return array_column($results, 'piso');
    }
}
