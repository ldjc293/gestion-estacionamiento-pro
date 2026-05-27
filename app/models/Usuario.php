<?php
/**
 * Modelo Usuario
 *
 * Maneja operaciones CRUD y lógica de negocio para usuarios
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../helpers/CacheHelper.php';

class Usuario
{
    // Propiedades
    public $id;
    public $nombre_completo;
    public $cedula;
    public $email;
    public $password;
    public $telefono;
    public $rol;
    public $activo;
    public $intentos_fallidos;
    public $bloqueado_hasta;
    public $primer_acceso;
    public $password_temporal;
    public $perfil_completo;
    public $exonerado;
    public $motivo_exoneracion;
    public $fecha_registro;
    public $ultimo_acceso;

    // Propiedades adicionales para vistas
    public $controles;
    public $apartamento;

    /**
     * Buscar usuario por ID
     *
     * @param int $id ID del usuario
     * @return Usuario|null
     */
    public static function findById(int $id): ?Usuario
    {
        $cacheKey = "user_id_{$id}";
        $cached = CacheHelper::get($cacheKey);

        if ($cached) {
            return $cached;
        }

        $sql = "SELECT * FROM usuarios WHERE id = ?";
        $result = Database::fetchOne($sql, [$id]);

        $user = $result ? self::hydrate($result) : null;

        if ($user) {
            CacheHelper::set($cacheKey, $user, 300); // Cache por 5 minutos
        }

        return $user;
    }

    /**
     * Buscar usuario por email
     *
     * @param string $email Email del usuario
     * @return Usuario|null
     */
    public static function findByEmail(?string $email): ?Usuario
    {
        if (!$email) {
            return null;
        }

        $cacheKey = "user_email_{$email}";
        $cached = CacheHelper::get($cacheKey);

        if ($cached) {
            return $cached;
        }

        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $result = Database::fetchOne($sql, [$email]);

        $user = $result ? self::hydrate($result) : null;

        if ($user) {
            CacheHelper::set($cacheKey, $user, 300); // Cache por 5 minutos
        }

        return $user;
    }

    /**
     * Verificar credenciales de login
     *
     * @param string $email Email
     * @param string $password Contraseña en texto plano
     * @return array ['success' => bool, 'user' => Usuario|null, 'message' => string]
     */
    public static function verifyLogin(string $email, string $password): array
    {
        writeLog("Login attempt for email: $email", 'info');

        // PASO 1: Verificar rate limiting global por email (antes de buscar el usuario)
        $rateLimit = self::checkRateLimitByEmail($email);
        if ($rateLimit['blocked']) {
            writeLog("Login failed: Rate limited for email $email", 'warning');
            return [
                'success' => false,
                'user' => null,
                'message' => "Demasiados intentos fallidos. Intente en {$rateLimit['minutes_remaining']} minutos."
            ];
        }

        // PASO 2: Buscar usuario
        $usuario = self::findByEmail($email);

        if (!$usuario) {
            // Registrar intento fallido incluso si el usuario no existe (prevenir enumeración)
            self::registerFailedLoginAttempt($email);
            writeLog("Login failed: User not found for email $email", 'warning');

            return [
                'success' => false,
                'user' => null,
                'message' => 'Email o contraseña incorrectos'
            ];
        }

        writeLog("User found for email $email, ID: {$usuario->id}", 'info');

        // PASO 3: Verificar si está bloqueado temporalmente (tabla usuarios)
        if ($usuario->bloqueado_hasta && strtotime($usuario->bloqueado_hasta) > time()) {
            $minutosRestantes = ceil((strtotime($usuario->bloqueado_hasta) - time()) / 60);
            writeLog("Login failed: User blocked for email $email", 'warning');
            return [
                'success' => false,
                'user' => null,
                'message' => "Cuenta bloqueada temporalmente. Intente en $minutosRestantes minutos."
            ];
        }

        // PASO 4: Verificar si está activo
        if (!$usuario->activo) {
            writeLog("Login failed: User inactive for email $email", 'warning');
            return [
                'success' => false,
                'user' => null,
                'message' => 'Cuenta desactivada. Contacte al administrador.'
            ];
        }

        // PASO 5: Verificar contraseña
        if (!password_verify($password, $usuario->password)) {
            // Incrementar intentos fallidos en ambas tablas
            $usuario->incrementarIntentosFallidos();
            self::registerFailedLoginAttempt($email);
            writeLog("Login failed: Wrong password for email $email", 'warning');

            return [
                'success' => false,
                'user' => null,
                'message' => 'Email o contraseña incorrectos'
            ];
        }

        writeLog("Password verified for email $email", 'info');

        // PASO 6: Login exitoso - resetear intentos fallidos en ambas tablas
        $usuario->resetearIntentosFallidos();
        self::resetRateLimitByEmail($email);
        $usuario->actualizarUltimoAcceso();

        writeLog("Login successful for email $email", 'info');

        return [
            'success' => true,
            'user' => $usuario,
            'message' => 'Login exitoso'
        ];
    }

    /**
     * Crear nuevo usuario
     *
     * @param array $data Datos del usuario
     * @return int ID del usuario creado
     */
    public static function create(array $data): int
    {
        // Hash de contraseña si no viene ya hasheada
        $passwordHash = isset($data['is_hashed']) && $data['is_hashed'] 
            ? $data['password'] 
            : password_hash($data['password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuarios (
            nombre_completo, email, password, telefono, cedula, rol,
            activo, primer_acceso, password_temporal, perfil_completo, exonerado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $data['nombre_completo'],
            $data['email'],
            $passwordHash,
            $data['telefono'] ?? null,
            $data['cedula'] ?? null,
            $data['rol'] ?? 'cliente',
            $data['activo'] ?? true,
            $data['primer_acceso'] ?? true,
            $data['password_temporal'] ?? true,
            $data['perfil_completo'] ?? false,
            $data['exonerado'] ?? false
        ];

        $id = Database::insert($sql, $params);

        // Log de actividad
        self::logActividad($id, 'create', 'Usuario creado');

        return $id;
    }

    /**
     * Actualizar usuario
     *
     * @param array $data Datos a actualizar
     * @return bool
     */
    public function update(array $data): bool
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (property_exists($this, $key) && $key !== 'id') {
                $fields[] = "$key = ?";
                $params[] = $value;
                $this->$key = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $this->id;
        $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = ?";

        $result = Database::execute($sql, $params) > 0;

        if ($result) {
            // Invalidar caché
            CacheHelper::delete("user_id_{$this->id}");
            if ($this->email) {
                CacheHelper::delete("user_email_{$this->email}");
            }

            self::logActividad($this->id, 'update', 'Usuario actualizado');
        }

        return $result;
    }

    /**
     * Cambiar contraseña
     *
     * @param string $nuevaPassword Nueva contraseña
     * @return bool
     */
    public function cambiarPassword(string $nuevaPassword): bool
    {
        $passwordHash = password_hash($nuevaPassword, PASSWORD_BCRYPT);

        $sql = "UPDATE usuarios
                SET password = ?, password_temporal = FALSE
                WHERE id = ?";

        $result = Database::execute($sql, [$passwordHash, $this->id]) > 0;

        if ($result) {
            self::logActividad($this->id, 'password_change', 'Contraseña cambiada');
        }

        return $result;
    }

    /**
     * Incrementar intentos fallidos de login
     */
    private function incrementarIntentosFallidos(): void
    {
        $this->intentos_fallidos++;

        // Si alcanza el máximo, bloquear temporalmente
        if ($this->intentos_fallidos >= MAX_LOGIN_ATTEMPTS) {
            $bloqueadoHasta = date('Y-m-d H:i:s', strtotime('+' . LOGIN_LOCKOUT_TIME . ' minutes'));

            $sql = "UPDATE usuarios
                    SET intentos_fallidos = ?, bloqueado_hasta = ?
                    WHERE id = ?";

            Database::execute($sql, [$this->intentos_fallidos, $bloqueadoHasta, $this->id]);

            self::logActividad($this->id, 'account_locked', 'Cuenta bloqueada por intentos fallidos');
        } else {
            $sql = "UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?";
            Database::execute($sql, [$this->intentos_fallidos, $this->id]);
        }
    }

    /**
     * Resetear intentos fallidos
     */
    private function resetearIntentosFallidos(): void
    {
        $sql = "UPDATE usuarios
                SET intentos_fallidos = 0, bloqueado_hasta = NULL
                WHERE id = ?";

        Database::execute($sql, [$this->id]);
    }

    /**
     * Actualizar último acceso
     */
    private function actualizarUltimoAcceso(): void
    {
        $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
        Database::execute($sql, [$this->id]);
    }

    /**
     * Obtener todos los usuarios
     *
     * @param array $filters Filtros opcionales
     * @return array
     */
    public static function getAll(array $filters = []): array
    {
        $sql = "SELECT * FROM usuarios WHERE 1=1";
        $params = [];

        if (isset($filters['rol'])) {
            $sql .= " AND rol = ?";
            $params[] = $filters['rol'];
        }

        if (isset($filters['activo'])) {
            $sql .= " AND activo = ?";
            $params[] = $filters['activo'];
        }

        $sql .= " ORDER BY nombre_completo ASC";

        $results = Database::fetchAll($sql, $params);

        return array_map(fn($row) => self::hydrate($row), $results);
    }

    /**
     * Obtener clientes con morosidad
     *
     * @return array
     */
    public static function getClientesConMorosidad(): array
    {
        $sql = "SELECT * FROM vista_morosidad ORDER BY meses_pendientes DESC";
        return Database::fetchAll($sql);
    }

    /**
     * Desactivar usuario (soft delete)
     *
     * @return bool
     */
    public function desactivar(): bool
    {
        $sql = "UPDATE usuarios SET activo = FALSE WHERE id = ?";
        $result = Database::execute($sql, [$this->id]) > 0;

        if ($result) {
            self::logActividad($this->id, 'deactivate', 'Usuario desactivado');
        }

        return $result;
    }

    /**
     * Activar usuario
     *
     * @return bool
     */
    public function activar(): bool
    {
        $sql = "UPDATE usuarios SET activo = TRUE WHERE id = ?";
        $result = Database::execute($sql, [$this->id]) > 0;

        if ($result) {
            self::logActividad($this->id, 'activate', 'Usuario activado');
        }

        return $result;
    }

    /**
     * Marcar perfil como completo
     *
     * @return bool
     */
    public function marcarPerfilCompleto(): bool
    {
        $sql = "UPDATE usuarios SET perfil_completo = TRUE WHERE id = ?";
        return Database::execute($sql, [$this->id]) > 0;
    }

    /**
     * Marcar primer acceso como completado
     *
     * @return bool
     */
    public function marcarPrimerAccesoCompletado(): bool
    {
        $sql = "UPDATE usuarios SET primer_acceso = FALSE WHERE id = ?";
        return Database::execute($sql, [$this->id]) > 0;
    }

    /**
     * Verificar si tiene un permiso específico
     *
     * @param string $permission Permiso a verificar
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return hasPermission($permission, $this->rol);
    }

    /**
     * Registrar actividad en logs
     *
     * @param int $usuarioId ID del usuario
     * @param string $accion Acción realizada
     * @param string $descripcion Descripción
     */
    private static function logActividad(int $usuarioId, string $accion, string $descripcion): void
    {
        $sql = "INSERT INTO logs_actividad (usuario_id, accion, modulo, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)";

        $params = [
            $usuarioId,
            $descripcion, // La descripción va en 'accion'
            'usuarios',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ];

        Database::execute($sql, $params);
    }

    /**
     * Hidratar objeto Usuario desde array
     *
     * @param array $data Datos del usuario
     * @return Usuario
     */
    private static function hydrate(array $data): Usuario
    {
        $usuario = new self();

        foreach ($data as $key => $value) {
            if (property_exists($usuario, $key)) {
                $usuario->$key = $value;
            }
        }

        return $usuario;
    }

    /**
     * Convertir a array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre_completo' => $this->nombre_completo,
            'cedula' => $this->cedula,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'rol' => $this->rol,
            'activo' => $this->activo,
            'intentos_fallidos' => $this->intentos_fallidos,
            'bloqueado_hasta' => $this->bloqueado_hasta,
            'primer_acceso' => $this->primer_acceso,
            'password_temporal' => $this->password_temporal,
            'perfil_completo' => $this->perfil_completo,
            'exonerado' => $this->exonerado,
            'motivo_exoneracion' => $this->motivo_exoneracion,
            'fecha_registro' => $this->fecha_registro,
            'ultimo_acceso' => $this->ultimo_acceso
        ];
    }

    // ============================================================================
    // MÉTODOS DE RATE LIMITING POR IP/EMAIL
    // ============================================================================

    /**
     * Verificar si un email tiene demasiados intentos fallidos
     * (Rate limiting global por email, incluso si el email no existe)
     *
     * @param string $email Email a verificar
     * @return array ['blocked' => bool, 'minutes_remaining' => int, 'attempts' => int]
     */
    public static function checkRateLimitByEmail(string $email): array
    {
        // Limpiar intentos antiguos (más de 1 hora)
        // PostgreSQL: user NOW() - INTERVAL '1 HOUR'
        $sql = "DELETE FROM login_intentos
                WHERE ultimo_intento < NOW() - INTERVAL '1 HOUR'";
        Database::execute($sql);

        // Obtener intentos actuales
        $sql = "SELECT intentos, bloqueado_hasta
                FROM login_intentos
                WHERE email = ?
                LIMIT 1";

        $result = Database::fetchOne($sql, [$email]);

        if (!$result) {
            return ['blocked' => false, 'minutes_remaining' => 0, 'attempts' => 0];
        }

        // Verificar si está bloqueado
        if ($result['bloqueado_hasta'] && strtotime($result['bloqueado_hasta']) > time()) {
            $minutosRestantes = ceil((strtotime($result['bloqueado_hasta']) - time()) / 60);
            return [
                'blocked' => true,
                'minutes_remaining' => $minutosRestantes,
                'attempts' => $result['intentos']
            ];
        }

        // Si el bloqueo expiró, resetear
        if ($result['bloqueado_hasta'] && strtotime($result['bloqueado_hasta']) <= time()) {
            self::resetRateLimitByEmail($email);
            return ['blocked' => false, 'minutes_remaining' => 0, 'attempts' => 0];
        }

        return [
            'blocked' => false,
            'minutes_remaining' => 0,
            'attempts' => $result['intentos']
        ];
    }

    /**
     * Registrar intento fallido de login por email
     *
     * @param string $email Email que intentó login
     */
    public static function registerFailedLoginAttempt(string $email): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // PostgreSQL Syntax for Upsert
        $sql = "INSERT INTO login_intentos (email, ip_address, user_agent, exitoso, intentos, ultimo_intento, bloqueado_hasta)
                VALUES (?, ?, ?, FALSE, 1, NOW(), NULL)
                ON CONFLICT (email) DO UPDATE SET
                    intentos = login_intentos.intentos + 1,
                    ultimo_intento = NOW(),
                    exitoso = FALSE,
                    ip_address = EXCLUDED.ip_address,
                    user_agent = EXCLUDED.user_agent,
                    bloqueado_hasta = NULL"; 

        Database::execute($sql, [$email, $ip, $userAgent]);

        // Después de registrar el intento, verificar si se debe bloquear
        $sql = "SELECT intentos FROM login_intentos WHERE email = ?";
        $result = Database::fetchOne($sql, [$email]);

        if ($result && $result['intentos'] >= MAX_LOGIN_ATTEMPTS) {
            // Bloquear
            $bloqueadoHasta = date('Y-m-d H:i:s', strtotime('+' . LOGIN_LOCKOUT_TIME . ' minutes'));
            $sql = "UPDATE login_intentos SET bloqueado_hasta = ? WHERE email = ?";
            Database::execute($sql, [$bloqueadoHasta, $email]);

            writeLog("Rate limit: Email $email bloqueado por " . LOGIN_LOCKOUT_TIME . " minutos", 'warning');
        }
    }

    /**
     * Resetear rate limit por email (después de login exitoso)
     *
     * @param string $email Email a resetear
     */
    public static function resetRateLimitByEmail(string $email): void
    {
        $sql = "DELETE FROM login_intentos WHERE email = ?";
        Database::execute($sql, [$email]);
    }

    /**
     * Buscar cliente por email, nombre, cédula o bloque
     *
     * @param string $criterio Email, nombre, cédula o bloque del cliente
     * @return Usuario|null
     */
    public static function buscarCliente(string $criterio): ?Usuario
    {
        $sql = "SELECT u.* FROM usuarios u
                LEFT JOIN apartamento_usuario au ON au.usuario_id = u.id AND au.activo = TRUE
                LEFT JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE u.rol = 'cliente'
                AND u.activo = TRUE
                AND (
                    u.email = ?
                    OR u.nombre_completo ILIKE ?
                    OR u.cedula ILIKE ?
                    OR a.bloque ILIKE ?
                    OR CONCAT(a.bloque, '-', a.escalera, '-', a.numero_apartamento) ILIKE ?
                )
                LIMIT 1";

        // Buscar por coincidencia exacta en email y por coincidencia parcial en los demás campos
        $result = Database::fetchOne($sql, [$criterio, "%$criterio%", "%$criterio%", "%$criterio%", "%$criterio%"]);

        if ($result) {
            return self::hydrate($result);
        }

        return null;
    }

    /**
     * Buscar clientes múltiples para autocompletar
     *
     * @param string $criterio Criterio de búsqueda
     * @param int $limit Límite de resultados
     * @return array Lista de clientes encontrados
     */
    public static function buscarClientes(string $criterio, int $limit = 10): array
    {
        $sql = "SELECT DISTINCT u.id, u.nombre_completo, u.email, u.cedula,
                       CONCAT(a.bloque, '-', a.escalera, '-', a.numero_apartamento) as apartamento
                FROM usuarios u
                LEFT JOIN apartamento_usuario au ON au.usuario_id = u.id AND au.activo = TRUE
                LEFT JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE u.rol = 'cliente'
                AND u.activo = TRUE
                AND (
                    u.email = ?
                    OR u.nombre_completo ILIKE ?
                    OR u.cedula ILIKE ?
                    OR a.bloque ILIKE ?
                    OR CONCAT(a.bloque, '-', a.escalera, '-', a.numero_apartamento) ILIKE ?
                )
                ORDER BY u.nombre_completo
                LIMIT ?";

        $params = [
            $criterio,
            "%$criterio%",
            "%$criterio%",
            "%$criterio%",
            "%$criterio%",
            $limit
        ];

        return Database::fetchAll($sql, $params);
    }

    /**
     * Obtener clientes con información de controles asignados
     *
     * @param array $filters Filtros opcionales
     * @return array Lista de clientes con conteo de controles
     */
    public static function getClientesConControles(array $filters = []): array
    {
        $sql = "SELECT u.id, u.nombre_completo, u.email, u.cedula, u.activo,
                       CONCAT(a.bloque, '-', a.escalera, '-', a.numero_apartamento) as apartamento,
                       a.bloque,
                       COUNT(c.id) as total_controles,
                       COUNT(CASE WHEN c.estado = 'activo' THEN 1 END) as controles_activos,
                       COUNT(CASE WHEN c.estado = 'bloqueado' THEN 1 END) as controles_bloqueados,
                       COUNT(CASE WHEN c.estado = 'suspendido' THEN 1 END) as controles_suspendidos,
                       COUNT(CASE WHEN c.estado = 'desactivado' THEN 1 END) as controles_desactivados,
                       COUNT(CASE WHEN c.estado = 'perdido' THEN 1 END) as controles_perdidos
                FROM usuarios u
                LEFT JOIN apartamento_usuario au ON au.usuario_id = u.id AND au.activo = TRUE
                LEFT JOIN apartamentos a ON a.id = au.apartamento_id
                LEFT JOIN controles_estacionamiento c ON c.apartamento_usuario_id = au.id
                WHERE u.rol = 'cliente'
                AND u.activo = TRUE";

        $params = [];

        if (isset($filters['bloque'])) {
            $sql .= " AND a.bloque = ?";
            $params[] = $filters['bloque'];
        }

        if (isset($filters['busqueda'])) {
            $sql .= " AND (u.nombre_completo ILIKE ? OR u.email ILIKE ? OR u.cedula ILIKE ? OR CONCAT(a.bloque, '-', a.escalera, '-', a.numero_apartamento) ILIKE ?)";
            $busqueda = "%{$filters['busqueda']}%";
            $params = array_merge($params, [$busqueda, $busqueda, $busqueda, $busqueda]);
        }

        $sql .= " GROUP BY u.id, u.nombre_completo, u.email, u.cedula, u.activo, a.bloque, a.numero_apartamento
                  ORDER BY u.nombre_completo";

        return Database::fetchAll($sql, $params);
    }
}
