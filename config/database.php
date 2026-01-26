<?php
/**
 * Configuración de Base de Datos
 *
 * Establece la conexión PDO con MySQL usando credenciales del archivo .env
 * Implementa singleton pattern para reutilizar la misma conexión
 */

// Cargar variables de entorno
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar .env solo si existe
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

/**
 * Clase Database - Manejo de conexión PDO (Singleton)
 */
class Database
{
    private static ?PDO $instance = null;
    private static bool $testMode = false;

    /**
     * Configuración de la base de datos desde .env
     */
    private static function getConfig(): array
    {
        if (self::$testMode) {
            return [
                'driver'  => 'sqlite',
                'database' => ':memory:',
            ];
        }

        return [
            'host'    => $_ENV['DB_HOST'] ?? 'localhost',
            'port'    => $_ENV['DB_PORT'] ?? '3306',
            'dbname'  => $_ENV['DB_NAME'] ?? 'estacionamiento_db',
            'user'    => $_ENV['DB_USER'] ?? 'root',
            'pass'    => $_ENV['DB_PASS'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        ];
    }

    /**
     * Set test mode for using SQLite in-memory database
     */
    public static function setTestMode(bool $testMode = true): void
    {
        self::$testMode = $testMode;
        // Reset instance to force reconnection
        self::$instance = null;
    }

    /**
     * Obtener instancia única de PDO (Singleton)
     *
     * @return PDO
     * @throws PDOException
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = self::getConfig();

            if (self::$testMode) {
                $dsn = "sqlite:{$config['database']}";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                $user = null;
                $pass = null;
            } else {
                // Soporte para MySQL y PostgreSQL
                $driver = $_ENV['DB_CONNECTION'] ?? 'mysql';
                
                if ($driver === 'pgsql') {
                    $dsn = sprintf(
                        "pgsql:host=%s;port=%s;dbname=%s",
                        $config['host'],
                        $config['port'],
                        $config['dbname']
                    );
                    $options = [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ];
                } else {
                    $dsn = sprintf(
                        "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                        $config['host'],
                        $config['port'],
                        $config['dbname'],
                        $config['charset']
                    );
                    $options = [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
                    ];
                }
                
                $user = $config['user'];
                $pass = $config['pass'];
            }

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
                
                // Force UTF-8 encoding for all database operations
                if (!self::$testMode) {
                    self::$instance->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
                    self::$instance->exec("SET CHARACTER SET utf8mb4");
                    self::$instance->exec("SET character_set_connection=utf8mb4");
                    self::$instance->exec("SET character_set_client=utf8mb4");
                    self::$instance->exec("SET character_set_results=utf8mb4");
                }

                // Log de conexión exitosa (solo en desarrollo)
                if (($_ENV['APP_DEBUG'] ?? false) === 'true') {
                    error_log("[DB] Conexión establecida exitosamente");
                }

            } catch (PDOException $e) {
                error_log("[DB ERROR] No se pudo conectar: " . $e->getMessage());

                // En producción, mostrar mensaje genérico
                if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
                    throw new PDOException("Error de conexión a la base de datos");
                } else {
                    throw $e;
                }
            }
        }

        return self::$instance;
    }

    /**
     * Ejecutar una consulta preparada
     *
     * @param string $sql Consulta SQL
     * @param array $params Parámetros de la consulta
     * @return PDOStatement
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Obtener un solo registro
     *
     * @param string $sql Consulta SQL
     * @param array $params Parámetros
     * @return array|false
     */
    public static function fetchOne(string $sql, array $params = [])
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Obtener todos los registros
     *
     * @param string $sql Consulta SQL
     * @param array $params Parámetros
     * @return array
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insertar registro y obtener el ID insertado
     *
     * @param string $sql Consulta INSERT
     * @param array $params Parámetros
     * @return string ID del último registro insertado
     */
    public static function insert(string $sql, array $params = []): string
    {
        self::query($sql, $params);
        return self::getInstance()->lastInsertId();
    }

    /**
     * Ejecutar UPDATE o DELETE y retornar filas afectadas
     *
     * @param string $sql Consulta SQL
     * @param array $params Parámetros
     * @return int Número de filas afectadas
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Iniciar transacción
     */
    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }

    /**
     * Confirmar transacción
     */
    public static function commit(): void
    {
        self::getInstance()->commit();
    }

    /**
     * Revertir transacción
     */
    public static function rollback(): void
    {
        self::getInstance()->rollBack();
    }

    /**
     * Verificar si hay una transacción activa
     *
     * @return bool
     */
    public static function inTransaction(): bool
    {
        return self::getInstance()->inTransaction();
    }

    /**
     * Cerrar conexión (destruir singleton)
     */
    public static function closeConnection(): void
    {
        self::$instance = null;
    }
}

/**
 * Función helper para obtener la conexión PDO directamente
 *
 * @return PDO
 */
function getDB(): PDO
{
    return Database::getInstance();
}

/**
 * Función helper para ejecutar consultas rápidas
 *
 * @param string $sql
 * @param array $params
 * @return PDOStatement
 */
function db_query(string $sql, array $params = []): PDOStatement
{
    return Database::query($sql, $params);
}

/**
 * Función helper para obtener un registro
 *
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function db_fetch_one(string $sql, array $params = [])
{
    return Database::fetchOne($sql, $params);
}

/**
 * Función helper para obtener múltiples registros
 *
 * @param string $sql
 * @param array $params
 * @return array
 */
function db_fetch_all(string $sql, array $params = []): array
{
    return Database::fetchAll($sql, $params);
}

// Ejemplo de uso:
/*
try {
    // Opción 1: Usando la clase directamente
    $usuarios = Database::fetchAll("SELECT * FROM usuarios WHERE activo = ?", [true]);

    // Opción 2: Usando helpers
    $usuario = db_fetch_one("SELECT * FROM usuarios WHERE id = ?", [1]);

    // Transacciones
    Database::beginTransaction();
    try {
        Database::insert("INSERT INTO logs_actividad (accion) VALUES (?)", ['test']);
        Database::commit();
    } catch (Exception $e) {
        Database::rollback();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("Error de BD: " . $e->getMessage());
    die("Error en la base de datos");
}
*/
