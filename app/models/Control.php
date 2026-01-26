<?php
/**
 * Modelo Control
 *
 * Maneja los 500 controles de estacionamiento (250 posiciones × 2 receptores)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';

class Control
{
    public $id;
    public $apartamento_usuario_id;
    public $posicion_numero;
    public $receptor;
    public $numero_control_completo;
    public $estado;
    public $motivo_estado;
    public $fecha_estado;
    public $aprobado_por;
    public $fecha_asignacion;

    /**
     * Buscar control por ID
     *
     * @param int $id ID del control
     * @return Control|null
     */
    public static function findById(int $id): ?Control
    {
        $sql = "SELECT * FROM controles_estacionamiento WHERE id = ?";
        $result = Database::fetchOne($sql, [$id]);

        return $result ? self::hydrate($result) : null;
    }

    /**
     * Buscar control por número completo (Ej: 15A, 250B)
     *
     * @param string $numeroCompleto Número de control
     * @return Control|null
     */
    public static function findByNumero(string $numeroCompleto): ?Control
    {
        $sql = "SELECT * FROM controles_estacionamiento WHERE numero_control_completo = ?";
        $result = Database::fetchOne($sql, [$numeroCompleto]);

        return $result ? self::hydrate($result) : null;
    }

    /**
     * Obtener controles de un apartamento_usuario
     *
     * @param int $apartamentoUsuarioId ID de apartamento_usuario
     * @return array
     */
    public static function getByApartamentoUsuario(int $apartamentoUsuarioId): array
    {
        $sql = "SELECT * FROM controles_estacionamiento
                WHERE apartamento_usuario_id = ?
                ORDER BY posicion_numero, receptor";

        $results = Database::fetchAll($sql, [$apartamentoUsuarioId]);

        return array_map(fn($row) => self::hydrate($row), $results);
    }

    /**
     * Obtener controles vacíos (disponibles para asignar)
     *
     * @param string|null $receptor Filtrar por receptor (A o B)
     * @return array
     */
    public static function getVacios(?string $receptor = null): array
    {
        $sql = "SELECT * FROM controles_estacionamiento
                WHERE estado = 'vacio'
                  AND apartamento_usuario_id IS NULL";

        $params = [];

        if ($receptor) {
            $sql .= " AND receptor = ?";
            $params[] = $receptor;
        }

        $sql .= " ORDER BY posicion_numero, receptor";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Obtener todos los controles con información del propietario
     *
     * @param array $filters Filtros opcionales
     * @return array
     */
    public static function getAll(array $filters = []): array
    {
        $sql = "SELECT c.*,
                       u.nombre_completo as propietario_nombre,
                       CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento
                FROM controles_estacionamiento c
                LEFT JOIN apartamento_usuario au ON au.id = c.apartamento_usuario_id
                LEFT JOIN usuarios u ON u.id = au.usuario_id
                LEFT JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE 1=1";

        $params = [];

        if (isset($filters['estado'])) {
            $sql .= " AND c.estado = ?";
            $params[] = $filters['estado'];
        }

        if (isset($filters['receptor'])) {
            $sql .= " AND c.receptor = ?";
            $params[] = $filters['receptor'];
        }

        if (isset($filters['posicion_numero'])) {
            $sql .= " AND c.posicion_numero = ?";
            $params[] = $filters['posicion_numero'];
        }

        $sql .= " ORDER BY c.posicion_numero, c.receptor";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Asignar control a un apartamento_usuario
     *
     * @param int $apartamentoUsuarioId ID de apartamento_usuario
     * @param int $aprobadoPor ID del usuario que aprueba
     * @return bool
     */
    public function asignar(int $apartamentoUsuarioId, int $aprobadoPor): bool
    {
        $sql = "UPDATE controles_estacionamiento
                SET apartamento_usuario_id = ?,
                    estado = 'activo',
                    motivo_estado = 'Asignado',
                    fecha_asignacion = NOW(),
                    fecha_estado = NOW(),
                    aprobado_por = ?
                WHERE id = ? AND estado = 'vacio'";

        $result = Database::execute($sql, [$apartamentoUsuarioId, $aprobadoPor, $this->id]) > 0;

        if ($result) {
            writeLog("Control {$this->numero_control_completo} asignado", 'info');
        }

        return $result;
    }

    /**
     * Desasignar control (marcar como vacío)
     *
     * @param string $motivo Motivo de la desasignación
     * @param int $aprobadoPor ID del usuario que aprueba
     * @return bool
     */
    public function desasignar(string $motivo, int $aprobadoPor): bool
    {
        $sql = "UPDATE controles_estacionamiento
                SET apartamento_usuario_id = NULL,
                    estado = 'vacio',
                    motivo_estado = ?,
                    fecha_estado = NOW(),
                    aprobado_por = ?
                WHERE id = ?";

        $result = Database::execute($sql, [$motivo, $aprobadoPor, $this->id]) > 0;

        if ($result) {
            writeLog("Control {$this->numero_control_completo} desasignado - Motivo: $motivo", 'info');
        }

        return $result;
    }

    /**
     * Cambiar estado del control
     *
     * @param string $nuevoEstado Estado (suspendido, desactivado, perdido, bloqueado)
     * @param string $motivo Motivo del cambio
     * @param int $aprobadoPor ID del usuario que aprueba
     * @return bool
     */
    public function cambiarEstado(string $nuevoEstado, string $motivo, int $aprobadoPor): bool
    {
        $sql = "UPDATE controles_estacionamiento
                SET estado = ?,
                    motivo_estado = ?,
                    fecha_estado = NOW(),
                    aprobado_por = ?
                WHERE id = ?";

        $result = Database::execute($sql, [$nuevoEstado, $motivo, $aprobadoPor, $this->id]) > 0;

        if ($result) {
            writeLog("Control {$this->numero_control_completo} cambió a estado: $nuevoEstado", 'info');
        }

        return $result;
    }

    /**
     * Bloquear control por morosidad
     *
     * @param string $motivo Motivo del bloqueo
     * @return bool
     */
    public function bloquear(string $motivo = 'Bloqueado por morosidad'): bool
    {
        $sql = "UPDATE controles_estacionamiento
                SET estado = 'bloqueado',
                    motivo_estado = ?,
                    fecha_estado = NOW()
                WHERE id = ?";

        return Database::execute($sql, [$motivo, $this->id]) > 0;
    }

    /**
     * Desbloquear control (reconexión)
     *
     * @param int $aprobadoPor ID del usuario que aprueba
     * @return bool
     */
    public function desbloquear(int $aprobadoPor): bool
    {
        $sql = "UPDATE controles_estacionamiento
                SET estado = 'activo',
                    motivo_estado = 'Reconectado después de pago',
                    fecha_estado = NOW(),
                    aprobado_por = ?
                WHERE id = ? AND estado = 'bloqueado'";

        $result = Database::execute($sql, [$aprobadoPor, $this->id]) > 0;

        if ($result) {
            writeLog("Control {$this->numero_control_completo} desbloqueado/reconectado", 'info');
        }

        return $result;
    }

    /**
     * Crear controles iniciales (500 controles: 250 posiciones × 2 receptores)
     * Se ejecuta una sola vez durante la instalación
     *
     * @return int Número de controles creados
     */
    public static function crearControlesIniciales(): int
    {
        try {
            Database::beginTransaction();

            $creados = 0;

            for ($posicion = 1; $posicion <= TOTAL_POSICIONES; $posicion++) {
                foreach (RECEPTORES as $receptor) {
                    $numeroCompleto = $posicion . $receptor;

                    // Verificar si ya existe
                    if (self::findByNumero($numeroCompleto)) {
                        continue;
                    }

                    $sql = "INSERT INTO controles_estacionamiento
                            (posicion_numero, receptor, numero_control_completo, estado)
                            VALUES (?, ?, ?, 'vacio')";

                    Database::execute($sql, [$posicion, $receptor, $numeroCompleto]);
                    $creados++;
                }
            }

            Database::commit();

            writeLog("Controles iniciales creados: $creados", 'info');

            return $creados;

        } catch (Exception $e) {
            Database::rollback();
            writeLog("Error al crear controles iniciales: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Obtener estadísticas de controles
     *
     * @return array
     */
    public static function getEstadisticas(): array
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    COUNT(CASE WHEN estado = 'activo' THEN 1 END) as activos,
                    COUNT(CASE WHEN estado = 'vacio' THEN 1 END) as vacios,
                    COUNT(CASE WHEN estado = 'bloqueado' THEN 1 END) as bloqueados,
                    COUNT(CASE WHEN estado = 'suspendido' THEN 1 END) as suspendidos,
                    COUNT(CASE WHEN estado = 'desactivado' THEN 1 END) as desactivados,
                    COUNT(CASE WHEN estado = 'perdido' THEN 1 END) as perdidos
                FROM controles_estacionamiento";

        return Database::fetchOne($sql) ?: [];
    }

    /**
     * Obtener mapa de controles (posiciones 1-250 con receptores A y B)
     *
     * @return array Array agrupado por posición
     */
    public static function getMapaControles(): array
    {
        $sql = "SELECT c.*,
                       u.nombre_completo as propietario_nombre,
                       CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento
                FROM controles_estacionamiento c
                LEFT JOIN apartamento_usuario au ON au.id = c.apartamento_usuario_id
                LEFT JOIN usuarios u ON u.id = au.usuario_id
                LEFT JOIN apartamentos a ON a.id = au.apartamento_id
                ORDER BY c.posicion_numero, c.receptor";

        $controles = Database::fetchAll($sql);

        // Agrupar por posición
        $mapa = [];
        foreach ($controles as $control) {
            $posicion = $control['posicion_numero'];
            if (!isset($mapa[$posicion])) {
                $mapa[$posicion] = [];
            }
            $mapa[$posicion][$control['receptor']] = $control;
        }

        return $mapa;
    }

    /**
     * Buscar controles por criterios
     *
     * @param string $criterio Texto a buscar
     * @return array
     */
    public static function buscar(string $criterio): array
    {
        $sql = "SELECT c.*,
                       u.nombre_completo as propietario_nombre,
                       u.email as propietario_email,
                       u.cedula as propietario_cedula,
                       CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento,
                       a.bloque
                FROM controles_estacionamiento c
                LEFT JOIN apartamento_usuario au ON au.id = c.apartamento_usuario_id
                LEFT JOIN usuarios u ON u.id = au.usuario_id
                LEFT JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE c.numero_control_completo LIKE ?
                   OR u.nombre_completo LIKE ?
                   OR u.email LIKE ?
                   OR u.cedula LIKE ?
                   OR CONCAT(a.bloque, '-', a.numero_apartamento) LIKE ?
                   OR a.bloque LIKE ?
                ORDER BY c.posicion_numero, c.receptor";

        $criterio = "%$criterio%";
        return Database::fetchAll($sql, [$criterio, $criterio, $criterio, $criterio, $criterio, $criterio]);
    }

    /**
     * Obtener controles con información de propietarios para vista del operador
     *
     * @param array $filters Filtros opcionales
     * @return array
     */
    public static function getControlesConPropietarios(array $filters = []): array
    {
        $sql = "SELECT c.*,
                       u.nombre_completo as propietario_nombre,
                       u.email as propietario_email,
                       u.cedula as propietario_cedula,
                       CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento,
                       a.bloque
                FROM controles_estacionamiento c
                LEFT JOIN apartamento_usuario au ON au.id = c.apartamento_usuario_id
                LEFT JOIN usuarios u ON u.id = au.usuario_id
                LEFT JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE 1=1";

        $params = [];

        if (isset($filters['estado']) && !empty($filters['estado'])) {
            $sql .= " AND c.estado = ?";
            $params[] = $filters['estado'];
        }

        if (isset($filters['receptor']) && !empty($filters['receptor'])) {
            $sql .= " AND c.receptor = ?";
            $params[] = $filters['receptor'];
        }

        if (isset($filters['bloque']) && !empty($filters['bloque'])) {
            $sql .= " AND a.bloque = ?";
            $params[] = $filters['bloque'];
        }

        if (isset($filters['busqueda']) && !empty($filters['busqueda'])) {
            $sql .= " AND (u.nombre_completo LIKE ? OR u.email LIKE ? OR u.cedula LIKE ? OR c.numero_control_completo LIKE ? OR CONCAT(a.bloque, '-', a.numero_apartamento) LIKE ?)";
            $busqueda = "%{$filters['busqueda']}%";
            $params = array_merge($params, [$busqueda, $busqueda, $busqueda, $busqueda, $busqueda]);
        }

        $sql .= " ORDER BY c.posicion_numero, c.receptor";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Hidratar objeto desde array
     *
     * @param array $data Datos
     * @return Control
     */
    private static function hydrate(array $data): Control
    {
        $control = new self();

        foreach ($data as $key => $value) {
            if (property_exists($control, $key)) {
                $control->$key = $value;
            }
        }

        return $control;
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
            'posicion_numero' => $this->posicion_numero,
            'receptor' => $this->receptor,
            'numero_control_completo' => $this->numero_control_completo,
            'estado' => $this->estado,
            'motivo_estado' => $this->motivo_estado,
            'fecha_asignacion' => $this->fecha_asignacion
        ];
    }
}
