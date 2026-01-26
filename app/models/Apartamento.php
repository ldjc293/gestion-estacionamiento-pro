<?php
/**
 * Modelo Apartamento
 *
 * Maneja apartamentos de los bloques 27-32
 */

require_once __DIR__ . '/../../config/database.php';

class Apartamento
{
    public $id;
    public $bloque;
    public $escalera;
    public $piso;
    public $numero_apartamento;
    public $activo;
    public $fecha_creacion;

    /**
     * Buscar apartamento por ID
     *
     * @param int $id ID del apartamento
     * @return Apartamento|null
     */
    public static function findById(int $id): ?Apartamento
    {
        $sql = "SELECT * FROM apartamentos WHERE id = ?";
        $result = Database::fetchOne($sql, [$id]);

        return $result ? self::hydrate($result) : null;
    }

    /**
     * Buscar apartamento por datos únicos
     *
     * @param string $bloque Bloque (27-32)
     * @param string $escalera Escalera (A, B, C)
     * @param int $piso Piso
     * @param string $numeroApartamento Número de apartamento
     * @return Apartamento|null
     */
    public static function findByDatos(string $bloque, string $escalera, int $piso, string $numeroApartamento): ?Apartamento
    {
        $sql = "SELECT * FROM apartamentos
                WHERE bloque = ? AND escalera = ? AND piso = ? AND numero_apartamento = ?";

        $result = Database::fetchOne($sql, [$bloque, $escalera, $piso, $numeroApartamento]);

        return $result ? self::hydrate($result) : null;
    }

    /**
     * Crear nuevo apartamento
     *
     * @param array $data Datos del apartamento
     * @return int ID del apartamento creado
     */
    public static function create(array $data): int
    {
        // Verificar si ya existe
        $existente = self::findByDatos(
            $data['bloque'],
            $data['escalera'],
            $data['piso'],
            $data['numero_apartamento']
        );

        if ($existente) {
            return $existente->id;
        }

        $sql = "INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo)
                VALUES (?, ?, ?, ?, ?)";

        $params = [
            $data['bloque'],
            $data['escalera'],
            $data['piso'],
            $data['numero_apartamento'],
            $data['activo'] ?? true
        ];

        $id = Database::insert($sql, $params);

        writeLog("Apartamento creado: {$data['bloque']}-{$data['numero_apartamento']}", 'info');

        return $id;
    }

    /**
     * Obtener todos los apartamentos
     *
     * @param array $filters Filtros opcionales
     * @return array
     */
    public static function getAll(array $filters = []): array
    {
        $sql = "SELECT * FROM apartamentos WHERE 1=1";
        $params = [];

        if (isset($filters['bloque'])) {
            $sql .= " AND bloque = ?";
            $params[] = $filters['bloque'];
        }

        if (isset($filters['activo'])) {
            $sql .= " AND activo = ?";
            $params[] = $filters['activo'];
        }

        $sql .= " ORDER BY bloque, escalera, piso, numero_apartamento";

        $results = Database::fetchAll($sql, $params);

        return array_map(fn($row) => self::hydrate($row), $results);
    }

    /**
     * Obtener apartamentos con información de residentes
     *
     * @return array
     */
    public static function getAllWithResidentes(): array
    {
        $sql = "SELECT a.*,
                       u.id as usuario_id,
                       u.nombre_completo as residente_nombre,
                       au.cantidad_controles,
                       au.activo as asignacion_activa
                FROM apartamentos a
                LEFT JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = TRUE
                LEFT JOIN usuarios u ON u.id = au.usuario_id
                WHERE a.activo = TRUE
                ORDER BY a.bloque, a.escalera, a.piso, a.numero_apartamento";

        return Database::fetchAll($sql);
    }

    /**
     * Obtener apartamentos con información de residentes (paginado y con filtros)
     *
     * @param array $filters Filtros ['busqueda', 'bloque', 'escalera']
     * @param int $limit Cantidad de registros por página
     * @param int $offset Desplazamiento para paginación
     * @return array
     */
    public static function getAllWithResidentesPaginado(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT a.*,
                       u.id as usuario_id,
                       u.nombre_completo as residente_nombre,
                       au.cantidad_controles,
                       au.activo as asignacion_activa
                FROM apartamentos a
                LEFT JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = TRUE
                LEFT JOIN usuarios u ON u.id = au.usuario_id
                WHERE a.activo = TRUE";

        $params = [];

        // Filtro de búsqueda (busca en bloque, escalera, número de apartamento y nombre de residente)
        if (!empty($filters['busqueda'])) {
            $sql .= " AND (
                a.bloque LIKE ? OR
                a.escalera LIKE ? OR
                a.numero_apartamento LIKE ? OR
                u.nombre_completo LIKE ?
            )";
            $busqueda = "%{$filters['busqueda']}%";
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
        }

