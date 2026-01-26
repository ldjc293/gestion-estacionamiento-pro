<?php
/**
 * Modelo ConfiguracionTarifa
 *
 * Maneja las tarifas mensuales configurables por el administrador
 */

require_once __DIR__ . '/../../config/database.php';

class ConfiguracionTarifa
{
    public $id;
    public $monto_mensual_usd;
    public $fecha_vigencia_inicio;
    public $fecha_vigencia_fin;
    public $activo;
    public $creado_por;
    public $fecha_creacion;

    /**
     * Obtener la tarifa actualmente activa
     *
     * @return ConfiguracionTarifa|null
     */
    public static function getTarifaActual(): ?ConfiguracionTarifa
    {
        $sql = "SELECT * FROM configuracion_tarifas
                WHERE activo = TRUE
                AND fecha_vigencia_inicio <= CURRENT_DATE
                ORDER BY fecha_vigencia_inicio DESC
                LIMIT 1";

        $result = Database::fetchOne($sql);

        return $result ? self::hydrate($result) : null;
    }

    /**
     * Obtener todas las tarifas
     *
     * @return array
     */
    public static function getAll(): array
    {
        $sql = "SELECT ct.*, u.nombre_completo as creado_por_nombre
                FROM configuracion_tarifas ct
                LEFT JOIN usuarios u ON u.id = ct.creado_por
                ORDER BY ct.fecha_vigencia_inicio DESC";

        $results = Database::fetchAll($sql);

        return array_map(fn($row) => self::hydrate($row), $results);
    }

    /**
     * Crear nueva tarifa
     *
     * @param float $montoMensualUSD Monto en USD
     * @param string $fechaVigenciaInicio Fecha de inicio
     * @param int $creadoPor ID del usuario que crea
     * @param string|null $fechaVigenciaFin Fecha de fin (opcional)
     * @return int ID de la nueva tarifa
     */
    public static function crear(float $montoMensualUSD, string $fechaVigenciaInicio,
                                int $creadoPor, ?string $fechaVigenciaFin = null): int
    {
        try {
            Database::beginTransaction();

            // Desactivar tarifa anterior si existe
            $sqlUpdate = "UPDATE configuracion_tarifas
                          SET activo = FALSE, fecha_vigencia_fin = CURDATE()
                          WHERE activo = TRUE
                          AND fecha_vigencia_inicio < ?";

            Database::execute($sqlUpdate, [$fechaVigenciaInicio]);

            // Insertar nueva tarifa
            $sqlInsert = "INSERT INTO configuracion_tarifas
                          (monto_mensual_usd, fecha_vigencia_inicio, fecha_vigencia_fin, activo, creado_por)
                          VALUES (?, ?, ?, TRUE, ?)";

            $params = [$montoMensualUSD, $fechaVigenciaInicio, $fechaVigenciaFin, $creadoPor];
            $nuevaId = Database::execute($sqlInsert, $params);

            Database::commit();

            writeLog("Nueva tarifa creada: $montoMensualUSD USD desde $fechaVigenciaInicio por usuario ID: $creadoPor", 'info');

            return $nuevaId;

        } catch (Exception $e) {
            Database::rollback();
            writeLog("Error al crear tarifa: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Actualizar tarifa existente
     *
     * @param int $id ID de la tarifa
     * @param float $montoMensualUSD Nuevo monto
     * @param string $fechaVigenciaInicio Nueva fecha inicio
     * @param string|null $fechaVigenciaFin Nueva fecha fin
     * @return bool
     */
    public function actualizar(int $id, float $montoMensualUSD, string $fechaVigenciaInicio,
                              ?string $fechaVigenciaFin = null): bool
    {
        $sql = "UPDATE configuracion_tarifas
                SET monto_mensual_usd = ?, fecha_vigencia_inicio = ?, fecha_vigencia_fin = ?
                WHERE id = ?";

        $result = Database::execute($sql, [$montoMensualUSD, $fechaVigenciaInicio, $fechaVigenciaFin, $id]);

        if ($result > 0) {
            writeLog("Tarifa ID $id actualizada: $montoMensualUSD USD", 'info');
        }

        return $result > 0;
    }

    /**
     * Desactivar tarifa
     *
     * @param int $id ID de la tarifa
     * @return bool
     */
    public static function desactivar(int $id): bool
    {
        $sql = "UPDATE configuracion_tarifas
                SET activo = FALSE, fecha_vigencia_fin = CURDATE()
                WHERE id = ?";

        $result = Database::execute($sql, [$id]);

        if ($result > 0) {
            writeLog("Tarifa ID $id desactivada", 'info');
        }

        return $result > 0;
    }

    /**
     * Calcular monto mensual para un apartamento_usuario
     *
     * @param int $apartamentoUsuarioId ID del apartamento_usuario
     * @return float Monto total en USD
     */
    public static function calcularMontoMensual(int $apartamentoUsuarioId): float
    {
        // Obtener tarifa actual
        $tarifa = self::getTarifaActual();

        if (!$tarifa) {
            throw new Exception("No hay tarifa configurada");
        }

        // Obtener cantidad de controles del apartamento
        $sql = "SELECT cantidad_controles FROM apartamento_usuario WHERE id = ?";
        $apartamento = Database::fetchOne($sql, [$apartamentoUsuarioId]);

        if (!$apartamento) {
            throw new Exception("Apartamento no encontrado");
        }

        return $tarifa->monto_mensual_usd * $apartamento['cantidad_controles'];
    }

    /**
     * Obtener historial de cambios de tarifa
     *
     * @param int $limit Límite de registros
     * @return array
     */
    public static function getHistorialCambios(int $limit = 10): array
    {
        $sql = "SELECT ct.*, u.nombre_completo as modificado_por
                FROM configuracion_tarifas ct
                LEFT JOIN usuarios u ON u.id = ct.creado_por
                ORDER BY ct.fecha_creacion DESC
                LIMIT ?";

        return Database::fetchAll($sql, [$limit]);
    }

    /**
     * Hidratar objeto desde array
     *
     * @param array $data Datos
     * @return ConfiguracionTarifa
     */
    private static function hydrate(array $data): ConfiguracionTarifa
    {
        $tarifa = new self();

        foreach ($data as $key => $value) {
            if (property_exists($tarifa, $key)) {
                $tarifa->$key = $value;
            }
        }

        return $tarifa;
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
            'monto_mensual_usd' => $this->monto_mensual_usd,
            'fecha_vigencia_inicio' => $this->fecha_vigencia_inicio,
            'fecha_vigencia_fin' => $this->fecha_vigencia_fin,
            'activo' => $this->activo,
            'creado_por' => $this->creado_por,
            'fecha_creacion' => $this->fecha_creacion
        ];
    }
}