        // Filtro por bloque
        if (!empty($filters['bloque'])) {
            $sql .= " AND a.bloque = ?";
            $params[] = $filters['bloque'];
        }

        // Filtro por escalera
        if (!empty($filters['escalera'])) {
            $sql .= " AND a.escalera = ?";
            $params[] = $filters['escalera'];
        }

        $sql .= " ORDER BY a.bloque, a.escalera, a.piso, a.numero_apartamento
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;

        return Database::fetchAll($sql, $params);
    }

    /**
     * Contar apartamentos con filtros aplicados
     *
     * @param array $filters Filtros ['busqueda', 'bloque', 'escalera']
     * @return int Total de apartamentos que coinciden con los filtros
     */
    public static function countWithFilters(array $filters = []): int
    {
        $sql = "SELECT COUNT(DISTINCT a.id) as total
                FROM apartamentos a
                LEFT JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = TRUE
                LEFT JOIN usuarios u ON u.id = au.usuario_id
                WHERE a.activo = TRUE";

        $params = [];

        // Filtro de búsqueda
        if (!empty($filters['busqueda'])) {
            $sql .= " AND (
                a.bloque LIKE ? OR
                a.escalera LIKE ? OR
                a.numero_apartamento LIKE ? OR
                u.nombre_completo LIKE ?
            )";
            $busqueda = "%{$filters['busqueda']}%";
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
        }

        // Filtro por bloque
        if (!empty($filters['bloque'])) {
            $sql .= " AND a.bloque = ?";
            $params[] = $filters['bloque'];
        }

        // Filtro por escalera
        if (!empty($filters['escalera'])) {
            $sql .= " AND a.escalera = ?";
            $params[] = $filters['escalera'];
        }

        $result = Database::fetchOne($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Obtener bloques únicos
     *
     * @return array Lista de bloques disponibles
     */
    public static function getBloques(): array
    {
        $sql = "SELECT DISTINCT bloque FROM apartamentos WHERE activo = TRUE ORDER BY bloque";
        $results = Database::fetchAll($sql);
        return array_column($results, 'bloque');
    }

    /**
     * Obtener escaleras únicas
     *
     * @return array Lista de escaleras disponibles
     */
    public static function getEscaleras(): array
    {
        $sql = "SELECT DISTINCT escalera FROM apartamentos WHERE activo = TRUE ORDER BY escalera";
        $results = Database::fetchAll($sql);
        return array_column($results, 'escalera');
    }


    /**
     * Asignar usuario al apartamento
     *
     * @param int $usuarioId ID del usuario
     * @param int $cantidadControles Cantidad de controles
     * @return int ID de la asignación (apartamento_usuario)
     */
    public function asignarUsuario(int $usuarioId, int $cantidadControles = 0): int
    {
        // Verificar si ya existe una asignación activa
        $sqlCheck = "SELECT id FROM apartamento_usuario
                     WHERE apartamento_id = ? AND usuario_id = ? AND activo = TRUE";
        $existente = Database::fetchOne($sqlCheck, [$this->id, $usuarioId]);

        if ($existente) {
            return $existente['id'];
        }

        $sql = "INSERT INTO apartamento_usuario (apartamento_id, usuario_id, cantidad_controles, activo)
                VALUES (?, ?, ?, TRUE)";

        $id = Database::insert($sql, [$this->id, $usuarioId, $cantidadControles]);

        writeLog("Usuario $usuarioId asignado a apartamento {$this->getIdentificador()}", 'info');

        return $id;
    }

    /**
     * Desasignar usuario del apartamento
     *
     * @param int $usuarioId ID del usuario
     * @return bool
     */
    public function desasignarUsuario(int $usuarioId): bool
    {
        $sql = "UPDATE apartamento_usuario
                SET activo = FALSE
                WHERE apartamento_id = ? AND usuario_id = ? AND activo = TRUE";

        $result = Database::execute($sql, [$this->id, $usuarioId]) > 0;

        if ($result) {
            writeLog("Usuario $usuarioId desasignado de apartamento {$this->getIdentificador()}", 'info');
        }

        return $result;
    }

    /**
     * Obtener residentes del apartamento
     *
     * @return array
     */
    public function getResidentes(): array
    {
        $sql = "SELECT u.*, au.cantidad_controles, au.fecha_asignacion
                FROM usuarios u
                JOIN apartamento_usuario au ON au.usuario_id = u.id
                WHERE au.apartamento_id = ? AND au.activo = TRUE";

        return Database::fetchAll($sql, [$this->id]);
    }

    /**
     * Obtener apartamento_usuario activo
     *
     * @return array|null
     */
    public function getApartamentoUsuarioActivo(): ?array
    {
        $sql = "SELECT * FROM apartamento_usuario
                WHERE apartamento_id = ? AND activo = TRUE
                LIMIT 1";

        return Database::fetchOne($sql, [$this->id]) ?: null;
    }

    /**
     * Obtener estadísticas de apartamentos
     *
     * @return array
     */
    public static function getEstadisticas(): array
    {
        $sql = "SELECT
                    COUNT(DISTINCT a.id) as total_apartamentos,
                    COUNT(DISTINCT CASE WHEN au.activo = TRUE THEN a.id END) as apartamentos_ocupados,
                    COUNT(DISTINCT CASE WHEN au.activo IS NULL THEN a.id END) as apartamentos_vacios,
                    COUNT(DISTINCT a.bloque) as total_bloques,
                    SUM(CASE WHEN au.activo = TRUE THEN au.cantidad_controles ELSE 0 END) as total_controles_asignados
                FROM apartamentos a
                LEFT JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = TRUE
                WHERE a.activo = TRUE";

        return Database::fetchOne($sql) ?: [];
    }

    /**
     * Obtener apartamentos vacíos (sin residentes)
     *
     * @return array
     */
    public static function getVacios(): array
    {
        $sql = "SELECT a.*
                FROM apartamentos a
                LEFT JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = TRUE
                WHERE a.activo = TRUE
                  AND au.id IS NULL
                ORDER BY a.bloque, a.escalera, a.piso, a.numero_apartamento";

        $results = Database::fetchAll($sql);

        return array_map(fn($row) => self::hydrate($row), $results);
    }

    /**
     * Obtener apartamentos por bloque
     *
     * @param string $bloque Bloque (27-32)
     * @return array
     */
    public static function getByBloque(string $bloque): array
    {
        $sql = "SELECT * FROM apartamentos
                WHERE bloque = ? AND activo = TRUE
                ORDER BY escalera, piso, numero_apartamento";

        $results = Database::fetchAll($sql, [$bloque]);

        return array_map(fn($row) => self::hydrate($row), $results);
    }

    /**
     * Buscar apartamentos
     *
     * @param string $criterio Criterio de búsqueda
     * @return array
     */
    public static function buscar(string $criterio): array
    {
        $sql = "SELECT a.*,
                       u.nombre_completo as residente_nombre
                FROM apartamentos a
                LEFT JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = TRUE
                LEFT JOIN usuarios u ON u.id = au.usuario_id
                WHERE CONCAT(a.bloque, '-', a.numero_apartamento) LIKE ?
                   OR u.nombre_completo LIKE ?
                   OR a.numero_apartamento LIKE ?
                ORDER BY a.bloque, a.numero_apartamento";

        $criterio = "%$criterio%";
        return Database::fetchAll($sql, [$criterio, $criterio, $criterio]);
    }

    /**
     * Actualizar apartamento
     *
     * @param array $data Datos a actualizar
     * @return bool
     */
    public function update(array $data): bool
    {
        $fields = [];
        $params = [];

        $allowedFields = ['bloque', 'escalera', 'piso', 'numero_apartamento', 'activo'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = ?";
                $params[] = $value;
                $this->$key = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $this->id;
        $sql = "UPDATE apartamentos SET " . implode(', ', $fields) . " WHERE id = ?";

        return Database::execute($sql, $params) > 0;
    }

    /**
     * Desactivar apartamento
     *
     * @return bool
     */
    public function desactivar(): bool
    {
        $sql = "UPDATE apartamentos SET activo = FALSE WHERE id = ?";
        return Database::execute($sql, [$this->id]) > 0;
    }

    /**
     * Activar apartamento
     *
     * @return bool
     */
    public function activar(): bool
    {
        $sql = "UPDATE apartamentos SET activo = TRUE WHERE id = ?";
        return Database::execute($sql, [$this->id]) > 0;
    }

    /**
     * Obtener identificador legible del apartamento
     *
     * @return string Ej: "29-501"
     */
    public function getIdentificador(): string
    {
        return "{$this->bloque}-{$this->numero_apartamento}";
    }

    /**
     * Obtener identificador completo
     *
     * @return string Ej: "Bloque 29, Escalera A, Piso 5, Apt 501"
     */
    public function getIdentificadorCompleto(): string
    {
        return "Bloque {$this->bloque}, Escalera {$this->escalera}, Piso {$this->piso}, Apt {$this->numero_apartamento}";
    }

    /**
     * Hidratar objeto desde array
     *
     * @param array $data Datos
     * @return Apartamento
     */
    private static function hydrate(array $data): Apartamento
    {
        $apartamento = new self();

        foreach ($data as $key => $value) {
            if (property_exists($apartamento, $key)) {
                $apartamento->$key = $value;
            }
        }

        return $apartamento;
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
            'bloque' => $this->bloque,
            'escalera' => $this->escalera,
            'piso' => $this->piso,
            'numero_apartamento' => $this->numero_apartamento,
            'identificador' => $this->getIdentificador(),
            'activo' => $this->activo
        ];
    }
}
